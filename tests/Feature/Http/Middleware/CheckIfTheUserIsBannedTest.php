<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use App\Http\Middleware\CheckIfTheUserIsBanned;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CheckIfTheUserIsBannedTest extends TestCase
{
    use RefreshDatabase;

    public function testBannedUserGetsLoggedOutAndRedirectedToLoginPage(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->states('banned')->create();
        $this->actingAs($user);

        $response = $this->get(route('home'));
        $this->assertGuest();
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $response->assertSessionHas('error', trans('messages.user.banned'));
    }

    public function testMiddlewareIsAppliedOnAllWebRoutes(): void
    {
        $this->app->make(Kernel::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(CheckIfTheUserIsBanned::class, $router->getMiddlewareGroups()['web']));
    }
}
