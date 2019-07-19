<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Torrent;
use App\Models\TorrentInfoHash;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TorrentInfoHashTest extends TestCase
{
    use DatabaseTransactions;

    public function testTorrentRelationship(): void
    {
        factory(TorrentInfoHash::class)->create();

        $torrentInfoHash = TorrentInfoHash::firstOrFail();
        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $torrentInfoHash->torrent());
        $this->assertInstanceOf(Torrent::class, $torrentInfoHash->torrent);
        $this->assertSame($torrent->name, $torrentInfoHash->torrent->name);
        $this->assertSame($torrent->size, $torrentInfoHash->torrent->size);
        $this->assertSame($torrent->uploader_id, $torrentInfoHash->torrent->uploader_id);
        $this->assertSame($torrent->category_id, $torrentInfoHash->torrent->category_id);
        $this->assertSame($torrent->description, $torrentInfoHash->torrent->description);
        $this->assertSame($torrent->seeders, $torrentInfoHash->torrent->seeders);
        $this->assertSame($torrent->leechers, $torrentInfoHash->torrent->leechers);
        $this->assertSame($torrent->imdb_id, $torrentInfoHash->torrent->imdb_id);
        $this->assertSame($torrent->slug, $torrentInfoHash->torrent->slug);
    }
}
