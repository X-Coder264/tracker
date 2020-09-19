<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Presenters\IMDb\Title;
use App\Services\TorrentInfoService;
use Database\Factories\TorrentFactory;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TorrentInfoServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function testGetIMDBData(): void
    {
        $torrent = TorrentFactory::new()->create(['imdb_id' => '0468569']);

        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $title = $torrentInfoService->getTorrentIMDBData($torrent);
        $this->assertInstanceOf(Title::class, $title);
        $this->assertSame('0468569', $title->getId());
    }

    public function testGetIMDBDataWhenThereIsNoIMDBId(): void
    {
        $torrent = TorrentFactory::new()->create(['imdb_id' => null]);

        $torrentInfoService = $this->app->make(TorrentInfoService::class);

        $title = $torrentInfoService->getTorrentIMDBData($torrent);
        $this->assertNull($title);
    }
}
