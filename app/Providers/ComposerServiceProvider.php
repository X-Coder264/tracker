<?php

declare(strict_types=1);

namespace App\Providers;

use App\Http\Models\User;
use Illuminate\View\View;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\View\Factory as ViewFactory;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $viewsThatNeedTimezoneInfo = ['torrents.index', 'torrents.show'];

    /**
     * @param ViewFactory  $viewFactory
     * @param AuthManager  $authManager
     * @param CacheManager $cacheManager
     */
    public function boot(ViewFactory $viewFactory, AuthManager $authManager, CacheManager $cacheManager)
    {
        $viewFactory->composer($this->viewsThatNeedTimezoneInfo, function (View $view) use ($authManager, $cacheManager) {
            $user = $cacheManager->remember('user.' . $authManager->guard()->id(), 24 * 60, function () use ($authManager) {
                return User::with('language')->find($authManager->guard()->id());
            });

            $view->with('timezone', $user->timezone);
        });
    }
}
