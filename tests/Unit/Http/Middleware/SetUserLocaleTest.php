<?php

declare(strict_types=1);

namespace Tests\Unit\Http\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Http\Kernel;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Contracts\Cache\Repository;
use PHPUnit\Framework\MockObject\MockObject;

class SetUserLocaleTest extends TestCase
{
    public function testForALoggedInUserItSetsHisLocale(): void
    {
        /** @var Guard|MockObject $guardMock */
        $guardMock = $this->createMock(Guard::class);
        $guardMock->expects($this->once())->method('check')->willReturn(true);

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

        $guardMock->expects($this->once())->method('user')->willReturn($user);

        /** @var Repository|MockObject $cache */
        $cache = $this->createMock(Repository::class);
        $cache->expects($this->once())->method('rememberForever')->willReturn('hr');

        $middleware = new SetUserLocale(
            $guardMock,
            $cache,
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
        $this->assertSame($request, $result);
    }

    public function testIfTheUserIsNotLoggedInJustCallTheNextMiddlewareInThePipe(): void
    {
        /** @var Guard|MockObject $guardMock */
        $guardMock = $this->createMock(Guard::class);
        $guardMock->expects($this->once())->method('check')->willReturn(false);

        $localeBefore = 'en';
        $this->app->setLocale($localeBefore);
        Carbon::setLocale($localeBefore);

        /** @var Repository|MockObject $cache */
        $cache = $this->createMock(Repository::class);
        $cache->expects($this->never())->method('rememberForever');

        $middleware = new SetUserLocale(
            $guardMock,
            $cache,
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
        $this->app->make(Kernel::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(SetUserLocale::class, $router->getMiddlewareGroups()['web']));
    }
}
