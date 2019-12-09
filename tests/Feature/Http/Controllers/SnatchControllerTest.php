<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SnatchControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShowFirstPage(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create();
        $snatch = factory(Snatch::class)->states('snatched')->create(['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader]);

        $nonRelevantTorrent = factory(Torrent::class)->create(['uploader_id' => $torrent->uploader]);
        factory(Snatch::class)->create(['torrent_id' => $nonRelevantTorrent->id, 'user_id' => $torrent->uploader]);

        $user = $torrent->uploader->fresh();
        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('snatches.show', ['torrent' => $torrent]));
        $response->assertStatus(200);
        $response->assertViewIs('snatches.show');

        $response->assertViewHas('torrent', $torrent);
        $response->assertViewHas('snatches');
        $response->assertViewHas('timezone', $torrent->uploader->timezone);

        $snatches = $response->viewData('snatches');
        $this->assertInstanceOf(LengthAwarePaginator::class, $snatches);
        $this->assertSame(1, $snatches->total());
        $this->assertSame(15, $snatches->perPage());
        $this->assertSame(1, $snatches->currentPage());
        $this->assertSame(1, count($snatches->items()));
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
        /** @var LengthAwarePaginator $cachedSnatches */
        $cachedSnatches = $cache->get(sprintf('torrent.%d.snatches.page.%d', $torrent->id, 1));
        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedSnatches);
        $this->assertSame(1, $cachedSnatches->total());
        $this->assertSame(15, $cachedSnatches->perPage());
        $this->assertSame(1, $cachedSnatches->currentPage());
        $this->assertSame(1, count($cachedSnatches->items()));
        $this->assertTrue($cachedSnatches->items()[0]->is($snatch));
    }

    public function testShowSecondPage(): void
    {
        $this->withoutExceptionHandling();

        $torrent = factory(Torrent::class)->create();
        /** @var Snatch[] $snatches */
        $snatches = factory(Snatch::class, 16)->states('snatched')->create(['torrent_id' => $torrent->id]);

        $nonRelevantTorrent = factory(Torrent::class)->create(['uploader_id' => $torrent->uploader]);
        factory(Snatch::class)->create(['torrent_id' => $nonRelevantTorrent->id, 'user_id' => $torrent->uploader]);

        $user = $torrent->uploader->fresh();
        $this->actingAs($user);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('snatches.show', ['torrent' => $torrent, 'page' => 2]));
        $response->assertStatus(200);
        $response->assertViewIs('snatches.show');

        $response->assertViewHas('torrent', $torrent);
        $response->assertViewHas('snatches');
        $response->assertViewHas('timezone', $torrent->uploader->timezone);

        $responseSnatches = $response->viewData('snatches');
        $this->assertInstanceOf(LengthAwarePaginator::class, $responseSnatches);
        $this->assertSame(16, $responseSnatches->total());
        $this->assertSame(15, $responseSnatches->perPage());
        $this->assertSame(2, $responseSnatches->currentPage());

        $this->assertTrue($responseSnatches->items()[0]->is($snatches[0]));

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);
        /** @var LengthAwarePaginator $cachedSnatches */
        $cachedSnatches = $cache->get(sprintf('torrent.%d.snatches.page.%d', $torrent->id, 2));
        $this->assertInstanceOf(LengthAwarePaginator::class, $cachedSnatches);
        $this->assertSame(16, $cachedSnatches->total());
        $this->assertSame(15, $cachedSnatches->perPage());
        $this->assertSame(2, $cachedSnatches->currentPage());
        $this->assertTrue($cachedSnatches->items()[0]->is($snatches[0]));
    }

    public function testGuestsCannotSeeTheSnatchPage(): void
    {
        $torrent = factory(Torrent::class)->create();
        factory(Snatch::class)->create(['torrent_id' => $torrent->id, 'user_id' => $torrent->uploader]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->get($urlGenerator->route('snatches.show', ['torrent' => $torrent]));
        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
    }
}
