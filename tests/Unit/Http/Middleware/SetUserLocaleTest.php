<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Auth\AuthManager;
use Illuminate\Cache\CacheManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\SetUserLocale;

class SetUserLocaleTest extends TestCase
{
    public function testForALoggedInUserItSetsHisLocale(): void
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

        $middleware = new SetUserLocale(
            $this->app->make(AuthManager::class),
            $this->app->make(CacheManager::class),
            $this->app
        );

        $request = new Request();
        $next = new class() {
            public $called = false;

            public function __invoke(Request $request)
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

    public function testIfTheUserIsNotLoggedInJustCallTheNextMiddlewareInThePipe(): void
    {
        Auth::shouldReceive('check')->andReturn(false);

        $localeBefore = 'en';
        $this->app->setLocale($localeBefore);
        Carbon::setLocale($localeBefore);

        $middleware = new SetUserLocale(
            $this->app->make(AuthManager::class),
            $this->app->make(CacheManager::class),
            $this->app
        );

        $request = new Request();
        $next = new class() {
            public $called = false;

            public function __invoke(Request $request)
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

    public function testMiddlewareIsAppliedOnAllWebRoutes(): void
    {
        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(SetUserLocale::class, $router->getMiddlewareGroups()['web']));
    }
}
