<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class SetUserLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (($locale = $request->session()->get('locale') !== null)) {
            app()->setLocale($locale);
        } else {
            if (true === auth()->check()) {
                if (Cache::has('user.' . auth()->user()->slug . '.locale')) {
                    $locale = Cache::get('user.' . auth()->user()->slug . '.locale');
                    app()->setLocale($locale);
                    $request->session()->put('locale', $locale);
                } else {
                    $locale = auth()->user()->language->localeShort;
                    Cache::forever('user.' . auth()->user()->slug . '.locale', $locale);
                    app()->setLocale($locale);
                    $request->session()->put('locale', $locale);
                }
            }
        }

        return $next($request);
    }
}
