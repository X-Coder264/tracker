<?php

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\PeerRepository;
use App\Repositories\SnatchRepository;
use App\Repositories\TorrentRepository;
use App\Repositories\UserRepository;
use App\Services\Announce\Contracts\PeerRepositoryInterface;
use App\Services\Announce\Contracts\SnatchRepositoryInterface;
use App\Services\Announce\Contracts\TorrentRepositoryInterface;
use App\Services\Announce\Contracts\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;

final class AnnounceServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(TorrentRepositoryInterface::class, TorrentRepository::class);
        $this->app->bind(PeerRepositoryInterface::class, PeerRepository::class);
        $this->app->bind(SnatchRepositoryInterface::class, SnatchRepository::class);
    }
}
