<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use Illuminate\Foundation\Testing\RefreshDatabase;

class HomeControllerTest extends TestCase
{
    use RefreshDatabase;

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
        ]);
    }

    public function testGuestsCannotSeeTheHomePage(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }
}
