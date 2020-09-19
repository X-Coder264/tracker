<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Torrent;
use App\Models\TorrentComment;
use Database\Factories\TorrentCommentFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\UserFactory;
use Illuminate\Cache\TaggedCache;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Tests\TestCase;

class TorrentCommentControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testCreate()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        /** @var Torrent $torrent */
        $torrent = TorrentFactory::new()->create(['uploader_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('torrent-comments.create', $torrent));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrent-comments.create');
        $response->assertViewHas('torrent');
        $response->assertViewHas('torrentComment');
        $this->assertTrue($torrent->is($response->original->torrent));
        $this->assertInstanceOf(TorrentComment::class, $response->original->torrentComment);
    }

    public function testStore()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $torrent = TorrentFactory::new()->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $cache = $this->app->make(Repository::class);

        /** @var TaggedCache $taggedCache */
        $taggedCache = $cache->tags([sprintf('torrent.%d', $torrent->id)]);
        $taggedCache->put(sprintf('comments.page.%d', 1), 'foo');
        $taggedCache->put(sprintf('comments.page.%d', 9999), 'bar');
        $this->assertTrue($taggedCache->has(sprintf('comments.page.%d', 1)));
        $this->assertTrue($taggedCache->has(sprintf('comments.page.%d', 9999)));

        $comment = 'test comment';

        $response = $this->post(route('torrent-comments.store', $torrent), [
            'comment' => $comment,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('torrentCommentSuccess');

        $torrentComment = TorrentComment::firstOrFail();
        $this->assertSame($user->id, (int) $torrentComment->user_id);
        $this->assertSame($torrent->id, (int) $torrentComment->torrent_id);
        $this->assertSame($comment, $torrentComment->comment);

        $this->assertFalse($taggedCache->has(sprintf('comments.page.%d', 1)));
        $this->assertFalse($taggedCache->has(sprintf('comments.page.%d', 9999)));
    }

    public function testEdit()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $torrentComment = TorrentCommentFactory::new()->create(['user_id' => $user->id]);
        $this->actingAs($user);
        $response = $this->get(route('torrent-comments.edit', $torrentComment));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('torrent-comments.edit');
        $response->assertViewHas('torrentComment');
        $this->assertInstanceOf(TorrentComment::class, $response->original->torrentComment);
        $response->assertSee($torrentComment->comment);
    }

    public function testUpdate()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $torrent = TorrentFactory::new()->create(['uploader_id' => $user->id]);
        $torrentComment = TorrentCommentFactory::new()->create(
            [
                'user_id' => $user->id,
                'torrent_id' => $torrent->id,
                'comment' => 'test 123',
            ]
        );
        $this->actingAs($user);

        $cache = $this->app->make(Repository::class);

        /** @var TaggedCache $taggedCache */
        $taggedCache = $cache->tags([sprintf('torrent.%d', $torrent->id)]);
        $taggedCache->put(sprintf('comments.page.%d', 1), 'foo');
        $taggedCache->put(sprintf('comments.page.%d', 9999), 'bar');
        $this->assertTrue($taggedCache->has(sprintf('comments.page.%d', 1)));
        $this->assertTrue($taggedCache->has(sprintf('comments.page.%d', 9999)));

        $comment = 'test comment';

        $response = $this->put(route('torrent-comments.update', $torrentComment), [
            'comment' => $comment,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrents.show', $torrent));
        $response->assertSessionHas('torrentCommentSuccess');

        $torrentComment = TorrentComment::firstOrFail();
        $this->assertSame($user->id, (int) $torrentComment->user_id);
        $this->assertSame($torrent->id, (int) $torrentComment->torrent_id);
        $this->assertSame($comment, $torrentComment->comment);

        $this->assertFalse($taggedCache->has(sprintf('comments.page.%d', 1)));
        $this->assertFalse($taggedCache->has(sprintf('comments.page.%d', 9999)));
    }

    public function testCommentIsRequiredOnCreate()
    {
        $user = UserFactory::new()->create();
        $torrent = TorrentFactory::new()->create(['uploader_id' => $user->id]);
        $this->actingAs($user);

        $response = $this->from(route('torrent-comments.create', $torrent))
            ->post(
                route('torrent-comments.store', $torrent),
                [
                    'comment' => '',
                ]
            );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrent-comments.create', $torrent));
        $response->assertSessionHasErrors('comment');
        $this->assertSame(0, TorrentComment::count());
    }

    public function testCommentIsRequiredOnUpdate()
    {
        $user = UserFactory::new()->create();
        $torrent = TorrentFactory::new()->create(['uploader_id' => $user->id]);
        $oldComment = 'test 123';
        $torrentComment = TorrentCommentFactory::new()->create(
            [
                'user_id' => $user->id,
                'torrent_id' => $torrent->id,
                'comment' => $oldComment,
            ]
        );
        $this->actingAs($user);

        $response = $this->from(route('torrent-comments.edit', $torrentComment))
            ->put(
                route('torrent-comments.update', $torrentComment),
                [
                    'comment' => '',
                ]
            );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('torrent-comments.edit', $torrentComment));
        $response->assertSessionHasErrors('comment');
        $this->assertSame($oldComment, TorrentComment::firstOrFail()->comment);
    }
}
