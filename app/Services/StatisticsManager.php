<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use App\Enumerations\Cache;
use Illuminate\Contracts\Cache\Repository;

class StatisticsManager
{
    /**
     * @var Repository
     */
    private $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function getPeersCount(): int
    {
        return (int) $this->cache->remember('peers_count', Cache::ONE_HOUR, function () {
            return Peer::count();
        });
    }

    public function getSeedersCount(): int
    {
        return (int) $this->cache->remember('seeders_count', Cache::ONE_HOUR, function () {
            return Peer::seeders()->count();
        });
    }

    public function getLeechersCount(): int
    {
        return (int) $this->cache->remember('leechers_count', Cache::ONE_HOUR, function () {
            return Peer::leechers()->count();
        });
    }

    public function getUsersCount(): int
    {
        return (int) $this->cache->remember('users_count', Cache::ONE_HOUR, function () {
            return User::count();
        });
    }

    public function getBannedUsersCount(): int
    {
        return (int) $this->cache->remember('banned_users_count', Cache::ONE_HOUR, function () {
            return User::banned()->count();
        });
    }

    public function getTorrentsCount(): int
    {
        return (int) $this->cache->remember('torrents_count', Cache::ONE_HOUR, function () {
            return Torrent::count();
        });
    }

    public function getDeadTorrentsCount(): int
    {
        return (int) $this->cache->remember('dead_torrents_count', Cache::ONE_HOUR, function () {
            return Torrent::dead()->count();
        });
    }
}
