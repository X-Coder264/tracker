<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Session\Session;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Laravel\Passport\Http\Middleware\CreateFreshApiToken;
use Tests\TestCase;

final class GetApiTokenTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2019-08-07 12:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function testGetApiToken(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $this->actingAs($user);

        $response = $this->get($this->app->make(UrlGenerator::class)->route('home'));

        $response->assertOk();

        $config = $this->app->make(Repository::class);

        if (Str::startsWith($key = $config->get('app.key'), 'base64:')) {
            $key = base64_decode(substr($key, 7));
        }

        $sessionConfig = $config->get('session');
        $expiration = Carbon::now()->addMinutes($sessionConfig['lifetime']);

        $value = JWT::encode([
            'sub' => $user->id,
            'csrf' => $this->app->make(Session::class)->get('_token'),
            'expiry' => $expiration->getTimestamp(),
        ], $key);

        $response->assertCookie('laravel_token', $value);

        $cookies = $response->headers->getCookies();
        foreach ($cookies as $cookie) {
            if ('laravel_token' === $cookie->getName()) {
                $this->assertSame($sessionConfig['path'], $cookie->getPath());
                $this->assertSame($sessionConfig['domain'], $cookie->getDomain());
                $this->assertSame($sessionConfig['secure'] ?? false, $cookie->isSecure());
                $this->assertSame($sessionConfig['same_site'], $cookie->getSameSite());
                $this->assertSame($expiration->getTimestamp(), $cookie->getExpiresTime());
                $this->assertTrue($cookie->isHttpOnly());
                $this->assertFalse($cookie->isRaw());
            }
        }
    }

    public function testMiddlewareIsAppliedOnAllWebRoutes(): void
    {
        $this->app->make(Kernel::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(CreateFreshApiToken::class, $router->getMiddlewareGroups()['web']));
    }
}
