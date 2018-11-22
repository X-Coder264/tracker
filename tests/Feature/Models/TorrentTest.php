<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\TorrentComment;
use App\Models\TorrentCategory;
use App\Models\TorrentInfoHash;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorrentTest extends TestCase
{
    use RefreshDatabase;

    public function testSizeAccessor(): void
    {
        factory(Torrent::class)->create();
        $torrent = Torrent::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($torrent->getOriginal('size'))->andReturn($returnValue);
        $this->assertSame($returnValue, $torrent->size);
    }

    public function testTorrentHasSlug(): void
    {
        $user = factory(User::class)->create();
        $torrent = new Torrent();
        $torrent->name = 'test name';
        $torrent->size = 34356212;
        $torrent->uploader_id = $user->id;
        $torrent->category_id = factory(TorrentCategory::class)->create()->id;
        $torrent->description = 'test description';
        $torrent->save();

        $this->assertNotEmpty($torrent->slug);
    }

    public function testUploaderRelationship(): void
    {
        factory(Torrent::class)->create();

        $user = User::firstOrFail();
        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $torrent->uploader());
        $this->assertInstanceOf(User::class, $torrent->uploader);
        $this->assertSame($torrent->uploader->id, $user->id);
        $this->assertSame($torrent->uploader->name, $user->name);
        $this->assertSame($torrent->uploader->slug, $user->slug);
    }

    public function testPeersRelationship(): void
    {
        factory(Peer::class)->create();

        $torrent = Torrent::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->peers());
        $this->assertInstanceOf(Collection::class, $torrent->peers);
        $this->assertSame($torrent->peers[0]->id, $peer->id);
        $this->assertSame($torrent->peers[0]->user_id, $peer->user_id);
        $this->assertSame($torrent->peers[0]->uploaded, $peer->uploaded);
        $this->assertSame($torrent->peers[0]->downloaded, $peer->downloaded);
        $this->assertSame($torrent->peers[0]->userAgent, $peer->userAgent);
        $this->assertSame($torrent->peers[0]->seeder, $peer->seeder);
        $this->assertSame($torrent->peers[0]->updated_at->format('Y-m-d H:i:s'), $peer->updated_at->format('Y-m-d H:i:s'));
    }

    public function testInfoHashesRelationship(): void
    {
        $torrent = factory(Torrent::class)->create();
        $v1InfoHash = factory(TorrentInfoHash::class)->create(['torrent_id' => $torrent->id]);
        $v2InfoHash = factory(TorrentInfoHash::class)->states('v2')->create(['torrent_id' => $torrent->id]);
        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->infoHashes());
        $this->assertInstanceOf(Collection::class, $torrent->infoHashes);
        $this->assertSame($v1InfoHash->id, $torrent->infoHashes[0]->id);
        $this->assertSame($v1InfoHash->version, $torrent->infoHashes[0]->version);
        $this->assertSame($v1InfoHash->info_hash, $torrent->infoHashes[0]->info_hash);
        $this->assertSame($v2InfoHash->id, $torrent->infoHashes[1]->id);
        $this->assertSame($v2InfoHash->version, $torrent->infoHashes[1]->version);
        $this->assertSame($v2InfoHash->info_hash, $torrent->infoHashes[1]->info_hash);
    }

    public function testCommentsRelationship(): void
    {
        factory(TorrentComment::class)->create();

        $torrent = Torrent::firstOrFail();
        $torrentComment = TorrentComment::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->comments());
        $this->assertInstanceOf(Collection::class, $torrent->comments);
        $this->assertSame($torrent->comments[0]->id, $torrentComment->id);
        $this->assertSame($torrent->comments[0]->user_id, $torrentComment->user_id);
        $this->assertSame($torrent->comments[0]->comment, $torrentComment->comment);
        $this->assertSame($torrent->comments[0]->created_at->format('Y-m-d H:i:s'), $torrentComment->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->comments[0]->updated_at->format('Y-m-d H:i:s'), $torrentComment->updated_at->format('Y-m-d H:i:s'));
    }

    public function testCategoryRelationship(): void
    {
        factory(Torrent::class)->create();

        $torrentCategory = TorrentCategory::firstOrFail();
        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $torrent->category());
        $this->assertInstanceOf(TorrentCategory::class, $torrent->category);
        $this->assertTrue($torrent->category->is($torrentCategory));
        $this->assertSame($torrent->category->id, $torrentCategory->id);
        $this->assertSame($torrent->category->name, $torrentCategory->name);
        $this->assertSame($torrent->category->slug, $torrentCategory->slug);
    }

    public function testSnatchesRelationship(): void
    {
        factory(Snatch::class)->states('snatched')->create();

        $torrent = Torrent::firstOrFail();
        $snatch = Snatch::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->snatches());
        $this->assertInstanceOf(Collection::class, $torrent->snatches);
        $this->assertSame($torrent->snatches[0]->id, $snatch->id);
        $this->assertSame($torrent->snatches[0]->user_id, $snatch->user_id);
        $this->assertSame($torrent->snatches[0]->uploaded, $snatch->uploaded);
        $this->assertSame($torrent->snatches[0]->downloaded, $snatch->downloaded);
        $this->assertSame($torrent->snatches[0]->left, $snatch->left);
        $this->assertSame($torrent->snatches[0]->seedTime, $snatch->seedTime);
        $this->assertSame($torrent->snatches[0]->leechTime, $snatch->leechTime);
        $this->assertSame($torrent->snatches[0]->timesAnnounced, $snatch->timesAnnounced);
        $this->assertSame($torrent->snatches[0]->userAgent, $snatch->userAgent);
        $this->assertSame($torrent->snatches[0]->seeder, $snatch->seeder);
        $this->assertSame($torrent->snatches[0]->created_at->format('Y-m-d H:i:s'), $snatch->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->snatches[0]->updated_at->format('Y-m-d H:i:s'), $snatch->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->snatches[0]->finished_at->format('Y-m-d H:i:s'), $snatch->finished_at->format('Y-m-d H:i:s'));
    }
}
