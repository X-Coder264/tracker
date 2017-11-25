<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
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
    public function handle($request, Closure $next)
    {
        if (true === auth()->check()) {
            if (Cache::has('user.' . auth()->user()->slug . '.locale')) {
                $locale = Cache::get('user.' . auth()->user()->slug . '.locale');
                app()->setLocale($locale);
                Carbon::setLocale($locale);
            } else {
                $locale = auth()->user()->language->localeShort;
                Cache::forever('user.' . auth()->user()->slug . '.locale', $locale);
                app()->setLocale($locale);
                Carbon::setLocale($locale);
            }
        }

        return $next($request);
    }
}
