<?php

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use App\Http\Models\TorrentComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TorrentCommentTest extends TestCase
{
    use RefreshDatabase;

    public function testTorrentCommentHasSlug()
    {
        $torrent = factory(Torrent::class)->create();
        $torrentComment = new TorrentComment();
        $torrentComment->comment = 'test comment';
        $torrentComment->torrent_id = $torrent->id;
        $torrentComment->user_id = 1;
        $torrentComment->save();

        $this->assertNotEmpty($torrentComment->slug);
    }

    public function testUserRelationship()
    {
        factory(TorrentComment::class)->create();

        $user = User::findOrFail(1);
        $torrentComment = TorrentComment::findOrFail(1);
        $this->assertInstanceOf(BelongsTo::class, $torrentComment->user());
        $this->assertInstanceOf(User::class, $torrentComment->user);
        $this->assertSame($torrentComment->user->id, $user->id);
        $this->assertSame($torrentComment->user->name, $user->name);
        $this->assertSame($torrentComment->user->slug, $user->slug);
    }

    public function testTorrentRelationship()
    {
        factory(TorrentComment::class)->create();

        $torrent = Torrent::findOrFail(1);
        $torrentComment = TorrentComment::findOrFail(1);
        $this->assertInstanceOf(BelongsTo::class, $torrentComment->torrent());
        $this->assertInstanceOf(Torrent::class, $torrentComment->torrent);
        $this->assertSame($torrentComment->torrent->id, $torrent->id);
        $this->assertSame($torrentComment->torrent->name, $torrent->name);
        $this->assertSame($torrentComment->torrent->slug, $torrent->slug);
    }
}
