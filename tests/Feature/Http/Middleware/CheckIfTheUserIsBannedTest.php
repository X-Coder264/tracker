<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\CheckIfTheUserIsBanned;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Router;
use Tests\TestCase;

class CheckIfTheUserIsBannedTest extends TestCase
{
    use DatabaseTransactions;

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
