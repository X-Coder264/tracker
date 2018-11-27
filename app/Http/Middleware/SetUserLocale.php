<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Cache\Repository;

class SetUserLocale
{
    /**
     * @var Guard
     */
    private $guard;

    /**
     * @var Repository
     */
    private $cache;

    /**
     * @var Application
     */
    private $application;

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
        }

        return $next($request);
    }
}
