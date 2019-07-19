<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Torrent;
use App\Models\TorrentComment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class TorrentCommentTest extends TestCase
{
    use DatabaseTransactions;

    public function testTorrentCommentHasSlug()
    {
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $torrentComment = new TorrentComment();
        $torrentComment->comment = 'test comment';
        $torrentComment->torrent_id = $torrent->id;
        $torrentComment->user_id = $user->id;
        $torrentComment->save();

        $this->assertNotEmpty($torrentComment->slug);
    }

    public function testUserRelationship()
    {
        factory(TorrentComment::class)->create();

        $user = User::firstOrFail();
        $torrentComment = TorrentComment::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $torrentComment->user());
        $this->assertInstanceOf(User::class, $torrentComment->user);
        $this->assertSame($torrentComment->user->id, $user->id);
        $this->assertSame($torrentComment->user->name, $user->name);
        $this->assertSame($torrentComment->user->slug, $user->slug);
    }

    public function testTorrentRelationship()
    {
        factory(TorrentComment::class)->create();

        $torrent = Torrent::firstOrFail();
        $torrentComment = TorrentComment::firstOrFail();
        $this->assertInstanceOf(BelongsTo::class, $torrentComment->torrent());
        $this->assertInstanceOf(Torrent::class, $torrentComment->torrent);
        $this->assertSame($torrentComment->torrent->id, $torrent->id);
        $this->assertSame($torrentComment->torrent->name, $torrent->name);
        $this->assertSame($torrentComment->torrent->slug, $torrent->slug);
    }
}
