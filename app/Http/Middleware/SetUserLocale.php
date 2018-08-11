<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
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

    /**
     * @param AuthManager  $authManager
     * @param CacheManager $cacheManager
     * @param Application  $application
     */
    public function __construct(AuthManager $authManager, CacheManager $cacheManager, Application $application)
    {
        $this->authManager = $authManager;
        $this->cacheManager = $cacheManager;
        $this->application = $application;
    }

    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure $next
     *
     * @return mixed
     */
    public function handle($request, $next)
    {
        if (true === $this->authManager->check()) {
            /** @var User $user */
            $user = $this->authManager->user();
            $locale = $this->cacheManager->rememberForever('user.' . $user->slug . '.locale', function () use ($user) {
                return $user->language->localeShort;
            });
            $this->application->setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
