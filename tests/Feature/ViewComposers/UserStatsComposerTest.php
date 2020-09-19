<?php

declare(strict_types=1);

namespace Tests\Feature\ViewComposers;

use Database\Factories\PeerFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\View\Factory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserStatsComposerTest extends TestCase
{
    use DatabaseTransactions;

    public function testDataForLoggedInUser(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $this->actingAs($user);

        $seedingPeers = PeerFactory::new()->count(2)->create(['user_id' => $user->id, 'left' => 0]);
        $leechingPeer = PeerFactory::new()->create(['user_id' => $user->id, 'left' => 500]);

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

        $user = UserFactory::new()->create();
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
        PeerFactory::new()->count(2)->create(['left' => 0]);
        PeerFactory::new()->create(['left' => 800]);

        $viewFactory = $this->app->make(Factory::class);
        $view = $viewFactory->make('layouts.app');
        $view->render();
        $viewData = $view->getData();
        $this->assertSame([], $viewData);
    }
}
