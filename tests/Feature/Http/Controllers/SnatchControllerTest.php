<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class SnatchControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create();
        $snatch = factory(Snatch::class)->states('snatched')->create(['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader]);

        $nonRelevantTorrent = factory(Torrent::class)->create(['uploader_id' => $torrent->uploader]);
        factory(Snatch::class)->create(['torrent_id' => $nonRelevantTorrent->id, 'user_id' => $torrent->uploader]);

        $user = $torrent->uploader->fresh();
        $this->actingAs($user);

        $response = $this->get(route('snatches.show', ['torrent' => $torrent]));
        $response->assertStatus(200);
        $response->assertViewIs('snatches.show');

        $response->assertViewHas('torrent', $torrent);
        $response->assertViewHas('snatches');
        $response->assertViewHas('timezone', $torrent->uploader->timezone);

        $snatches = $response->viewData('snatches');
        $this->assertInstanceOf(LengthAwarePaginator::class, $snatches);
        $this->assertSame(1, $snatches->total());

        $this->assertTrue($snatches->items()[0]->is($snatch));

        $response->assertSee($snatch->user->name);
        $response->assertSee($snatch->uploaded);
        $response->assertSee($snatch->downloaded);
        $response->assertSee($snatch->seedTime);
        $response->assertSee($snatch->leechTime);
        $response->assertSee($snatch->left);
        $response->assertSee($snatch->userAgent);
        $response->assertSee($snatch->finished_at->timezone($user->timezone));
        $response->assertSee($snatch->updated_at->timezone($user->timezone));

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);
        $cachedSnatches = $cache->get('torrent.' . $torrent->id . '.snatches');
        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedSnatches);
        $this->assertSame(1, $cachedSnatches->total());
        $this->assertTrue($snatches->items()[0]->is($snatch));
    }

    public function testGuestsCannotSeeTheSnatchPage(): void
    {
        $torrent = factory(Torrent::class)->create();
        factory(Snatch::class)->create(['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader]);

        $response = $this->get(route('snatches.show', ['torrent' => $torrent]));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
