<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\News;
use Illuminate\Contracts\Cache\Repository;

class NewsObserver
{
    private Repository $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    public function saved(News $news): void
    {
        $this->cache->forget('news');
    }
}
