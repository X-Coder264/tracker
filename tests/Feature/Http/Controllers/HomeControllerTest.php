<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\News;
use App\Models\Peer;
use App\Models\Torrent;
use App\Models\User;
use App\Services\SizeFormatter;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

class HomeControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testIndex(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);

        factory(User::class, 2)->states('banned')->create();

        $torrent = factory(Torrent::class)->states('alive')->create(['uploader_id' => $user->id]);
        factory(Torrent::class, 3)->states('dead')->create(['uploader_id' => $user->id]);

        factory(Peer::class)->states('seeder')->create(['torrent_id' => $torrent->id, 'user_id' => $user->id]);
        factory(Peer::class, 2)->states('leecher')->create(['torrent_id' => $torrent->id, 'user_id' => $user->id]);

        $newsOne = factory(News::class)->create(['user_id' => $user]);
        $newsTwo = factory(News::class)->create(['user_id' => $user, 'subject' => 'test foo', 'text' => 'text foobar']);

        $response = $this->get(route('home'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('home.index');
        $response->assertViewHasAll([
            'usersCount' => 3,
            'bannedUsersCount' => 2,
            'peersCount' => 3,
            'seedersCount' => 1,
            'leechersCount' => 2,
            'torrentsCount' => 4,
            'deadTorrentsCount' => 3,
            'totalTorrentSize' => $this->app->make(SizeFormatter::class)->getFormattedSize((int) Torrent::sum('size')),
            'news' => $newsTwo,
        ]);

        $response->assertSee('test foo');
        $response->assertSee('text foobar');

        $cache = $this->app->make(Repository::class);
        $this->assertTrue($newsTwo->is($cache->get('news')));
    }

    public function testGuestsCannotSeeTheHomePage(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }
}
