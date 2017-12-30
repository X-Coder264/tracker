<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Torrent;
use Illuminate\Http\Response;
use App\Http\Models\TorrentComment;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TorrentCommentControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testCreate()
    {
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('torrent-comments.create', $torrent));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrent-comments.create');
        $response->assertViewHas('torrent');
    }

    public function testStore()
    {
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $comment = 'test comment';

        $response = $this->post(route('torrent-comments.store', $torrent), [
            'comment' => $comment,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('torrentCommentSuccess');

        $torrentComment = TorrentComment::findOrFail(1);
        $this->assertSame($user->id, (int) $torrentComment->user_id);
        $this->assertSame($torrent->id, (int) $torrentComment->torrent_id);
        $this->assertSame($comment, $torrentComment->comment);
    }

    public function testCommentIsRequired()
    {
        $user = factory(User::class)->create();
        $torrent = factory(Torrent::class)->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->from(route('torrent-comments.create', $torrent))->post(route('torrent-comments.store', $torrent), [
            'comment' => '',
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrent-comments.create', $torrent));
        $response->assertSessionHasErrors('comment');
        $this->assertSame(0, TorrentComment::count());
    }
}