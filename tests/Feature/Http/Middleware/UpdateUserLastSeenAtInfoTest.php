<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Http\Middleware\UpdateUserLastSeenAtInfo;
use Carbon\CarbonImmutable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Router;
use Tests\TestCase;

class UpdateUserLastSeenAtInfoTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserLastSeenAtGetsUpdatedIfItWasNullBefore(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['last_seen_at' => null]);
        $this->actingAs($user);

        $this->assertNull($user->last_seen_at);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertInstanceOf(CarbonImmutable::class, $freshUser->last_seen_at);
        $this->assertLessThanOrEqual(5, CarbonImmutable::now()->diffInSeconds($freshUser->last_seen_at));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testUserLastSeenAtDoesNotGetUpdatedIfItWasAlreadyUpdatedLessThanFiveMinutesAgo(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['last_seen_at' => CarbonImmutable::now()->subMinutes(4)]);
        $this->actingAs($user);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertSame($user->last_seen_at->format('Y-m-d H:i:s'), $freshUser->last_seen_at->format('Y-m-d H:i:s'));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testUserLastSeenAtGetsUpdatedIfItWasLastUpdatedMoreThanFiveMinutesAgo(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['last_seen_at' => CarbonImmutable::now()->subSeconds(UpdateUserLastSeenAtInfo::FIVE_MINUTES_IN_SECONDS + 1)]);
        $this->actingAs($user);

        $this->get(route('home'));

        $freshUser = $user->fresh();
        $this->assertLessThanOrEqual(5, CarbonImmutable::now()->diffInSeconds($freshUser->last_seen_at));
        $this->assertSame($user->updated_at->format('Y-m-d H:i:s'), $freshUser->updated_at->format('Y-m-d H:i:s'));
    }

    public function testMiddlewareIsAppliedOnAllWebRoutes(): void
    {
        $this->app->make(Kernel::class);

        /** @var Router $router */
        $router = $this->app->make(Router::class);
        $this->assertTrue(in_array(UpdateUserLastSeenAtInfo::class, $router->getMiddlewareGroups()['web'], true));
    }
}
