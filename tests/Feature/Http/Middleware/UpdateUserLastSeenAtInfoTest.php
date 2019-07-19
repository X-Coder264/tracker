<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Routing\Router;
use Illuminate\Contracts\Http\Kernel;
use App\Http\Middleware\UpdateUserLastSeenAtInfo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UpdateUserLastSeenAtInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserLastSeenAtGetsUpdatedIfItWasNullBefore(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['last_seen_at' => null]);
        $this->actingAs($user);

        $this->assertNull($user->last_seen_at);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertInstanceOf(Carbon::class, $freshUser->last_seen_at);
        $this->assertLessThanOrEqual(5, Carbon::now()->diffInSeconds($freshUser->last_seen_at));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testUserLastSeenAtDoesNotGetUpdatedIfItWasAlreadyUpdatedLessThanFiveMinutesAgo(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['last_seen_at' => Carbon::now()->subMinutes(4)]);
        $this->actingAs($user);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertSame($user->last_seen_at->format('Y-m-d H:i:s'), $freshUser->last_seen_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testUserLastSeenAtGetsUpdatedIfItWasLastUpdatedMoreThanFiveMinutesAgo(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['last_seen_at' => Carbon::now()->subSeconds(UpdateUserLastSeenAtInfo::FIVE_MINUTES_IN_SECONDS + 1)]);
        $this->actingAs($user);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertLessThanOrEqual(5, Carbon::now()->diffInSeconds($freshUser->last_seen_at));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testMiddlewareIsAppliedOnAllWebRoutes(): void
    {
        $this->app->make(Kernel::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(UpdateUserLastSeenAtInfo::class, $router->getMiddlewareGroups()['web']));
    }
}
