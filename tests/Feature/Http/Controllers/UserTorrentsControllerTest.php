<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\User;
use App\Models\Torrent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserTorrentsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $nonRelevantUser = factory(User::class)->create();

        $torrents = factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);
        $torrents[] = factory(Torrent::class)->states('dead')->create(['uploader_id' => $user->id]);
        factory(Torrent::class, 2)->create(['uploader_id' => $nonRelevantUser->id]);

        $this->actingAs($user);

        $response = $this->get(route('user-torrents.show'));
        $response->assertStatus(200);
        $response->assertViewIs('user-torrents.show');
        $response->assertViewHas('torrents');

        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('torrents'));
        $this->assertSame(3, $response->viewData('torrents')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($torrents[2]->is($response->viewData('torrents')[0]));
        $this->assertTrue($torrents[1]->is($response->viewData('torrents')[1]));
        $this->assertTrue($torrents[0]->is($response->viewData('torrents')[2]));

        $response->assertSee($torrents[0]->name);
        $response->assertSee($torrents[1]->name);
        $response->assertSee($torrents[2]->name);
    }

    public function testGuestGetsRedirectedToTheLoginPage(): void
    {
        $response = $this->get(route('user-torrents.show'));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
