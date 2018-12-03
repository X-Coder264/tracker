<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Peer;
use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository
{
    /**
     * @var ConnectionInterface
     */
    private $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function getTotalSeedingSize(int $userId): int
    {
        return (int) $this->connection->table('torrents')
            ->join('peers', 'torrents.id', '=', 'peers.torrent_id')
            ->where('peers.user_id', '=', $userId)
            ->where('peers.seeder', '=', true)
            ->sum('size');
    }

    public function getUploadedTorrents(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Torrent::where('uploader_id', '=', $userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getUploadedTorrentsCount(int $userId): int
    {
        return Torrent::where('uploader_id', '=', $userId)->count();
    }

    public function getSeedingTorrentPeers(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Peer::with('torrent')
            ->where('user_id', '=', $userId)
            ->seeders()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getSeedingTorrentPeersCount(int $userId): int
    {
        return Peer::with('torrent')
            ->where('user_id', '=', $userId)
            ->seeders()
            ->count();
    }

    public function getLeechingTorrentPeers(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Peer::with('torrent')
            ->where('user_id', '=', $userId)
            ->leechers()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getLeechingTorrentPeersCount(int $userId): int
    {
        return Peer::with('torrent')
            ->where('user_id', '=', $userId)
            ->leechers()
            ->count();
    }

    public function getUserSnatches(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return Snatch::with('torrent')
            ->where('user_id', '=', $userId)
            ->where('left', '=', 0)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function getUserSnatchesCount(int $userId): int
    {
        return Snatch::where('user_id', '=', $userId)
            ->where('left', '=', 0)
            ->count();
    }
}
