<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SetUserLocale
{
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
        if (true === Auth::check()) {
            $locale = Cache::rememberForever('user.' . Auth::user()->slug . '.locale', function () {
                return Auth::user()->language->localeShort;
            });
            App::setLocale($locale);
            Carbon::setLocale($locale);
        }

        return $next($request);
    }
}
