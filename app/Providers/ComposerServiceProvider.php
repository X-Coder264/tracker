<?php

namespace App\Providers;

use App\Http\Models\User;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * @var array
     */
    private $viewsThatNeedTimezoneInfo = ['torrents.index', 'torrents.show'];

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        view()->composer($this->viewsThatNeedTimezoneInfo, function (View $view) {
            $user = Cache::remember('user.' . Auth::id(), 24 * 60, function () {
                return User::with('language')->find(Auth::id());
            });
            $view->with('timezone', $user->timezone);
        });
    }
}
