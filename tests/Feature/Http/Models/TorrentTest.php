<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Models;

use Tests\TestCase;
use App\Http\Models\Peer;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use App\Http\Models\TorrentComment;
use Facades\App\Services\SizeFormatter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorrentTest extends TestCase
{
    use RefreshDatabase;

    public function testSizeAccessor()
    {
        factory(Torrent::class)->create();
        $torrent = Torrent::firstOrFail();
        $returnValue = '500 MB';
        SizeFormatter::shouldReceive('getFormattedSize')->once()->with($torrent->getOriginal('size'))->andReturn($returnValue);
        $this->assertSame($returnValue, $torrent->size);
    }

    public function testTorrentHasSlug()
    {
        $user = factory(User::class)->create();
        $torrent = new Torrent();
        $torrent->name = 'test name';
        $torrent->info_hash = 'fefsrgererw';
        $torrent->size = 34356212;
        $torrent->uploader_id = $user->id;
        $torrent->description = 'test description';
        $torrent->save();

        $this->assertNotEmpty($torrent->slug);
    }

    public function testUploaderRelationship()
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

    public function testPeersRelationship()
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
        $this->assertSame($torrent->peers[0]->downloaded, $peer->downloaded);
        $this->assertSame($torrent->peers[0]->userAgent, $peer->userAgent);
        $this->assertSame($torrent->peers[0]->seeder, $peer->seeder);
        $this->assertSame($torrent->peers[0]->updated_at->format('Y-m-d H:i:s'), $peer->updated_at->format('Y-m-d H:i:s'));
    }

    public function testCommentsRelationship()
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
}
