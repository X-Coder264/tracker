<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enumerations\Cache;
use App\Models\Peer;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Presenters\Announce\User as AnnounceUserModel;
use App\Services\Announce\Contracts\UserRepositoryInterface as AnnounceUserRepositoryInterface;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\ConnectionInterface;

final class UserRepository implements AnnounceUserRepositoryInterface
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

    public function getTotalSeedingSize(int $userId): int
    {
        return (int) $this->connection->table('torrents')
            ->join('peers', 'torrents.id', '=', 'peers.torrent_id')
            ->where('peers.user_id', '=', $userId)
            ->where('peers.left', '=', 0)
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

    public function getUserFromPasskey(string $passkey): ?AnnounceUserModel
    {
        return $this->cache->remember('user.' . $passkey, Cache::ONE_DAY, function () use ($passkey) {
            $user = $this->connection->table('users')
                ->where('passkey', '=', $passkey)
                ->select(['id', 'slug', 'uploaded', 'downloaded', 'banned'])
                ->first();

            if (null === $user) {
                return null;
            }

            return new AnnounceUserModel($user->id, $user->slug, $user->uploaded, $user->downloaded, (bool) $user->banned);
        });
    }

    public function updateUserUploadedAndDownloadedStats(string $passkey, AnnounceUserModel $user): void
    {
        $this->connection->table('users')
            ->where('id', '=', $user->getId())
            ->update(
                [
                    'uploaded'   => $user->getUploaded(),
                    'downloaded' => $user->getDownloaded(),
                ]
            );

        $this->cache->put('user.' . $passkey, $user, Cache::ONE_DAY);
    }
}
