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
     *
     * @return void
     */
    public function boot()
    {
        view()->composer($this->viewsThatNeedTimezoneInfo, function (View $view) {
            if (Cache::has('user.' . Auth::id())) {
                $user = Cache::get('user.' . Auth::id());
            } else {
                $user = User::with('language')->find(Auth::id());
                Cache::forever('user.' . Auth::id(), $user);
            }
            $view->with('timezone', $user->timezone);
        });
    }
}
