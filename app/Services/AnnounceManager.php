<?php

declare(strict_types=1);

namespace App\Services;

use stdClass;
use Carbon\Carbon;
use App\Enumerations\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use App\Exceptions\AnnounceValidationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

/**
 * Note: For performance reasons the query builder is used instead of Eloquent.
 */
class AnnounceManager
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var Bencoder
     */
    private $encoder;

    /**
     * @var ConnectionInterface
     */
    private $connection;

    /**
     * @var CacheRepository
     */
    private $cache;

    /**
     * @var ValidationFactory
     */
    private $validationFactory;

    /**
     * @var Translator
     */
    private $translator;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var stdClass
     */
    private $user;

    /**
     * @var null|stdClass
     */
    private $peer = null;

    /**
     * @var string
     */
    private $peerID;

    /**
     * @var stdClass
     */
    private $torrent;

    /**
     * @var bool
     */
    private $seeder;

    /**
     * @var string|null
     */
    private $event;

    /**
     * @var null|stdClass
     */
    private $snatch;

    /**
     * @var int
     */
    private $numberOfWantedPeers = 50;

    /**
     * @var null|string
     */
    private $ipv4Address = null;

    /**
     * @var null|string
     */
    private $ipv6Address = null;

    /**
     * @var null|int
     */
    private $ipv4Port = null;

    /**
     * @var null|int
     */
    private $ipv6Port = null;

    /**
     * @var int
     */
    private $seedTime = 0;

    /**
     * @var int
     */
    private $leechTime = 0;

    /**
     * @var int
     */
    private $downloadedInThisAnnounceCycle = 0;

    /**
     * @var int
     */
    private $uploadedInThisAnnounceCycle = 0;

    public function __construct(
        Bencoder $encoder,
        ConnectionInterface $connection,
        CacheRepository $cache,
        ValidationFactory $validationFactory,
        Translator $translator,
        Repository $config
    ) {
        $this->encoder = $encoder;
        $this->connection = $connection;
        $this->cache = $cache;
        $this->validationFactory = $validationFactory;
        $this->translator = $translator;
        $this->config = $config;
    }

    public function announce(Request $request): string
    {
        $this->request = $request;

        $this->event = $this->request->input('event');

        try {
            // info_hash and peer_id are validated separately because the Laravel validator uses
            // mb_strlen to get the length of the (sometimes binary) string which returns a wrong number
            // when used on those two properties so strlen must be used
            // mb_strlen returns a "wrong" number because it counts code points instead of characters
            $this->validateInfoHash();
            $this->validatePeerID();
            // validate the rest of the request (passkey, uploaded, downloaded, left, port)
            $this->validateRequest();
        } catch (AnnounceValidationException $exception) {
            $validationData = $exception->getValidationMessages() ?: $exception->getMessage();

            return $this->announceErrorResponse($validationData);
        }

        // if we get the stopped event there is no need to validate the IP address,
        // since we are just going to delete the peer from the DB
        if ('stopped' !== $this->event) {
            try {
                // in order to support IPv6 peers (BEP 7) a more complex IP validation logic is needed
                $this->validateAndSetIPAddress();
            } catch (AnnounceValidationException $exception) {
                return $this->announceErrorResponse($exception->getMessage());
            }
        }

        $this->user = $this->getUser($this->request->input('passkey'));

        if (null === $this->user) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_passkey'), true);
        }

        if (true === (bool) $this->user->banned) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.banned_user'), true);
        }

        $this->torrent = $this->connection->table('torrents')
                                               ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
                                               ->where('info_hash', '=', bin2hex($this->request->input('info_hash')))
                                               ->select(['torrents.id', 'seeders', 'leechers', 'slug', 'version'])
                                               ->first();

        if (null === $this->torrent) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_info_hash'));
        }

        $left = (int) $this->request->input('left');
        $this->seeder = 0 === $left ? true : false;

        $this->peerID = bin2hex($this->request->input('peer_id'));

        $this->peer = $this->connection->table('peers')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->where('peer_id', '=', $this->peerID)
            ->where('torrent_id', '=', $this->torrent->id)
            ->where('user_id', '=', $this->user->id)
            ->select('peers.*', 'peers_version.version')
            ->first();

        if (null === $this->peer && ('completed' === $this->event || 'stopped' === $this->event)) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_peer_id'));
        }

        $timeNow = Carbon::now();
        $downloaded = $this->request->input('downloaded');
        $uploaded = $this->request->input('uploaded');

        if (null === $this->peer) {
            $this->downloadedInThisAnnounceCycle = $downloaded;
            $this->uploadedInThisAnnounceCycle = $uploaded;
        } else {
            $this->downloadedInThisAnnounceCycle = max(0, $downloaded - $this->peer->downloaded);
            $this->uploadedInThisAnnounceCycle = max(0, $uploaded - $this->peer->uploaded);
            if (false === $this->seeder || (true === $this->seeder && 'completed' === $this->event)) {
                $this->leechTime = $timeNow->diffInSeconds(new Carbon($this->peer->updated_at));
            } else {
                $this->seedTime = $timeNow->diffInSeconds(new Carbon($this->peer->updated_at));
            }
        }

        $this->snatch = $this->connection->table('snatches')
                                ->where('torrent_id', '=', $this->torrent->id)
                                ->where('user_id', '=', $this->user->id)
                                ->first();

        if ($this->request->filled('numwant') && (int) $this->request->input('numwant') > 0) {
            $this->numberOfWantedPeers = (int) $this->request->input('numwant');
        }

        if ('started' === $this->event) {
            return $this->startedEventAnnounceResponse();
        } elseif ('stopped' === $this->event) {
            return $this->stoppedEventAnnounceResponse();
        } elseif ('completed' === $this->event && 0 === $left) {
            return $this->completedEventAnnounceResponse();
        }

        return $this->noEventAnnounceResponse();
    }

    public function scrape(array $infoHashes): string
    {
        $response = [];

        foreach ($infoHashes as $infoHash) {
            $torrent = $this->connection
                ->table('torrents')
                ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
                ->where('info_hash', '=', bin2hex($infoHash))
                ->select(['torrents.id', 'seeders', 'leechers'])
                ->first();

            if (null === $torrent) {
                continue;
            }

            $snatchesCount = $this->connection
                ->table('snatches')
                ->where('torrent_id', '=', $torrent->id)
                ->where('left', '=', 0)
                ->count();

            $response['files'][$infoHash] = [
                'complete' => (int) $torrent->seeders,
                'incomplete' => (int) $torrent->leechers,
                'downloaded' => $snatchesCount,
            ];
        }

        if (empty($response['files'])) {
            return $this->announceErrorResponse($this->translator->trans('messages.scrape.no_torrents'));
        }

        return $this->encoder->encode($response);
    }

    public function getUser(string $passkey): ?stdClass
    {
        return $this->cache->remember('user.' . $passkey, Cache::ONE_DAY, function () use ($passkey) {
            return $this->connection->table('users')
                ->where('passkey', '=', $passkey)
                ->select(['id', 'slug', 'uploaded', 'downloaded', 'banned'])
                ->first();
        });
    }

    /**
     * @throws AnnounceValidationException
     */
    private function validateInfoHash(): void
    {
        if (true !== $this->request->filled('info_hash')) {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (20 !== strlen($this->request->input('info_hash'))) {
            $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'info_hash']);

            throw new AnnounceValidationException($errorMessage);
        }
    }

    /**
     * @throws AnnounceValidationException
     */
    private function validatePeerID(): void
    {
        if (true !== $this->request->filled('peer_id')) {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }

        if (20 !== strlen($this->request->input('peer_id'))) {
            $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'peer_id']);

            throw new AnnounceValidationException($errorMessage);
        }
    }

    /**
     * @throws AnnounceValidationException
     */
    private function validateRequest(): void
    {
        $validator = $this->validationFactory->make(
            $this->request->all(),
            [
                'passkey'    => 'required|string|size:64',
                'port'       => 'required|integer|min:1|max:65535',
                'uploaded'   => 'required|integer|min:0',
                'downloaded' => 'required|integer|min:0',
                'left'       => 'required|integer|min:0',
                'numwant'    => 'sometimes|integer',
            ],
            [
                'passkey.required'    => $this->translator->trans('messages.validation.variable.required', ['var' => 'passkey']),
                'passkey.string'      => $this->translator->trans('messages.validation.variable.string', ['var' => 'passkey']),
                'passkey.size'        => $this->translator->trans('messages.validation.variable.size', ['var' => 'passkey']),
                'port.required'       => $this->translator->trans('messages.validation.variable.required', ['var' => 'port']),
                'port.integer'        => $this->translator->trans('messages.validation.variable.port', ['port' => $this->request->input('port')]),
                'port.min'            => $this->translator->trans('messages.validation.variable.port', ['port' => $this->request->input('port')]),
                'port.max'            => $this->translator->trans('messages.validation.variable.port', ['port' => $this->request->input('port')]),
                'uploaded.required'   => $this->translator->trans('messages.validation.variable.required', ['var' => 'uploaded']),
                'uploaded.integer'    => $this->translator->trans('messages.validation.variable.integer', ['var' => 'uploaded']),
                'uploaded.min'        => $this->translator->trans('messages.validation.variable.uploaded', ['uploaded' => $this->request->input('uploaded')]),
                'downloaded.required' => $this->translator->trans('messages.validation.variable.required', ['var' => 'downloaded']),
                'downloaded.integer'  => $this->translator->trans('messages.validation.variable.integer', ['var' => 'downloaded']),
                'downloaded.min'      => $this->translator->trans('messages.validation.variable.downloaded', ['downloaded' => $this->request->input('downloaded')]),
                'left.required'       => $this->translator->trans('messages.validation.variable.required', ['var' => 'left']),
                'left.integer'        => $this->translator->trans('messages.validation.variable.integer', ['var' => 'left']),
                'left.min'            => $this->translator->trans('messages.validation.variable.left', ['left' => $this->request->input('left')]),
                'numwant.integer'     => $this->translator->trans('messages.validation.variable.integer', ['var' => 'numwant']),
            ]
        );

        if ($validator->fails()) {
            $errors = $validator->errors();

            throw new AnnounceValidationException('', $errors->all());
        }
    }

    /**
     * @throws AnnounceValidationException
     */
    private function validateAndSetIPAddress(): void
    {
        $this->ipv4Port = $this->request->input('port');
        $this->ipv6Port = $this->request->input('port');

        if ($this->request->filled('ip')) {
            $IP = $this->request->input('ip');

            if (true === $this->validateIPv4Address($IP)) {
                $this->ipv4Address = $IP;
            }

            if (true === $this->validateIPv6Address($IP)) {
                $this->ipv6Address = $IP;
            }
        }

        if ($this->request->filled('ipv4')) {
            $IP = $this->request->input('ipv4');
            $explodedIPString = explode(':', $IP);
            // check if the ipv4 field has the IP address and the port
            // if it contains only the IP address the port is read from the port field
            if (2 === count($explodedIPString)) {
                if (true === $this->validateIPv4Address($explodedIPString[0])) {
                    $this->ipv4Address = $explodedIPString[0];
                    $port = (int) $explodedIPString[1];
                    if ($port >= 1 && $port <= 65535) {
                        $this->ipv4Port = $port;
                    }
                }
            } else {
                if (true === $this->validateIPv4Address($IP)) {
                    $this->ipv4Address = $IP;
                }
            }
        }

        if ($this->request->filled('ipv6')) {
            $IP = $this->request->input('ipv6');
            $explodedIPString = explode(':', $IP);
            // check if the ipv6 field has the IP address and the port
            // if it contains only the IP address the port is read from the port field
            if (4 <= count($explodedIPString) && '[' === $IP[0] && false !== strpos($IP, ']')) {
                $IPWithPort = str_replace(['[',']'], '', $IP);
                $IP = substr($IPWithPort, 0, strrpos($IPWithPort, ':'));
                if (true === $this->validateIPv6Address($IP)) {
                    $this->ipv6Address = $IP;
                    $port = (int) substr($IPWithPort, strrpos($IPWithPort, ':') + 1);
                    if ($port >= 1 && $port <= 65535) {
                        $this->ipv6Port = $port;
                    }
                }
            } else {
                if (true === $this->validateIPv6Address($IP)) {
                    $this->ipv6Address = $IP;
                }
            }
        }

        // this is the most secure way to get the real IP address because for example
        // uTorrent with Teredo enabled sends only an "IPv6" address even though the peer
        // has actually only an IPv4 address
        $IP = $this->request->getClientIp();

        if (true === $this->validateIPv4Address($IP)) {
            $this->ipv4Address = $IP;
        }

        if (true === $this->validateIPv6Address($IP)) {
            $this->ipv6Address = $IP;
        }

        // throw the validation exception if there is not at least one IP address and port set
        if (false === ((null !== $this->ipv4Address && null !== $this->ipv4Port) ||
                (null !== $this->ipv6Address && null !== $this->ipv6Port))) {
            throw new AnnounceValidationException($this->translator->trans('messages.announce.invalid_ip_or_port'));
        }
    }

    private function validateIPv4Address(string $IP): bool
    {
        if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        return false;
    }

    private function validateIPv6Address(string $IP): bool
    {
        if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        return false;
    }

    private function adjustTorrentPeers(int $seeder, int $leecher): void
    {
        $this->torrent->seeders = $this->torrent->seeders + $seeder;
        $this->torrent->leechers = $this->torrent->leechers + $leecher;
        $this->connection->table('torrents')->where('id', '=', $this->torrent->id)
            ->update(
                [
                    'seeders'  => $this->torrent->seeders,
                    'leechers' => $this->torrent->leechers,
                ]
            );
        $this->cache->delete('torrent.' . $this->torrent->id);
    }

    /**
     * Insert a new peer into the DB.
     */
    private function insertPeer(): void
    {
        $this->peer = new stdClass();
        $this->peer->id = $this->connection->table('peers')->insertGetId(
            [
                'peer_id'    => $this->peerID,
                'torrent_id' => $this->torrent->id,
                'user_id'    => $this->user->id,
                'uploaded'   => $this->uploadedInThisAnnounceCycle,
                'downloaded' => $this->downloadedInThisAnnounceCycle,
                'seeder'     => $this->seeder,
                'userAgent'  => $this->request->userAgent(),
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
        $this->connection->table('peers_version')->insert(
            [
                'peerID'     => $this->peer->id,
                'version'    => $this->torrent->version,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]
        );
    }

    /**
     * Update the peer if it already exists in the DB.
     */
    private function updatePeerIfItExists(): void
    {
        if (null !== $this->peer) {
            $this->connection->table('peers')
                ->where('id', '=', $this->peer->id)
                ->update(
                    [
                        'uploaded'   => $this->peer->uploaded + $this->uploadedInThisAnnounceCycle,
                        'downloaded' => $this->peer->downloaded + $this->downloadedInThisAnnounceCycle,
                        'seeder'     => $this->seeder,
                        'userAgent'  => $this->request->userAgent(),
                        'updated_at' => Carbon::now(),
                    ]
                );
            $this->connection->table('peers_version')
                ->where('id', '=', $this->peer->id)
                ->where('version', '=', $this->torrent->version)
                ->update(
                    [
                        'updated_at' => Carbon::now(),
                    ]
                );
        }
    }

    /**
     * Insert a new snatch into the DB.
     */
    private function insertSnatch(): void
    {
        $this->snatch = new stdClass();
        $this->snatch->id = $this->connection->table('snatches')->insertGetId(
            [
                'torrent_id'     => $this->torrent->id,
                'user_id'        => $this->user->id,
                'uploaded'       => $this->uploadedInThisAnnounceCycle,
                'downloaded'     => $this->downloadedInThisAnnounceCycle,
                'left'           => $this->request->input('left'),
                'timesAnnounced' => 1,
                'userAgent'      => $this->request->userAgent(),
                'created_at'     => Carbon::now(),
                'updated_at'     => Carbon::now(),
            ]
        );
    }

    /**
     * Update the snatch if it already exists in the DB.
     */
    private function updateSnatchIfItExists(): void
    {
        if (null !== $this->snatch) {
            $finishedAt = $this->snatch->finished_at;
            if (0 === (int) $this->request->input('left') && null === $this->snatch->finished_at) {
                $finishedAt = Carbon::now();
            }

            $this->connection->table('snatches')
                ->where('id', '=', $this->snatch->id)
                ->update(
                    [
                        'uploaded'       => $this->snatch->uploaded + $this->uploadedInThisAnnounceCycle,
                        'downloaded'     => $this->snatch->downloaded + $this->downloadedInThisAnnounceCycle,
                        'left'           => $this->request->input('left'),
                        'seedTime'       => $this->snatch->seedTime + $this->seedTime,
                        'leechTime'      => $this->snatch->leechTime + $this->leechTime,
                        'timesAnnounced' => $this->snatch->timesAnnounced + 1,
                        'finished_at'    => $finishedAt,
                        'userAgent'      => $this->request->userAgent(),
                        'updated_at'     => Carbon::now(),
                    ]
                );
        }
    }

    /**
     * Update the user uploaded and downloaded data.
     */
    private function updateUser(): void
    {
        $this->user->uploaded = $this->user->uploaded + $this->uploadedInThisAnnounceCycle;
        $this->user->downloaded = $this->user->downloaded + $this->downloadedInThisAnnounceCycle;

        $this->connection->table('users')
            ->where('id', '=', $this->user->id)
            ->update(
                [
                    'uploaded'   => $this->user->uploaded,
                    'downloaded' => $this->user->downloaded,
                ]
            );
        $this->cache->put('user.' . $this->request->input('passkey'), $this->user, Cache::ONE_DAY);
    }

    /**
     * Insert the peer IP address(es).
     */
    private function insertPeerIPs(): void
    {
        $this->connection->table('peers_ip')->where('peerID', '=', $this->peer->id)->delete();

        if (! empty($this->ipv4Address) && ! empty($this->ipv4Port)) {
            $this->connection->table('peers_ip')->insert(
                [
                    'peerID' => $this->peer->id,
                    'IP'     => $this->ipv4Address,
                    'port'   => $this->ipv4Port,
                    'isIPv6' => false,
                ]
            );
        }

        if (! empty($this->ipv6Address) && ! empty($this->ipv6Port)) {
            $this->connection->table('peers_ip')->insert(
                [
                    'peerID' => $this->peer->id,
                    'IP'     => $this->ipv6Address,
                    'port'   => $this->ipv6Port,
                    'isIPv6' => true,
                ]
            );
        }
    }

    private function startedEventAnnounceResponse(): string
    {
        if (null !== $this->peer) {
            $this->updatePeerIfItExists();
            $this->updateSnatchIfItExists();
        } else {
            $this->insertPeer();

            if (true === $this->seeder) {
                $this->adjustTorrentPeers(1, 0);
            } else {
                $this->adjustTorrentPeers(0, 1);

                if (null !== $this->snatch) {
                    $this->updateSnatchIfItExists();
                } else {
                    $this->insertSnatch();
                }
            }
        }

        $this->insertPeerIPs();

        return $this->announceSuccessResponse();
    }

    private function stoppedEventAnnounceResponse(): string
    {
        $this->connection->table('peers')->where('id', '=', $this->peer->id)->delete();

        if (true === $this->seeder) {
            $this->adjustTorrentPeers(-1, 0);
        } else {
            $this->adjustTorrentPeers(0, -1);
        }

        $this->updateSnatchIfItExists();

        return $this->announceSuccessResponse();
    }

    private function completedEventAnnounceResponse(): string
    {
        $this->updatePeerIfItExists();
        $this->insertPeerIPs();
        $this->adjustTorrentPeers(1, -1);
        $this->updateSnatchIfItExists();

        return $this->announceSuccessResponse();
    }

    private function noEventAnnounceResponse(): string
    {
        if (null !== $this->peer) {
            $this->updatePeerIfItExists();
        } else {
            $this->insertPeer();
            if (true === $this->seeder) {
                $this->adjustTorrentPeers(1, 0);
            } else {
                $this->adjustTorrentPeers(0, 1);
            }
        }

        $this->insertPeerIPs();
        $this->updateSnatchIfItExists();

        return $this->announceSuccessResponse();
    }

    protected function getPeers(): Collection
    {
        return $this->connection->table('peers')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peerID')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->when($this->seeder, function (Builder $query) {
                return $query->where('seeder', '!=', true);
            })
            ->where('user_id', '!=', $this->user->id)
            ->where('torrent_id', '=', $this->torrent->id)
            ->where('peers_version.version', '=', $this->torrent->version)
            ->limit($this->numberOfWantedPeers)
            ->inRandomOrder()
            ->select('peer_id', 'seeder', 'peers_ip.*')
            ->get();
    }

    private function announceSuccessResponse(): string
    {
        $this->updateUser();

        $compact = $this->request->input('compact');
        // return compact response if the client wants a compact response or if the client did not
        // specify what kind of response it wants, else return non-compact response
        if (null === $compact || 1 === (int) $compact) {
            return $this->compactResponse();
        }

        return $this->nonCompactResponse();
    }

    private function getSeedersAndLeechersCount(): array
    {
        $seedersCount = (int) $this->torrent->seeders;
        $leechersCount = (int) $this->torrent->leechers;
        // We don't want to include the current user/peer in the returned seeder/leecher count.
        if ('stopped' !== $this->event) {
            if (true === $this->seeder) {
                $seedersCount--;
            } else {
                $leechersCount--;
            }
        }

        return [$seedersCount, $leechersCount];
    }

    private function getCommonResponsePart(): array
    {
        $response['interval'] = $this->config->get('tracker.announce_interval') * 60;
        $response['min interval'] = $this->config->get('tracker.min_announce_interval') * 60;

        $peersCount = $this->getSeedersAndLeechersCount();
        $response['complete'] = $peersCount[0];
        $response['incomplete'] = $peersCount[1];

        return $response;
    }

    private function compactResponse(): string
    {
        $response = $this->getCommonResponsePart();

        $response['peers'] = '';

        // BEP 7 -> IPv6 peers support -> http://www.bittorrent.org/beps/bep_0007.html
        $response['peers6'] = '';

        $peers = $this->getPeers();

        foreach ($peers as $peer) {
            $peerIPAddress = inet_pton($peer->IP);
            $peerPort = pack('n*', $peer->port);

            if (true === (bool) $peer->isIPv6) {
                $response['peers6'] .= $peerIPAddress . $peerPort;
            } else {
                $response['peers'] .= $peerIPAddress . $peerPort;
            }
        }

        return $this->encoder->encode($response);
    }

    private function nonCompactResponse(): string
    {
        $response = $this->getCommonResponsePart();
        $response['peers'] = [];

        $peers = $this->getPeers();

        foreach ($peers as $peer) {
            // IPv6 peers are not separated for non-compact responses
            $response['peers'][] = [
                'peer id' => hex2bin($peer->peer_id),
                'ip'      => $peer->IP,
                'port'    => (int) $peer->port,
            ];
        }

        return $this->encoder->encode($response);
    }

    /**
     * @param array|string $error
     */
    private function announceErrorResponse($error, bool $neverRetry = false): string
    {
        $response['failure reason'] = '';
        if (is_array($error)) {
            $i = 0;
            $numberOfElements = count($error);
            foreach ($error as $message) {
                if ($numberOfElements - 1 === $i) {
                    $response['failure reason'] .= $message;
                } else {
                    $response['failure reason'] .= $message . ' ';
                }
                $i++;
            }
        } else {
            $response['failure reason'] = $error;
        }

        // BEP 31 -> http://www.bittorrent.org/beps/bep_0031.html
        if (true === $neverRetry) {
            $response['retry in'] = 'never';
        }

        return $this->encoder->encode($response);
    }
}
