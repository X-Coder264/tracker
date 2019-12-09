<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enumerations\Cache;
use App\Models\TorrentCategory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;

class TorrentCategoryRepository
{
    private Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function getAllCategories(): Collection
    {
        return $this->cache->remember('torrentCategories', Cache::ONE_DAY, function () {
            return TorrentCategory::all();
        });
    }
}
