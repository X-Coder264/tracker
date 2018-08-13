<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use App\Enumerations\Cache;
use Illuminate\Cache\CacheManager;

class StatisticsManager
{
    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @param CacheManager $cacheManager
     */
    public function __construct(CacheManager $cacheManager)
    {
        $this->cacheManager = $cacheManager;
    }

    /**
     * @return int
     */
    public function getPeersCount(): int
    {
        return (int) $this->cacheManager->remember('peers_count', Cache::ONE_HOUR, function () {
            return Peer::count();
        });
    }

    /**
     * @return int
     */
    public function getSeedersCount(): int
    {
        return (int) $this->cacheManager->remember('seeders_count', Cache::ONE_HOUR, function () {
            return Peer::seeders()->count();
        });
    }

    /**
     * @return int
     */
    public function getLeechersCount(): int
    {
        return (int) $this->cacheManager->remember('leechers_count', Cache::ONE_HOUR, function () {
            return Peer::leechers()->count();
        });
    }

    /**
     * @return int
     */
    public function getUsersCount(): int
    {
        return (int) $this->cacheManager->remember('users_count', Cache::ONE_HOUR, function () {
            return User::count();
        });
    }

    /**
     * @return int
     */
    public function getBannedUsersCount(): int
    {
        return (int) $this->cacheManager->remember('banned_users_count', Cache::ONE_HOUR, function () {
            return User::banned()->count();
        });
    }

    /**
     * @return int
     */
    public function getTorrentsCount(): int
    {
        return (int) $this->cacheManager->remember('torrents_count', Cache::ONE_HOUR, function () {
            return Torrent::count();
        });
    }

    /**
     * @return int
     */
    public function getDeadTorrentsCount(): int
    {
        return (int) $this->cacheManager->remember('dead_torrents_count', Cache::ONE_HOUR, function () {
            return Torrent::dead()->count();
        });
    }
}
