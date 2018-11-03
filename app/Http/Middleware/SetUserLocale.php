<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Application;

class SetUserLocale
{
    /**
     * @var AuthManager
     */
    private $authManager;

    /**
     * @var CacheManager
     */
    private $cacheManager;

    /**
     * @var Application
     */
    private $application;

    public function __construct(AuthManager $authManager, CacheManager $cacheManager, Application $application)
    {
        $this->authManager = $authManager;
        $this->cacheManager = $cacheManager;
        $this->application = $application;
    }

    public function handle(Request $request, $next)
    {
        if (true === $this->authManager->check()) {
            /** @var User $user */
            $user = $this->authManager->user();
            $locale = $this->cacheManager->rememberForever('user.' . $user->slug . '.locale', function () use ($user): string {
                return $user->language->localeShort;
            });
            $this->application->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
