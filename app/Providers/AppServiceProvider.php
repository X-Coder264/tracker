<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Controllers\Torrents\DownloadSeedingTorrentsZipArchiveController;
use App\Http\Controllers\Torrents\DownloadSnatchedTorrentsZipArchiveController;
use App\Models\News;
use App\Models\TorrentCategory;
use App\Models\User;
use App\Observers\NewsObserver;
use App\Observers\TorrentCategoryObserver;
use App\Observers\UserObserver;
use App\Repositories\PrivateMessages\CachedThreadParticipantRepository;
use App\Repositories\PrivateMessages\ThreadParticipantRepository;
use App\Repositories\PrivateMessages\ThreadParticipantRepositoryInterface;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\DateFactory;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        DateFactory::useClass(CarbonImmutable::class);

        User::observe(UserObserver::class);
        TorrentCategory::observe(TorrentCategoryObserver::class);
        News::observe(NewsObserver::class);

        $this->app->bind(HttpClientInterface::class, function () {
            return HttpClient::create();
        });

        $this->app->singleton(ThreadParticipantRepositoryInterface::class, function (Container $container) {
            return new CachedThreadParticipantRepository(
                $container->make(Repository::class),
                $container->make(ThreadParticipantRepository::class)
            );
        });

        $this->app->when(
            [
                DownloadSnatchedTorrentsZipArchiveController::class,
                DownloadSeedingTorrentsZipArchiveController::class,
            ]
        )
            ->needs('$storagePath')
            ->give(function (): string {
                return $this->app->make('path.storage');
            });
    }
}
