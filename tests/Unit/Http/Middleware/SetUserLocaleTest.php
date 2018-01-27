<?php

namespace Tests\Unit\Http\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Routing\RouteCollection;

class SetUserLocaleTest extends TestCase
{
    public function testForALoggedInUserItSetsHisLocale()
    {
        Auth::shouldReceive('check')->andReturn(true);

        $user = new class() {
            public $language;
            public $slug = 'test_slug';

            public function __construct()
            {
                $this->language = new class() {
                    public $localeShort = 'hr';
                };
            }
        };

        Auth::shouldReceive('user')->andReturn($user);

        $middleware = new SetUserLocale();

        $request = new Request();
        $next = new class() {
            public $called = false;

            public function __invoke($request)
            {
                $this->called = true;

                return $request;
            }
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($next->called);
        $this->assertSame('hr', $this->app->getLocale());
        $this->assertSame('hr', Carbon::getLocale());
        $this->assertSame('hr', Cache::get('user.test_slug.locale'));
        $this->assertSame($request, $result);
    }

    public function testIfTheUserIsNotLoggedInJustCallTheNextMiddlewareInThePipe()
    {
        Auth::shouldReceive('check')->andReturn(false);

        $localeBefore = 'en';
        $this->app->setLocale($localeBefore);
        Carbon::setLocale($localeBefore);

        $middleware = new SetUserLocale();

        $request = new Request();
        $next = new class() {
            public $called = false;

            public function __invoke($request)
            {
                $this->called = true;

                return $request;
            }
        };

        $result = $middleware->handle($request, $next);

        $this->assertTrue($next->called);
        $this->assertSame($localeBefore, $this->app->getLocale());
        $this->assertSame($localeBefore, Carbon::getLocale());
        $this->assertSame($request, $result);
    }

    public function testMiddlewareIsAppliedToAllWebRoutes()
    {
        /* @var RouteCollection $allRoutes */
        $allRoutes = Route::getRoutes()->getRoutesByName();

        foreach ($allRoutes as $route) {
            if (! Str::startsWith($route->uri, ['_', 'api'])) {
                $this->assertContains(
                    SetUserLocale::class,
                    Route::gatherRouteMiddleware($route)
                );
            }
        }
    }
}
