<?php

declare(strict_types=1);

namespace App\Repositories\User;

use App\Models\Peer;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Presenters\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final class UserRepository implements UserRepositoryInterface
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
        return (int)$this->connection->table('torrents')
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

    public function getUserByPassKey(string $passkey): ?User
    {
        $user = $this->connection->table('users')
            ->where('passkey', '=', $passkey)
            ->select(['id', 'slug', 'uploaded', 'downloaded', 'banned'])
            ->first();

        if (empty($user)) {
            return null;
        }

        return new User(
            (int)$user->id,
            $user->slug,
            (int)$user->uploaded,
            (int)$user->downloaded,
            (bool)$user->banned,
            $passkey
        );
    }

    public function updateUserStatistics(User $user): void
    {
        $this->connection->table('users')
            ->where('id', '=', $user->getId())
            ->update(
                [
                    'uploaded' => $user->getUpdated(),
                    'downloaded' => $user->getDownloaded(),
                ]
            );
    }
}
