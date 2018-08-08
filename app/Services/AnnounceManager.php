<?php

declare(strict_types=1);

namespace App\Services;

use stdClass;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Validation\Factory as ValidationFactory;

/**
 * Note: For performance reasons the announce uses the query builder instead of Eloquent.
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
     * @var DatabaseManager
     */
    private $databaseManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var ValidationFactory
     */
    private $validationFactory;

    /**
     * @var Translator
     */
    private $translator;

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

    /**
     * @param Bencoder          $encoder
     * @param DatabaseManager   $databaseManager
     * @param CacheManager      $cacheManager
     * @param ValidationFactory $validationFactory
     * @param Translator        $translator
     */
    public function __construct(
        Bencoder $encoder,
        DatabaseManager $databaseManager,
        CacheManager $cacheManager,
        ValidationFactory $validationFactory,
        Translator $translator
    ) {
        $this->encoder = $encoder;
        $this->databaseManager = $databaseManager;
        $this->cacheManager = $cacheManager;
        $this->validationFactory = $validationFactory;
        $this->translator = $translator;
    }

    /**
     * @param Request $request
     *
     * @return string
     */
    public function announce(Request $request): string
    {
        $this->request = $request;

        $this->event = $this->request->input('event');

        // info_hash and peer_id are validated separately because the Laravel validator uses
        // mb_strlen to get the length of the (sometimes binary) string which returns a wrong number
        // when used on those two properties so strlen must be used
        // mb_strlen returns a "wrong" number because it counts code points instead of characters
        $validationMessage = $this->validateInfoHashAndPeerID();
        if (null !== $validationMessage) {
            return $validationMessage;
        }

        // validate the rest of the request (passkey, uploaded, downloaded, left, port)
        $validationMessage = $this->validateRequest();
        if (null !== $validationMessage) {
            return $validationMessage;
        }

        // if we get the stopped event there is no need to validate the IP address,
        // since we are just going to delete the peer from the DB
        if ('stopped' !== $this->event) {
            // in order to support IPv6 peers (BEP 7) a more complex IP validation logic is needed
            $validationMessage = $this->validateAndSetIPAddress();
            if (null !== $validationMessage) {
                return $validationMessage;
            }
        }

        $this->peerID = bin2hex($this->request->input('peer_id'));

        $this->user = $this->cacheManager->remember('user.' . $this->request->input('passkey'), 24 * 60, function () {
            return $this->databaseManager->table('users')
                ->where('passkey', '=', $this->request->input('passkey'))
                ->select(['id', 'slug', 'uploaded', 'downloaded'])
                ->first();
        });

        if (null === $this->user) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_passkey'));
        }

        $this->torrent = $this->databaseManager->table('torrents')
                                               ->where('info_hash', bin2hex($this->request->input('info_hash')))
                                               ->select(['id', 'seeders', 'leechers', 'slug'])
                                               ->first();

        if (null === $this->torrent) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_info_hash'));
        }

        $left = (int) $this->request->input('left');
        $this->seeder = 0 === $left ? true : false;

        $this->peer = $this->databaseManager->table('peers')
            ->where('peer_id', '=', $this->peerID)
            ->where('torrent_id', '=', $this->torrent->id)
            ->where('user_id', '=', $this->user->id)
            ->first();

        if ('completed' === $this->event || 'stopped' === $this->event) {
            if (null === $this->peer) {
                return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_peer_id'));
            }
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

        $this->snatch = $this->databaseManager->table('snatches')
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

    /**
     * Returns null if the validation is successful or a string if it is not.
     *
     * @return null|string
     */
    private function validateInfoHashAndPeerID(): ?string
    {
        if ($this->request->filled('info_hash')) {
            if (20 !== strlen($this->request->input('info_hash'))) {
                $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'info_hash']);

                return $this->announceErrorResponse($errorMessage);
            }
        } else {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'info_hash']);

            return $this->announceErrorResponse($errorMessage);
        }

        if ($this->request->filled('peer_id')) {
            if (20 !== strlen($this->request->input('peer_id'))) {
                $errorMessage = $this->translator->trans('messages.validation.variable.size', ['var' => 'peer_id']);

                return $this->announceErrorResponse($errorMessage);
            }
        } else {
            $errorMessage = $this->translator->trans('messages.validation.variable.required', ['var' => 'peer_id']);

            return $this->announceErrorResponse($errorMessage);
        }

        return null;
    }

    /**
     * Returns null if the validation is successful or a string if it is not.
     *
     * @return null|string
     */
    private function validateRequest(): ?string
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

            return $this->announceErrorResponse($errors->all());
        }

        return null;
    }

    /**
     * @return null|string
     */
    private function validateAndSetIPAddress(): ?string
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

        // return an error if there is not at least one IP address and port set
        if (false === ((null !== $this->ipv4Address && null !== $this->ipv4Port) ||
                (null !== $this->ipv6Address && null !== $this->ipv6Port))) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_ip_or_port'));
        }

        return null;
    }

    /**
     * @param string $IP
     *
     * @return bool
     */
    private function validateIPv4Address(string $IP): bool
    {
        if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return true;
        }

        return false;
    }

    /**
     * @param string $IP
     *
     * @return bool
     */
    private function validateIPv6Address(string $IP): bool
    {
        if (filter_var($IP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return true;
        }

        return false;
    }

    /**
     * Get the format for database stored dates.
     *
     * @return string
     */
    private function getDateFormat(): string
    {
        return $this->databaseManager->getQueryGrammar()->getDateFormat();
    }

    /**
     * @param int $seeder
     * @param int $leecher
     */
    private function adjustTorrentPeers(int $seeder, int $leecher): void
    {
        $this->torrent->seeders = $this->torrent->seeders + $seeder;
        $this->torrent->leechers = $this->torrent->leechers + $leecher;
        $this->databaseManager->table('torrents')->where('id', '=', $this->torrent->id)
            ->update(
                [
                    'seeders'  => $this->torrent->seeders,
                    'leechers' => $this->torrent->leechers,
                ]
            );
    }

    /**
     * Insert a new peer into the DB.
     */
    private function insertPeer(): void
    {
        $this->peer = new stdClass();
        $this->peer->id = $this->databaseManager->table('peers')->insertGetId(
            [
                'peer_id'    => $this->peerID,
                'torrent_id' => $this->torrent->id,
                'user_id'    => $this->user->id,
                'uploaded'   => $this->uploadedInThisAnnounceCycle,
                'downloaded' => $this->downloadedInThisAnnounceCycle,
                'seeder'     => $this->seeder,
                'userAgent'  => $this->request->userAgent(),
                'created_at' => Carbon::now()->format($this->getDateFormat()),
                'updated_at' => Carbon::now()->format($this->getDateFormat()),
            ]
        );
    }

    /**
     * Update the peer if it already exists in the DB.
     */
    private function updatePeerIfItExists(): void
    {
        if (null !== $this->peer) {
            $this->databaseManager->table('peers')
                ->where('id', '=', $this->peer->id)
                ->update(
                    [
                        'uploaded'   => $this->peer->uploaded + $this->uploadedInThisAnnounceCycle,
                        'downloaded' => $this->peer->downloaded + $this->downloadedInThisAnnounceCycle,
                        'seeder'     => $this->seeder,
                        'userAgent'  => $this->request->userAgent(),
                        'updated_at' => Carbon::now()->format($this->getDateFormat()),
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
        $this->snatch->id = $this->databaseManager->table('snatches')->insertGetId(
            [
                'torrent_id'     => $this->torrent->id,
                'user_id'        => $this->user->id,
                'uploaded'       => $this->uploadedInThisAnnounceCycle,
                'downloaded'     => $this->downloadedInThisAnnounceCycle,
                'left'           => $this->request->input('left'),
                'timesAnnounced' => 1,
                'userAgent'      => $this->request->userAgent(),
                'created_at'     => Carbon::now()->format($this->getDateFormat()),
                'updated_at'     => Carbon::now()->format($this->getDateFormat()),
            ]
        );
    }

    /**
     * Update the snatch if it already exists in the DB.
     */
    private function updateSnatchIfItExists(): void
    {
        if (null !== $this->snatch) {
            if (0 === (int) $this->request->input('left') && null === $this->snatch->finished_at) {
                $finishedAt = Carbon::now()->format($this->getDateFormat());
            } else {
                $finishedAt = $this->snatch->finished_at;
            }
            $this->databaseManager->table('snatches')
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
                        'updated_at'     => Carbon::now()->format($this->getDateFormat()),
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

        $this->databaseManager->table('users')
            ->where('id', '=', $this->user->id)
            ->update(
                [
                    'uploaded'   => $this->user->uploaded,
                    'downloaded' => $this->user->downloaded,
                ]
            );
        $this->cacheManager->put('user.' . $this->request->input('passkey'), $this->user, 24 * 60);
    }

    /**
     * Insert the peer IP address(es).
     */
    private function insertPeerIPs(): void
    {
        $this->databaseManager->table('peers_ip')->where('peerID', '=', $this->peer->id)->delete();

        if (false !== isset($this->ipv4Address) && false !== isset($this->ipv4Port)) {
            $this->databaseManager->table('peers_ip')->insert(
                [
                    'peerID' => $this->peer->id,
                    'IP'     => $this->ipv4Address,
                    'port'   => $this->ipv4Port,
                    'isIPv6' => false,
                ]
            );
        }

        if (false !== isset($this->ipv6Address) && false !== isset($this->ipv6Port)) {
            $this->databaseManager->table('peers_ip')->insert(
                [
                    'peerID' => $this->peer->id,
                    'IP'     => $this->ipv6Address,
                    'port'   => $this->ipv6Port,
                    'isIPv6' => true,
                ]
            );
        }
    }

    /**
     * @return string
     */
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

    /**
     * @return string
     */
    private function stoppedEventAnnounceResponse(): string
    {
        $this->databaseManager->table('peers')->where('id', '=', $this->peer->id)->delete();

        if (true === $this->seeder) {
            $this->adjustTorrentPeers(-1, 0);
        } else {
            $this->adjustTorrentPeers(0, -1);
        }

        $this->updateSnatchIfItExists();

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
    private function completedEventAnnounceResponse(): string
    {
        $this->updatePeerIfItExists();
        $this->insertPeerIPs();
        $this->adjustTorrentPeers(1, -1);
        $this->updateSnatchIfItExists();

        return $this->announceSuccessResponse();
    }

    /**
     * @return string
     */
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

    /**
     * @return Collection
     */
    protected function getPeers(): Collection
    {
        return $this->databaseManager->table('peers')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peerID')
            ->when($this->seeder, function (Builder $query) {
                return $query->where('seeder', '!=', true);
            })
            ->where('user_id', '!=', $this->user->id)
            ->where('torrent_id', '=', $this->torrent->id)
            ->limit($this->numberOfWantedPeers)
            ->inRandomOrder()
            ->select('peer_id', 'seeder', 'peers_ip.*')
            ->get();
    }

    /**
     * @return string
     */
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

    /**
     * @return array
     */
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

    /**
     * @return array
     */
    private function getCommonResponsePart(): array
    {
        $response['interval'] = 40 * 60; // 40 minutes
        $response['min interval'] = 1 * 60; // 1 minute

        $peersCount = $this->getSeedersAndLeechersCount();
        $response['complete'] = $peersCount[0];
        $response['incomplete'] = $peersCount[1];

        return $response;
    }

    /**
     * @return string
     */
    private function compactResponse(): string
    {
        $response = $this->getCommonResponsePart();

        $response['peers'] = '';

        // BEP 7 -> IPv6 peers support
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

    /**
     * @return string
     */
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
     *
     * @return string
     */
    private function announceErrorResponse($error): string
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

        return $this->encoder->encode($response);
    }
}
