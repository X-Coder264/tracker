<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\TorrentCategory;
use Illuminate\Contracts\Cache\Repository;

class TorrentCategoryObserver
{
    private Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function saved(TorrentCategory $torrentCategory): void
    {
        $this->cache->forget('torrentCategories');
    }
}
