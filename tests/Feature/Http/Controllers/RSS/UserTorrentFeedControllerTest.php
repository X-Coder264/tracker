<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\RSS;

use Tests\TestCase;
use App\Models\User;
use App\Models\TorrentCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserTorrentFeedControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $torrentCategories = factory(TorrentCategory::class, 2)->create();

        $this->actingAs($user);

        $response = $this->get(route('users.rss.show'));
        $response->assertStatus(200);
        $response->assertViewIs('rss.show');
        $response->assertViewHas('categories');

        $this->assertInstanceOf(Collection::class, $response->viewData('categories'));
        $this->assertSame(2, $response->viewData('categories')->count());
        $this->assertTrue($torrentCategories[0]->is($response->viewData('categories')[0]));
        $this->assertTrue($torrentCategories[1]->is($response->viewData('categories')[1]));

        $response->assertSee($torrentCategories[0]->name);
        $response->assertSee($torrentCategories[1]->name);
    }

    public function testGettingTheLinkWithoutProvidingAnyCategory(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        factory(TorrentCategory::class, 2)->create();

        $this->actingAs($user);

        $response = $this->post(route('users.rss.store'));
        $response->assertRedirect(route('users.rss.show'));
        $response->assertStatus(302);
        $response->assertSessionHas('rssURL', route('torrents.rss', ['passkey' => $user->passkey]));
    }

    public function testGettingTheLinkWithProvidingTheWantedCategories(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $torrentCategories = factory(TorrentCategory::class, 3)->create();

        $this->actingAs($user);

        $response = $this->post(
            route('users.rss.store'),
            [
                'categories' => [$torrentCategories[1]->id, 'non-valid-id', $torrentCategories[2]->id],
            ]
        );
        $response->assertRedirect(route('users.rss.show'));
        $response->assertStatus(302);
        $response->assertSessionHas(
            'rssURL',
            route(
                'torrents.rss',
                [
                    'passkey' => $user->passkey,
                    'categories' => sprintf('%d,%d', $torrentCategories[1]->id, $torrentCategories[2]->id),
                ]
            )
        );
    }

    public function testUserSeesTheFeedLink(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $rssFeedUrl = route('torrents.rss', ['passkey' => $user->passkey]);

        $this->actingAs($user)->withSession(['rssURL' => $rssFeedUrl]);

        $response = $this->get(route('users.rss.show'));
        $response->assertStatus(200);
        $response->assertViewIs('rss.show');
        $response->assertViewHas('categories');

        $response->assertSee($rssFeedUrl);
    }

    public function testGuestsCannotAccessThePage(): void
    {
        $response = $this->get(route('users.rss.show'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testGuestsCannotSubmitTheForm(): void
    {
        $response = $this->post(route('users.rss.store'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
