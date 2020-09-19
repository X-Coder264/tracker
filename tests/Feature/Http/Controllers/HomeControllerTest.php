<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Torrent;
use App\Services\SizeFormatter;
use Database\Factories\NewsFactory;
use Database\Factories\PeerFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\UserFactory;
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

        $user = UserFactory::new()->create();
        $this->actingAs($user);

        UserFactory::new()->count(2)->banned()->create();

        $torrent = TorrentFactory::new()->alive()->create(['uploader_id' => $user->id]);
        TorrentFactory::new()->count(3)->dead()->create(['uploader_id' => $user->id]);

        PeerFactory::new()->seeder()->create(['torrent_id' => $torrent->id, 'user_id' => $user->id]);
        PeerFactory::new()->count(2)->leecher()->create(['torrent_id' => $torrent->id, 'user_id' => $user->id]);

        $newsOne = NewsFactory::new()->create(['user_id' => $user]);
        $newsTwo = NewsFactory::new()->create(['user_id' => $user, 'subject' => 'test foo', 'text' => 'text foobar']);

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
