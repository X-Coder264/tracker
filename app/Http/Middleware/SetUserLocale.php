<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class SetUserLocale
{
    private Guard $guard;
    private Repository $cache;
    private Application $application;

    public function __construct(Guard $guard, Repository $cache, Application $application)
    {
        $this->guard = $guard;
        $this->cache = $cache;
        $this->application = $application;
    }

    public function handle(Request $request, $next)
    {
        if (true === $this->guard->check()) {
            /** @var User $user */
            $user = $this->guard->user();
            $locale = $this->cache->rememberForever('user.' . $user->slug . '.locale', function () use ($user): string {
                return $user->language->localeShort;
            });
            $this->application->setLocale($locale);
            Carbon::setLocale($locale);
            CarbonImmutable::setLocale($locale);
        }

        return $next($request);
    }
}
