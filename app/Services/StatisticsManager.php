<?php

declare(strict_types=1);

namespace App\Services;

use App\Enumerations\Cache;
use App\Models\Peer;
use App\Models\Torrent;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository;

class StatisticsManager
{
    private Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function getPeersCount(): int
    {
        return (int) $this->cache->remember('peers_count', Cache::ONE_HOUR, function (): int {
            return Peer::count();
        });
    }

    public function getSeedersCount(): int
    {
        return (int) $this->cache->remember('seeders_count', Cache::ONE_HOUR, function (): int {
            return Peer::seeders()->count();
        });
    }

    public function getLeechersCount(): int
    {
        return (int) $this->cache->remember('leechers_count', Cache::ONE_HOUR, function (): int {
            return Peer::leechers()->count();
        });
    }

    public function getUsersCount(): int
    {
        return (int) $this->cache->remember('users_count', Cache::ONE_HOUR, function (): int {
            return User::count();
        });
    }

    public function getBannedUsersCount(): int
    {
        return (int) $this->cache->remember('banned_users_count', Cache::ONE_HOUR, function (): int {
            return User::banned()->count();
        });
    }

    public function getTorrentsCount(): int
    {
        return (int) $this->cache->remember('torrents_count', Cache::ONE_HOUR, function (): int {
            return Torrent::count();
        });
    }

    public function getDeadTorrentsCount(): int
    {
        return (int) $this->cache->remember('dead_torrents_count', Cache::ONE_HOUR, function (): int {
            return Torrent::dead()->count();
        });
    }

    public function getTotalTorrentSize(): int
    {
        return (int) $this->cache->remember('torrents_size', Cache::ONE_HOUR, function (): int {
            return (int) Torrent::sum('size');
        });
    }
}
