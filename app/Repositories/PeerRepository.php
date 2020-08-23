<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Presenters\Announce\AnnounceRequest;
use App\Presenters\Announce\Peer as AnnouncePeerModel;
use App\Presenters\Announce\Torrent as AnnounceTorrentModel;
use App\Presenters\Announce\User as AnnounceUserModel;
use App\Services\Announce\Contracts\PeerRepositoryInterface as AnnouncePeerRepositoryInterface;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Query\Builder;
use stdClass;

final class PeerRepository implements AnnouncePeerRepositoryInterface
{
    private ConnectionInterface $connection;
    private Repository $config;
    private CacheRepository $cache;

    public function __construct(
        ConnectionInterface $connection,
        Repository $config,
        CacheRepository $cache
    ) {
        $this->connection = $connection;
        $this->config = $config;
        $this->cache = $cache;
    }

    public function getObsoletePeersQuery(): Builder
    {
        return $this->connection
            ->table('peers')
            ->where('updated_at', '<', Carbon::now()->subMinutes($this->config->get('tracker.announce_interval') + 10))
            ->select(['id', 'left', 'torrent_id', 'user_id']);
    }

    public function insertPeer(AnnouncePeerModel $peer, AnnounceTorrentModel $torrent, AnnounceUserModel $user, AnnounceRequest $request): AnnouncePeerModel
    {
        $peerId = $this->connection->table('peers')->insertGetId(
            [
                'peer_id'    => $peer->getPeerId(),
                'torrent_id' => $torrent->getId(),
                'user_id'    => $user->getId(),
                'uploaded'   => $peer->getUploaded(),
                'downloaded' => $peer->getDownloaded(),
                'left'       => $peer->getLeft(),
                'user_agent' => $peer->getUserAgent(),
                'key'        => $peer->getKey(),
                'created_at' => $peer->getCreatedAt(),
                'updated_at' => $peer->getUpdatedAt(),
            ]
        );

        $peer = $peer->withId($peerId);

        $this->connection->table('peers_version')->insert(
            [
                'peer_id'    => $peer->getId(),
                'version'    => $torrent->getVersion(),
                'created_at' => $peer->getCreatedAt(),
                'updated_at' => $peer->getUpdatedAt(),
            ]
        );

        $this->connection->table('peers_ip')->insert(
            [
                'peer_id'    => $peer->getId(),
                'ip'         => $peer->getIpAddress(),
                'port'       => $peer->getPort(),
                'is_ipv6'    => $peer->isIPv6(),
                'created_at' => $peer->getCreatedAt(),
                'updated_at' => $peer->getUpdatedAt(),
            ]
        );

        $this->cache->forget('user.' . $user->getId() . '.peers');

        return $peer;
    }

    public function updatePeer(AnnouncePeerModel $peer, AnnounceRequest $request): void
    {
        $this->connection->table('peers')
            ->where('id', '=', $peer->getId())
            ->update(
                [
                    'uploaded'   => $peer->getUploaded(),
                    'downloaded' => $peer->getDownloaded(),
                    'left'       => $peer->getLeft(),
                    'user_agent' => $peer->getUserAgent(),
                    'key'        => $peer->getKey(),
                    'updated_at' => $peer->getUpdatedAt(),
                ]
            );

        $this->connection->table('peers_version')
            ->where('peer_id', '=', $peer->getId())
            ->where('version', '=', $peer->getVersion())
            ->update(
                [
                    'updated_at' => $peer->getUpdatedAt(),
                ]
            );

        $this->connection->table('peers_ip')
            ->where('peer_id', '=', $peer->getId())
            ->where('is_ipv6', '=', $peer->isIPv6())
            ->update(
                [
                    'ip'         => $peer->getIpAddress(),
                    'updated_at' => $peer->getUpdatedAt(),
                ]
            );
    }

    public function findPeerByPeerIdAndKey(string $peerId, ?string $key, AnnounceTorrentModel $torrent, AnnounceUserModel $user): ?AnnouncePeerModel
    {
        $peer = $this->connection->table('peers')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peer_id')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peer_id')
            ->where('peers.peer_id', '=', $peerId)
            ->when(null !== $key, function (Builder $query) use ($key) {
                $query->where('peers.key', '=', $key);
            })
            ->when(null === $key, function (Builder $query) {
                $query->whereNull('peers.key');
            })
            ->where('torrent_id', '=', $torrent->getId())
            ->where('peers_version.version', '=', $torrent->getVersion())
            ->where('user_id', '=', $user->getId())
            ->select('peers.*', 'peers_version.version', 'peers_ip.ip', 'peers_ip.is_ipv6', 'peers_ip.port')
            ->lockForUpdate()
            ->first();

        if (null === $peer) {
            return null;
        }

        return new AnnouncePeerModel(
            $peer->id,
            $peer->uploaded,
            $peer->downloaded,
            $peer->left,
            $peer->peer_id,
            $peer->ip,
            (bool) $peer->is_ipv6,
            $peer->port,
            $torrent->getVersion(),
            $peer->user_agent,
            $peer->key,
            new CarbonImmutable($peer->created_at),
            new CarbonImmutable($peer->updated_at)
        );
    }

    public function getPeersForTorrent(
        AnnounceTorrentModel $torrent,
        AnnounceUserModel $user,
        bool $includeSeeders,
        int $numberOfWantedPeers
    ): iterable {
        $peers = $this->connection->table('peers')
            ->join('peers_version', 'peers.id', '=', 'peers_version.peer_id')
            ->join('peers_ip', 'peers.id', '=', 'peers_ip.peer_id')
            ->where('user_id', '!=', $user->getId())
            ->where('torrent_id', '=', $torrent->getId())
            ->where('peers_version.version', '=', $torrent->getVersion())
            ->when(false === $includeSeeders, function (Builder $query) {
                return $query->where('left', '!=', 0);
            })
            ->limit($numberOfWantedPeers)
            ->inRandomOrder()
            ->select('peers.*', 'peers_version.version', 'peers_ip.ip', 'peers_ip.is_ipv6', 'peers_ip.port')
            ->cursor();

        return $peers->map(function (stdClass $peer) {
            return new AnnouncePeerModel(
                $peer->id,
                $peer->uploaded,
                $peer->downloaded,
                $peer->left,
                $peer->peer_id,
                $peer->ip,
                (bool) $peer->is_ipv6,
                $peer->port,
                $peer->version,
                $peer->user_agent,
                $peer->key,
                new CarbonImmutable($peer->created_at),
                new CarbonImmutable($peer->updated_at)
            );
        });
    }
}
