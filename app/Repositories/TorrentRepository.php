<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Torrent;
use App\Presenters\Announce\Torrent as AnnounceTorrentModel;
use App\Services\Announce\Contracts\TorrentRepositoryInterface as AnnounceTorrentRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Collection;

final class TorrentRepository implements AnnounceTorrentRepositoryInterface
{
    private ConnectionInterface $connection;
    private Repository $cache;

    public function __construct(
        ConnectionInterface $connection,
        Repository $cache
    ) {
        $this->connection = $connection;
        $this->cache = $cache;
    }

    public function getNewestAliveTorrentsInCategories(array $categoryIds = [], int $limit = 20): Collection
    {
        $queryBuilder = Torrent::with('infoHashes')
            ->where('seeders', '>', 0)
            ->orderByDesc('id')
            ->limit($limit);

        if (empty($categoryIds)) {
            return $queryBuilder->get();
        }

        return $queryBuilder
            ->whereIn('category_id', $categoryIds)
            ->get();
    }

    public function getTorrentByInfoHash(string $infoHash): ?AnnounceTorrentModel
    {
        $torrent = $this->connection->table('torrents')
            ->join('torrent_info_hashes', 'torrents.id', '=', 'torrent_info_hashes.torrent_id')
            ->where('info_hash', '=', $infoHash)
            ->select(['torrents.id', 'seeders', 'leechers', 'slug', 'version'])
            ->first();

        if (null === $torrent) {
            return null;
        }

        return new AnnounceTorrentModel($torrent->id, $torrent->seeders, $torrent->leechers, $torrent->slug, $torrent->version);
    }

    public function updateTorrentSeederAndLeechersCount(AnnounceTorrentModel $torrent): void
    {
        $this->connection->table('torrents')->where('id', '=', $torrent->getId())
            ->update(
                [
                    'seeders'  => $torrent->getSeedersCount(),
                    'leechers' => $torrent->getLeechersCount(),
                ]
            );

        $this->cache->forget('torrent.' . $torrent->getId());
    }
}
