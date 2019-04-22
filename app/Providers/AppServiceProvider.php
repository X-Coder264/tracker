<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Observers\UserObserver;
use App\Repositories\User\CachedUserRepository;
use App\Repositories\User\UserRepository;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use App\Repositories\PrivateMessages\ThreadParticipantRepository;
use App\Repositories\PrivateMessages\CachedThreadParticipantRepository;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;
use Illuminate\Contracts\Cache\Repository as CacheRepository;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        User::observe(UserObserver::class);

        $this->app->singleton(ThreadParticipantRepositoryInterface::class, function (Container $container) {
            return new CachedThreadParticipantRepository(
                $container->make(Repository::class),
                $container->make(ThreadParticipantRepository::class)
            );
        });

        $this->app->singleton(UserRepositoryInterface::class, function (Container $container) {
            return new CachedUserRepository(
                $container->make(UserRepository::class),
                $container->make(CacheRepository::class)
            );
        });
    }
}
