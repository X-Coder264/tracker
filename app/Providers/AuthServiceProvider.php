<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Torrent;
use App\Policies\TorrentPolicy;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        Torrent::class => TorrentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(Gate $gate): void
    {
        foreach ($this->policies as $class => $policy) {
            $gate->policy($class, $policy);
        }
    }
}
