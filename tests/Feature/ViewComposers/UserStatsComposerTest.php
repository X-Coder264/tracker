<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComposers;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserStatsComposerTest extends TestCase
{
    use RefreshDatabase;

    public function testDataForLoggedInUser(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        $seedingPeers = factory(Peer::class, 2)->create(['user_id' => $user->id, 'seeder' => true]);
        $leechingPeer = factory(Peer::class)->create(['user_id' => $user->id, 'seeder' => false]);

        $viewFactory = $this->app->make(Factory::class);
        $view = $viewFactory->make('partials.user-statistics');
        $view->render();
        $viewData = $view->getData();
        $this->assertSame(2, $viewData['numberOfSeedingTorrents']);
        $this->assertSame(1, $viewData['numberOfLeechingTorrents']);

        $peers = Cache::get('user.' . $user->id . '.peers');
        $this->assertInstanceOf(Collection::class, $peers);
        $this->assertSame(3, $peers->count());
        $this->assertTrue($seedingPeers[0]->is($peers[0]));
        $this->assertTrue($seedingPeers[1]->is($peers[1]));
        $this->assertTrue($leechingPeer->is($peers[2]));
    }

    public function testDataForLoggedInUserWhenTheUserDoesNotSeedOrLeechAnything(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        $viewFactory = $this->app->make(Factory::class);
        $view = $viewFactory->make('partials.user-statistics');
        $view->render();
        $viewData = $view->getData();
        $this->assertSame(0, $viewData['numberOfSeedingTorrents']);
        $this->assertSame(0, $viewData['numberOfLeechingTorrents']);

        $peers = Cache::get('user.' . $user->id . '.peers');
        $this->assertInstanceOf(Collection::class, $peers);
        $this->assertTrue($peers->isEmpty());
    }

    public function testDataForNonLoggedInUser(): void
    {
        factory(Peer::class, 2)->create(['seeder' => true]);
        factory(Peer::class)->create(['seeder' => false]);

        $viewFactory = $this->app->make(Factory::class);
        $view = $viewFactory->make('layouts.app');
        $view->render();
        $viewData = $view->getData();
        $this->assertSame([], $viewData);
    }
}
