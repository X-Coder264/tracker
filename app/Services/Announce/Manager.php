<?php

declare(strict_types=1);

namespace App\Services\Announce;

use App\Presenters\User;
use App\Repositories\User\UserRepositoryInterface;
use stdClass;
use Generator;
use Carbon\Carbon;
use App\Presenters\Ip;
use Carbon\CarbonImmutable;
use App\Presenters\Announce\Data;
use App\Enumerations\AnnounceEvent;
use App\Presenters\Announce\Response;
use Illuminate\Database\Query\Builder;
use App\Exceptions\ValidationException;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

/**
 * Note: For performance reasons the query builder is used instead of Eloquent.
 */
class Manager
{
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
     * @var array
     */
    private $statistics;

    /**
     * @var DataFactory
     */
    private $dataFactory;
    /**
     * @var UserRepositoryInterface
     */
    private $userRepository;

    public function __construct(
        ConnectionInterface $connection,
        CacheRepository $cache,
        Translator $translator,
        DataFactory $dataFactory,
        UserRepositoryInterface $userRepository
    ) {
        $this->connection = $connection;
        $this->cache = $cache;
        $this->translator = $translator;
        $this->dataFactory = $dataFactory;
        $this->userRepository = $userRepository;
    }

    /**
     * @throws ValidationException
     */
    public function announce(Data $data, User $user, CarbonImmutable $timeNow): Response
    {
        $torrent = $this
            ->connection
            ->table('torrents')
            ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
            ->where('info_hash', '=', bin2hex($data->getInfoHash()))
            ->select(['torrents.id', 'seeders', 'leechers', 'slug', 'version'])
            ->first();

        if (null === $torrent) {
            throw ValidationException::single($this->translator->trans('messages.announce.invalid_info_hash'));
        }

        $isSeeding = 0 === $data->getLeft();

        $peer = $this
            ->connection
            ->table('peers')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->where('peer_id', '=', bin2hex($data->getPeerId()))
            ->where('torrent_id', '=', $torrent->id)
            ->where('user_id', '=', $user->getId())
            ->select('peers.*', 'peers_version.version')
            ->first();

        if (null === $peer && (AnnounceEvent::COMPLETED === $data->getEvent() || AnnounceEvent::STOPPED === $data->getEvent())) {
            throw ValidationException::single($this->translator->trans('messages.announce.invalid_peer_id'));
        }

        $downloaded = $data->getDownloaded();
        $uploaded = $data->getUploaded();

        $statistic = [];

        if (null === $peer) {
            $statistic['downloadedInThisAnnounceCycle'] = $downloaded;
            $statistic['uploadedInThisAnnounceCycle'] = $uploaded;
        } else {
            $statistic['downloadedInThisAnnounceCycle'] = max(0, $downloaded - $peer->downloaded);
            $statistic['uploadedInThisAnnounceCycle'] = max(0, $uploaded - $peer->uploaded);
            if (false === $isSeeding || (true === $isSeeding && AnnounceEvent::COMPLETED === $data->getEvent())) {
                $statistic['leechTime'] = $timeNow->diffInSeconds(new Carbon($peer->updated_at));
            } else {
                $statistic['seedTime'] = $timeNow->diffInSeconds(new Carbon($peer->updated_at));
            }
        }

        $this->statistics = $statistic;

        $snatch = $this
            ->connection
            ->table('snatches')
            ->where('torrent_id', '=', $torrent->id)
            ->where('user_id', '=', $user->getId())
            ->first();

        $peers = null;
        switch ($data->getEvent()) {
            case AnnounceEvent::STARTED:
                $peers = $this->startedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding, $timeNow);

                break;
            case AnnounceEvent::STOPPED:
                $peers = $this->stoppedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding, $timeNow);

                break;
            case AnnounceEvent::COMPLETED:
                if (0 === $data->getLeft()) {
                    $peers = $this->completedEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding, $timeNow);
                }

                break;
        }

        if (null === $peers) {
            $peers = $this->noEventAnnounceResponse($data, $user, $peer, $torrent, $snatch, $isSeeding, $timeNow);
        }

        $count = $this->getSeedersAndLeechersCount($data, $torrent, $isSeeding);

        return new Response($peers, $count['seeders'], $count['leechers']);
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
    private function insertPeer(Data $data, User $user, stdClass $torrent, bool $isSeeding, CarbonImmutable $now): stdClass
    {
        $peer = new stdClass();
        $peer->id = $this->connection
            ->table('peers')
            ->insertGetId(
                [
                'peer_id'    => bin2hex($data->getPeerId()),
                'torrent_id' => $torrent->id,
                'user_id'    => $user->getId(),
                'uploaded'   => $this->statistics['uploadedInThisAnnounceCycle'],
                'downloaded' => $this->statistics['downloadedInThisAnnounceCycle'],
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

        $this->cache->forget(sprintf('user.%s.peers', $user->getId()));

        return $peer;
    }

    /**
     * Update the peer if it already exists in the DB.
     */
    private function updatePeerIfItExists(Data $data, ?stdClass $peer, stdClass $torrent, bool $isSeeding, CarbonImmutable $now): void
    {
        if (null === $peer) {
            return;
        }

        $this->connection
            ->table('peers')
            ->where('id', '=', $peer->id)
            ->update(
                [
                    'uploaded'   => $peer->uploaded + $this->statistics['uploadedInThisAnnounceCycle'],
                    'downloaded' => $peer->downloaded + $this->statistics['downloadedInThisAnnounceCycle'],
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
    private function insertSnatch(Data $data, User $user, stdClass $torrent, CarbonImmutable $now): void
    {
        $snatch = new stdClass();
        $snatch->id = $this->connection
            ->table('snatches')
            ->insertGetId(
                [
                'torrent_id'     => $torrent->id,
                'user_id'        => $user->getId(),
                'uploaded'       => $this->statistics['uploadedInThisAnnounceCycle'],
                'downloaded'     => $this->statistics['downloadedInThisAnnounceCycle'],
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
    private function updateSnatchIfItExists(Data $data, ?stdClass $snatch, CarbonImmutable $now): void
    {
        if (null === $snatch) {
            return;
        }

        $finishedAt = $snatch->finished_at;
        if (0 === $data->getLeft() && null === $snatch->finished_at) {
            $finishedAt = $now;
        }

        $this->connection
            ->table('snatches')
            ->where('id', '=', $snatch->id)
            ->update(
                [
                    'uploaded'       => $snatch->uploaded + $this->statistics['uploadedInThisAnnounceCycle'],
                    'downloaded'     => $snatch->downloaded + $this->statistics['downloadedInThisAnnounceCycle'],
                    'left'           => $data->getLeft(),
                    'seedTime'       => $snatch->seedTime + $this->statistics['seedTime'],
                    'leechTime'      => $snatch->leechTime + $this->statistics['leechTime'],
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
    private function updateUser(User $user): void
    {
        $user->setUpdated($user->getUpdated() + $this->statistics['uploadedInThisAnnounceCycle']);
        $user->setDownloaded($user->getDownloaded() + $this->statistics['downloadedInThisAnnounceCycle']);

        $this->userRepository->updateUserStatistics($user);
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
        foreach ($ips as $ip) {
            if (null === $ip) {
                continue;
            }

            if (empty($ip->getIp()) || empty($ip->getPort())) {
                continue;
            }

            $dataToInsert[] = [
                'peerID' => $peer->id,
                'IP'     => $ip->getIp(),
                'port'   => $ip->getPort(),
                'isIPv6' => $ip->isV6(),
            ];
        }

        if (!empty($dataToInsert)) {
            // reduce number of queries with one insert
            $this->connection
                ->table('peers_ip')
                ->insert($dataToInsert);
        }
    }

    private function startedEventAnnounceResponse(
        Data $data,
        User $user,
        ?stdClass $peer,
        ?stdClass $torrent,
        ?stdClass $snatch,
        bool $isSeeding,
        CarbonImmutable $timeNow
    ): Generator
    {
        if (null !== $peer) {
            $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding, $timeNow);
            $this->updateSnatchIfItExists($data, $snatch, $timeNow);
        } else {
            $peer = $this->insertPeer($data, $user, $torrent, $isSeeding, $timeNow);

            if (true === $isSeeding) {
                $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $this->adjustTorrentPeers($torrent, 0, 1);

                if (null !== $snatch) {
                    $this->updateSnatchIfItExists($data, $snatch, $timeNow);
                } else {
                    $this->insertSnatch($data, $user, $torrent, $timeNow);
                }
            }
        }

        $this->insertPeerIPs($data, $peer);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function stoppedEventAnnounceResponse(
        Data $data,
        User $user,
        ?stdClass $peer,
        stdClass $torrent,
        ?stdClass $snatch,
        bool $isSeeding,
        CarbonImmutable $timeNow
    ): Generator
    {
        if (null !== $peer) {
            $this->connection
                ->table('peers')
                ->where('id', '=', $peer->id)
                ->delete();
        }

        $this->cache->forget(sprintf('user.%s.peers', $user->getId()));

        if (true === $isSeeding) {
            $this->adjustTorrentPeers($torrent, -1, 0);
        } else {
            $this->adjustTorrentPeers($torrent, 0, -1);
        }

        $this->updateSnatchIfItExists($data, $snatch, $timeNow);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function completedEventAnnounceResponse(
        Data $data,
        User $user,
        ?stdClass $peer,
        stdClass $torrent,
        ?stdClass $snatch,
        bool $isSeeding,
        CarbonImmutable $timeNow
    ): Generator
    {
        $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding, $timeNow);
        $this->insertPeerIPs($data, $peer);
        $this->cache->forget(sprintf('user.%s.peers', $user->getId()));
        $this->adjustTorrentPeers($torrent, 1, -1);
        $this->updateSnatchIfItExists($data, $snatch, $timeNow);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    private function noEventAnnounceResponse(
        Data $data,
        User $user,
        ?stdClass $peer,
        stdClass $torrent,
        ?stdClass $snatch,
        bool $isSeeding,
        CarbonImmutable $timeNow
    ): Generator
    {
        if (null !== $peer) {
            $this->updatePeerIfItExists($data, $peer, $torrent, $isSeeding, $timeNow);
        } else {
            $peer = $this->insertPeer($data, $user, $torrent, $isSeeding, $timeNow);
            if (true === $isSeeding) {
                $this->adjustTorrentPeers($torrent, 1, 0);
            } else {
                $this->adjustTorrentPeers($torrent, 0, 1);
            }
        }

        $this->insertPeerIPs($data, $peer);
        $this->updateSnatchIfItExists($data, $snatch, $timeNow);

        return $this->announceSuccessResponse($data, $user, $torrent, $isSeeding);
    }

    protected function getPeers(Data $data, User $user, stdClass $torrent, bool $isSeeding): Generator
    {
        return $this->connection->table('peers')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peerID')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peerID')
            ->when($isSeeding, function (Builder $query) {
                return $query->where('seeder', '!=', true);
            })
            ->where('user_id', '!=', $user->getId())
            ->where('torrent_id', '=', $torrent->id)
            ->where('peers_version.version', '=', $torrent->version)
            ->limit($data->getNumberOfWantedPeers())
            ->inRandomOrder()
            ->select('peer_id', 'seeder', 'peers_ip.*')
            ->cursor();
    }

    private function announceSuccessResponse(Data $data, User $user, stdClass $torrent, bool $isSeeding): Generator
    {
        $this->updateUser($user);

        return $this->getPeers($data, $user, $torrent, $isSeeding);
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

        return [
            'seeders' => $seedersCount,
            'leechers' => $leechersCount,
        ];
    }
}
