<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Torrent;
use App\Models\TorrentCategory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TorrentCategoryTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentCategoryHasSlug(): void
    {
        $torrentCategory = new TorrentCategory();
        $torrentCategory->name = 'test';
        $torrentCategory->save();

        $this->assertNotEmpty($torrentCategory->slug);
    }

    public function testTorrentsRelationship(): void
    {
        factory(Torrent::class)->create();

        $torrent = Torrent::firstOrFail();
        $torrentCategory = TorrentCategory::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrentCategory->torrents());
        $this->assertInstanceOf(Collection::class, $torrentCategory->torrents);
        $this->assertSame($torrent->id, $torrentCategory->torrents[0]->id);
        $this->assertSame($torrent->name, $torrentCategory->torrents[0]->name);
        $this->assertSame($torrent->description, $torrentCategory->torrents[0]->description);
        $this->assertSame($torrent->size, $torrentCategory->torrents[0]->size);
        $this->assertSame($torrent->seeders, $torrentCategory->torrents[0]->seeders);
        $this->assertSame($torrent->leechers, $torrentCategory->torrents[0]->leechers);
        $this->assertSame($torrent->slug, $torrentCategory->torrents[0]->slug);
        $this->assertSame($torrent->uploader_id, $torrentCategory->torrents[0]->uploader_id);
        $this->assertSame($torrent->info_hash, $torrentCategory->torrents[0]->info_hash);
        $this->assertSame($torrent->uploaded, $torrentCategory->torrents[0]->uploaded);
        $this->assertSame($torrent->downloaded, $torrentCategory->torrents[0]->downloaded);
        $this->assertSame($torrent->created_at->format('Y-m-d H:i:s'), $torrentCategory->torrents[0]->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->updated_at->format('Y-m-d H:i:s'), $torrentCategory->torrents[0]->updated_at->format('Y-m-d H:i:s'));
    }

    public function testAfterSavingACategoryTheCacheGetsFlushed(): void
    {
        $cache = $this->app->make(Repository::class);
        $cache->put('torrentCategories', [], 500);

        $this->assertTrue($cache->has('torrentCategories'));

        $torrentCategory = new TorrentCategory();
        $torrentCategory->name = 'test';
        $torrentCategory->imdb = 0;
        $torrentCategory->save();

        // the cache gets flushed when a new category is saved
        $this->assertFalse($cache->has('torrentCategories'));

        $cache->put('torrentCategories', [], 500);

        $this->assertTrue($cache->has('torrentCategories'));

        $torrentCategory->name = 'foo';
        $torrentCategory->save();

        // the cache gets flushed when an existing category is saved
        $this->assertFalse($cache->has('torrentCategories'));
    }
}
