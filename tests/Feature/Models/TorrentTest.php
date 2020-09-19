<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Peer;
use App\Models\Snatch;
use App\Models\Torrent;
use App\Models\TorrentCategory;
use App\Models\TorrentComment;
use App\Models\User;
use Database\Factories\PeerFactory;
use Database\Factories\SnatchFactory;
use Database\Factories\TorrentCategoryFactory;
use Database\Factories\TorrentCommentFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\TorrentInfoHashFactory;
use Database\Factories\UserFactory;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Str;
use Tests\TestCase;

class TorrentTest extends TestCase
{
    use DatabaseTransactions;

    public function testSizeAccessor(): void
    {
        TorrentFactory::new()->create();
        $torrent = Torrent::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($torrent->getRawOriginal('size'))->andReturn($returnValue);
        $this->assertSame($returnValue, $torrent->size);
    }

    public function testTorrentHasSlug(): void
    {
        $user = UserFactory::new()->create();
        $torrent = new Torrent();
        $torrent->name = 'test name';
        $torrent->size = 34356212;
        $torrent->uploader_id = $user->id;
        $torrent->category_id = TorrentCategoryFactory::new()->create()->id;
        $torrent->description = 'test description';
        $torrent->save();

        $this->assertNotEmpty($torrent->slug);
    }

    public function testUploaderRelationship(): void
    {
        TorrentFactory::new()->create();

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
        PeerFactory::new()->create();

        $torrent = Torrent::firstOrFail();
        $peer = Peer::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->peers());
        $this->assertInstanceOf(Collection::class, $torrent->peers);
        $this->assertSame($torrent->peers[0]->id, $peer->id);
        $this->assertSame($torrent->peers[0]->user_id, $peer->user_id);
        $this->assertSame($torrent->peers[0]->uploaded, $peer->uploaded);
        $this->assertSame($torrent->peers[0]->downloaded, $peer->downloaded);
        $this->assertSame($torrent->peers[0]->user_agent, $peer->user_agent);
        $this->assertSame($torrent->peers[0]->left, $peer->left);
        $this->assertSame($torrent->peers[0]->updated_at->format('Y-m-d H:i:s'), $peer->updated_at->format('Y-m-d H:i:s'));
    }

    public function testInfoHashesRelationship(): void
    {
        $v1InfoHash = sha1(Str::random(200));
        $v2InfoHash = substr(hash('sha256', Str::random(200)), 0, 40);

        TorrentFactory::new()
            ->has(TorrentInfoHashFactory::new()->state(['version' => 1, 'info_hash' => $v1InfoHash]), 'infoHashes')
            ->has(TorrentInfoHashFactory::new()->state(['version' => 2, 'info_hash' => $v2InfoHash]), 'infoHashes')
            ->create();

        $torrent = Torrent::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->infoHashes());
        $this->assertInstanceOf(Collection::class, $torrent->infoHashes);
        $this->assertSame(1, $torrent->infoHashes[0]->version);
        $this->assertSame($v1InfoHash, $torrent->infoHashes[0]->info_hash);
        $this->assertSame(2, $torrent->infoHashes[1]->version);
        $this->assertSame($v2InfoHash, $torrent->infoHashes[1]->info_hash);
    }

    public function testCommentsRelationship(): void
    {
        TorrentCommentFactory::new()->create();

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
        TorrentFactory::new()->create();

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
        SnatchFactory::new()->snatched()->create();

        $torrent = Torrent::firstOrFail();
        $snatch = Snatch::firstOrFail();
        $this->assertInstanceOf(HasMany::class, $torrent->snatches());
        $this->assertInstanceOf(Collection::class, $torrent->snatches);
        $this->assertSame($torrent->snatches[0]->id, $snatch->id);
        $this->assertSame($torrent->snatches[0]->user_id, $snatch->user_id);
        $this->assertSame($torrent->snatches[0]->uploaded, $snatch->uploaded);
        $this->assertSame($torrent->snatches[0]->downloaded, $snatch->downloaded);
        $this->assertSame($torrent->snatches[0]->left, $snatch->left);
        $this->assertSame($torrent->snatches[0]->seed_time, $snatch->seed_time);
        $this->assertSame($torrent->snatches[0]->leech_time, $snatch->leech_time);
        $this->assertSame($torrent->snatches[0]->times_announced, $snatch->times_announced);
        $this->assertSame($torrent->snatches[0]->user_agent, $snatch->user_agent);
        $this->assertSame($torrent->snatches[0]->left, $snatch->left);
        $this->assertSame($torrent->snatches[0]->created_at->format('Y-m-d H:i:s'), $snatch->created_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->snatches[0]->updated_at->format('Y-m-d H:i:s'), $snatch->updated_at->format('Y-m-d H:i:s'));
        $this->assertSame($torrent->snatches[0]->finished_at->format('Y-m-d H:i:s'), $snatch->finished_at->format('Y-m-d H:i:s'));
    }
}
