<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Models\TorrentCategory;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;
use App\Observers\TorrentCategoryObserver;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use App\Repositories\PrivateMessages\ThreadParticipantRepository;
use App\Repositories\PrivateMessages\CachedThreadParticipantRepository;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(UserObserver::class);
        TorrentCategory::observe(TorrentCategoryObserver::class);

        $this->app->singleton(ThreadParticipantRepositoryInterface::class, function (Container $container) {
            return new CachedThreadParticipantRepository(
                $container->make(Repository::class),
                $container->make(ThreadParticipantRepository::class)
            );
        });
    }
}
