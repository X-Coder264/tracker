<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Enumerations\AnnounceEvent;
use App\Presenters\Announce\Data;
use App\Presenters\Ip;
use App\Services\Bencoder;
use Carbon\CarbonImmutable;
use Generator;
use stdClass;
use Carbon\Carbon;
use App\Enumerations\Cache;
use Illuminate\Http\Request;
use Illuminate\Database\Query\Builder;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use App\Exceptions\AnnounceValidationException;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Note: For performance reasons the query builder is used instead of Eloquent.
 */
class Manager
{
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
     * @var Translator
     */
    private $translator;

    /**
     * @var Repository
     */
    private $config;

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
     * @var DataFactory
     */
    private $dataFactory;

    public function __construct(
        Bencoder $encoder,
        ConnectionInterface $connection,
        CacheRepository $cache,
        Translator $translator,
        Repository $config,
        DataFactory $dataFactory
    ) {
        $this->encoder = $encoder;
        $this->connection = $connection;
        $this->cache = $cache;
        $this->translator = $translator;
        $this->config = $config;
        $this->dataFactory = $dataFactory;
    }

    public function announce(Request $request): string
    {
        try {
            $data = $this->dataFactory->makeFromRequest($request);
        } catch (AnnounceValidationException $exception) {
            $validationData = $exception->getValidationMessages() ?: $exception->getMessage();

            return $this->announceErrorResponse($validationData);
        }

        $user = $this->getUser($data->getPassKey());

        if (null === $user) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_passkey'), true);
        }

        if (true === (bool) $user->banned) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.banned_user'), true);
        }

        $torrent = $this
            ->connection
            ->table('torrents')
            ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
            ->where('info_hash', '=', bin2hex($data->getInfoHash()))
            ->select(['torrents.id', 'seeders', 'leechers', 'slug', 'version'])
            ->first();

        if (null === $torrent) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_info_hash'));
        }

        $isSeeding = 0 === $data->getLeft();

        $peer = $this
            ->connection
            ->table('peers')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->where('peer_id', '=', bin2hex($data->getPeerId()))
            ->where('torrent_id', '=', $torrent->id)
            ->where('user_id', '=', $user->id)
            ->select('peers.*', 'peers_version.version')
            ->first();

        if (null === $peer && (AnnounceEvent::COMPLETED === $data->getEvent() || AnnounceEvent::STOPPED === $data->getEvent())) {
            return $this->announceErrorResponse($this->translator->trans('messages.announce.invalid_peer_id'));
        }

        $timeNow = Carbon::now();
        $downloaded = $data->getDownloaded();
        $uploaded = $data->getUploaded();

        if (null === $peer) {
            $this->downloadedInThisAnnounceCycle = $downloaded;
            $this->uploadedInThisAnnounceCycle = $uploaded;
        } else {
            $this->downloadedInThisAnnounceCycle = max(0, $downloaded - $peer->downloaded);
            $this->uploadedInThisAnnounceCycle = max(0, $uploaded - $peer->uploaded);
            if (false === $isSeeding || (true === $isSeeding && AnnounceEvent::COMPLETED === $data->getEvent())) {
                $this->leechTime = $timeNow->diffInSeconds(new Carbon($peer->updated_at));
            } else {
                $this->seedTime = $timeNow->diffInSeconds(new Carbon($peer->updated_at));
            }
        }

        $snatch = $this
            ->connection
            ->table('snatches')
            ->where('torrent_id', '=', $torrent->id)
            ->where('user_id', '=', $user->id)
            ->first();

        switch ($data->getEvent()){
            case AnnounceEvent::STARTED:
                return $this->startedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding);
            case AnnounceEvent::STOPPED:
                return $this->stoppedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding);
            case AnnounceEvent::COMPLETED:
                if(0 === $data->getLeft()){
                    return $this->completedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding);
                }
        }

        return $this->noEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding);
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

    private function adjustTorrentPeers(stdClass $torrent, int $seeder, int $leecher): void
    {
        $torrent->seeders = $torrent->seeders + $seeder;
        $torrent->leechers = $torrent->leechers + $leecher;
        $this->connection
            ->table('torrents')
            ->where('id', '=', $torrent->id)
            ->update(
                [
                    'seeders'  => $torrent->seeders,
                    'leechers' => $torrent->leechers,
                ]
            );
        $this->cache->forget(sprintf('torrent.%s', $torrent->id));
    }

    /**
     * Insert a new peer into the DB.
     */
    private function insertPeer(Data $data, stdClass $user, stdClass $torrent, bool $isSeeding): stdClass
    {
        $now = CarbonImmutable::now();

        $peer = new stdClass();
        $peer->id = $this->connection
            ->table('peers')
            ->insertGetId(
            [
                'peer_id'    => bin2hex($data->getPeerId()),
                'torrent_id' => $torrent->id,
                'user_id'    => $user->id,
                'uploaded'   => $this->uploadedInThisAnnounceCycle,
                'downloaded' => $this->downloadedInThisAnnounceCycle,
                'seeder'     => $isSeeding,
                'userAgent'  => $data->getUserAgent(),
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );
        $this->connection->table('peers_version')->insert(
            [
                'peerID'     => $peer->id,
                'version'    => $torrent->version,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        $this->cache->forget(sprintf('user.%s.peers', $user->id));

        return $peer;
    }

    /**
     * Update the peer if it already exists in the DB.
     */
    private function updatePeerIfItExists(Data $data, ?stdClass $peer, stdClass $torrent, bool $isSeeding): void
    {
        if (null === $peer){
            return;
        }

        $now = CarbonImmutable::now();

        $this->connection
            ->table('peers')
            ->where('id', '=', $peer->id)
            ->update(
                [
                    'uploaded'   => $peer->uploaded + $this->uploadedInThisAnnounceCycle,
                    'downloaded' => $peer->downloaded + $this->downloadedInThisAnnounceCycle,
                    'seeder'     => $isSeeding,
                    'userAgent'  => $data->getUserAgent(),
                    'updated_at' => $now,
                ]
            );
        $this->connection
            ->table('peers_version')
            ->where('id', '=', $peer->id)
            ->where('version', '=', $torrent->version)
            ->update(
                [
                    'updated_at' => $now,
                ]
            );
    }

    /**
     * Insert a new snatch into the DB.
     */
    private function insertSnatch(Data $data, stdClass $user, stdClass $torrent): void
    {
        $now = CarbonImmutable::now();

        $snatch = new stdClass();
        $snatch->id = $this->connection
            ->table('snatches')
            ->insertGetId(
            [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->id,
                'uploaded'       => $this->uploadedInThisAnnounceCycle,
                'downloaded'     => $this->downloadedInThisAnnounceCycle,
                'left'           => $data->getLeft(),
                'timesAnnounced' => 1,
                'userAgent'      => $data->getUserAgent(),
                'created_at'     => $now,
                'updated_at'     => $now,
            ]
        );
    }

    /**
     * Update the snatch if it already exists in the DB.
     */
    private function updateSnatchIfItExists(Data $data, ?stdClass $snatch): void
    {
        if (null === $snatch){
            return;
        }

        $now = CarbonImmutable::now();

        $finishedAt = $snatch->finished_at;
        if (0 === $data->getLeft() && null === $snatch->finished_at) {
            $finishedAt = $now;
        }

        $this->connection
            ->table('snatches')
            ->where('id', '=', $snatch->id)
            ->update(
                [
                    'uploaded'       => $snatch->uploaded + $this->uploadedInThisAnnounceCycle,
                    'downloaded'     => $snatch->downloaded + $this->downloadedInThisAnnounceCycle,
                    'left'           => $data->getLeft(),
                    'seedTime'       => $snatch->seedTime + $this->seedTime,
                    'leechTime'      => $snatch->leechTime + $this->leechTime,
                    'timesAnnounced' => $snatch->timesAnnounced + 1,
                    'finished_at'    => $finishedAt,
                    'userAgent'      => $data->getUserAgent(),
                    'updated_at'     => $now,
                ]
            );
    }

    /**
     * Update the user uploaded and downloaded data.
     */
    private function updateUser(Data $data, stdClass $user): void
    {
        $user->uploaded = $user->uploaded + $this->uploadedInThisAnnounceCycle;
        $user->downloaded = $user->downloaded + $this->downloadedInThisAnnounceCycle;

        $this->connection->table('users')
            ->where('id', '=', $user->id)
            ->update(
                [
                    'uploaded'   => $user->uploaded,
                    'downloaded' => $user->downloaded,
                ]
            );
        $this->cache->put(sprintf('user.%s', $data->getPassKey()), $user, Cache::ONE_DAY);
    }

    /**
     * Insert the peer IP address(es).
     */
    private function insertPeerIPs(Data $data, stdClass $peer): void
    {
        $this->connection
            ->table('peers_ip')
            ->where('peerID', '=', $peer->id)
            ->delete();

        $ips = [
            $data->getIpV4(),
            $data->getIpV6(),
        ];

        $dataToInsert = [];
        /** @var $ip Ip */
        foreach($ips as $ip){
            if (null === $ip) {
                continue;
            }

            if (empty($ip->getIp()) || empty($ip->getPort())){
                continue;
            }

            $dataToInsert[] = [
                'peerID' => $peer->id,
                'IP'     => $ip->getIp(),
                'port'   => $ip->getPort(),
                'isIPv6' => $ip->isV6(),
            ];
        }

        if(!empty($dataToInsert)){
            // reduce number of queries with one insert
            $this->connection
                ->table('peers_ip')
                ->insert($dataToInsert);
        }
    }

    private function startedEventAnnounceResponse(Data $data, stdClass $user, ?stdClass $peer, ?stdClass $torrent, ?stdClass $snatch, bool $isSeeding): string
    {
        if (null !== $peer) {
            $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding);
            $this->updateSnatchIfItExists($data, $snatch);
        } else {
            $peer = $this->insertPeer($data, $user, $torrent, $isSeeding);

            if (true === $isSeeding) {
                $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $this->adjustTorrentPeers($torrent, 0, 1);

                if (null !== $snatch) {
                    $this->updateSnatchIfItExists($data, $snatch);
                } else {
                    $this->insertSnatch($data, $user, $torrent);
                }
            }
        }

        $this->insertPeerIPs($data, $peer);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function stoppedEventAnnounceResponse(Data $data, stdClass $user, ?stdClass $peer, stdClass $torrent, ?stdClass $snatch, bool $isSeeding): string
    {
        if(null !== $peer) {
            $this->connection
                ->table('peers')
                ->where('id', '=', $peer->id)
                ->delete();
        }

        $this->cache->forget(sprintf('user.%s.peers', $user->id));

        if (true === $isSeeding) {
            $this->adjustTorrentPeers($torrent, -1, 0);
        } else {
            $this->adjustTorrentPeers($torrent, 0, -1);
        }

        $this->updateSnatchIfItExists($data, $snatch);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function completedEventAnnounceResponse(Data $data, stdClass $user, ?stdClass $peer, stdClass $torrent, ?stdClass $snatch, bool $isSeeding): string
    {
        $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding);
        $this->insertPeerIPs($data, $peer);
        $this->cache->forget(sprintf('user.%s.peers', $user->id));
        $this->adjustTorrentPeers($torrent, 1, -1);
        $this->updateSnatchIfItExists($data, $snatch);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function noEventAnnounceResponse(Data $data, stdClass $user, ?stdClass $peer, stdClass $torrent, ?stdClass $snatch, bool $isSeeding): string
    {
        if (null !== $peer) {
            $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding);
        } else {
            $peer = $this->insertPeer($data, $user, $torrent, $isSeeding);
            if (true === $isSeeding) {
                $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $this->adjustTorrentPeers($torrent, 0, 1);
            }
        }

        $this->insertPeerIPs($data, $peer);
        $this->updateSnatchIfItExists($data, $snatch);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    protected function getPeers(Data $data, stdClass $user, stdClass $torrent, bool $isSeeding): Generator
    {
        return $this->connection->table('peers')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peerID')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->when($isSeeding, function (Builder $query) {
                return $query->where('seeder', '!=', true);
            })
            ->where('user_id', '!=', $user->id)
            ->where('torrent_id', '=', $torrent->id)
            ->where('peers_version.version', '=', $torrent->version)
            ->limit($data->getNumberOfWantedPeers())
            ->inRandomOrder()
            ->select('peer_id', 'seeder', 'peers_ip.*')
            ->cursor();
    }

    private function announceSuccessResponse(Data $data, stdClass $user, stdClass $torrent, bool $isSeeding): string
    {
        $this->updateUser($data, $user);

        $response = $this->getCommonResponsePart($data, $torrent, $isSeeding);

        $peersIterator = $this->getPeers($data, $user, $torrent, $isSeeding);

        // return compact response if the client wantsgetCommonResponsePart a compact response or if the client did not
        // specify what kind of response it wants, else return non-compact response
        if ($data->isCompactResponse()) {
            $response = $this->compactResponse($peersIterator, $response);
        }else{
            $response = $this->nonCompactResponse($peersIterator, $response);
        }

        return $this->encoder->encode($response);
    }

    private function getSeedersAndLeechersCount(Data $data, stdClass $torrent, bool $isSeeding): array
    {
        $seedersCount = (int) $torrent->seeders;
        $leechersCount = (int) $torrent->leechers;
        // We don't want to include the current user/peer in the returned seeder/leecher count.
        if (AnnounceEvent::STOPPED !== $data->getEvent()) {
            if (true === $isSeeding) {
                $seedersCount--;
            } else {
                $leechersCount--;
            }
        }

        return [$seedersCount, $leechersCount];
    }

    private function getCommonResponsePart(Data $data, stdClass $torrent, bool $isSeeding): array
    {
        $response['interval'] = $this->config->get('tracker.announce_interval') * 60;
        $response['min interval'] = $this->config->get('tracker.min_announce_interval') * 60;

        $peersCount = $this->getSeedersAndLeechersCount($data, $torrent, $isSeeding);
        $response['complete'] = $peersCount[0];
        $response['incomplete'] = $peersCount[1];

        return $response;
    }

    private function compactResponse(Generator $peers, array $response): array
    {
        $response['peers'] = '';

        // BEP 7 -> IPv6 peers support -> http://www.bittorrent.org/beps/bep_0007.html
        $response['peers6'] = '';

        foreach ($peers as $peer) {
            $peerIPAddress = inet_pton($peer->IP);
            $peerPort = pack('n*', $peer->port);

            if (true === (bool) $peer->isIPv6) {
                $response['peers6'] .= $peerIPAddress . $peerPort;
            } else {
                $response['peers'] .= $peerIPAddress . $peerPort;
            }
        }

        return $response;
    }

    private function nonCompactResponse(Generator $peers, array $response): array
    {
        $response['peers'] = [];

        foreach ($peers as $peer) {
            // IPv6 peers are not separated for non-compact responses
            $response['peers'][] = [
                'peer id' => hex2bin($peer->peer_id),
                'ip'      => $peer->IP,
                'port'    => (int) $peer->port,
            ];
        }

        return $response;
    }

    /**
     * @param array|string $error
     */
    private function announceErrorResponse($error, bool $neverRetry = false): string
    {
        $response = [];
        if (is_array($error)) {
            $response['failure reason'] = implode(' ', $error);
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
