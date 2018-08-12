<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use Tests\TestCase;
use App\Models\Torrent;
use App\Services\TorrentInfoService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentInfoServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testGetIMDBData(): void
    {
        $torrent = factory(Torrent::class)->create(['imdb_id' => '0468569']);

        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $title = $torrentInfoService->getTorrentIMDBData($torrent);
        $this->assertSame('0468569', $title->imdbid());
        $this->assertSame(60 * 24, $title->cache_expire);
    }

    public function testGetIMDBDataWhenThereIsNoIMDBId(): void
    {
        $torrent = factory(Torrent::class)->create(['imdb_id' => null]);

        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $title = $torrentInfoService->getTorrentIMDBData($torrent);
        $this->assertNull($title);
    }
}
