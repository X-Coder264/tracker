<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Database\Factories\PeerFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTorrentsControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShowUploadedTorrents(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $nonRelevantUser = UserFactory::new()->create();

        $torrents = TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);
        $torrents[] = TorrentFactory::new()->dead()->create(['uploader_id' => $user->id]);
        TorrentFactory::new()->count(2)->create(['uploader_id' => $nonRelevantUser->id]);

        $this->actingAs($user);

        $response = $this->get(route('user-torrents.show-uploaded-torrents', $user));
        $response->assertStatus(200);
        $response->assertViewIs('user-torrents.show');
        $response->assertViewHas(['torrents', 'title', 'user']);

        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertSame(trans('messages.torrent.current-user.page_title'), $response->viewData('title'));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('torrents'));
        $this->assertSame(3, $response->viewData('torrents')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($torrents[2]->is($response->viewData('torrents')[0]));
        $this->assertTrue($torrents[1]->is($response->viewData('torrents')[1]));
        $this->assertTrue($torrents[0]->is($response->viewData('torrents')[2]));

        $response->assertSee($torrents[0]->name);
        $response->assertSee($torrents[1]->name);
        $response->assertSee($torrents[2]->name);
        $response->assertSee(trans('messages.torrent.current-user.page_title'));
    }

    public function testShowUploadedTorrentsForAnotherUser(): void
    {
        $this->withoutExceptionHandling();

        $loggedUser = UserFactory::new()->create();
        $user = UserFactory::new()->create();

        TorrentFactory::new()->count(2)->create(['uploader_id' => $loggedUser->id]);
        TorrentFactory::new()->dead()->create(['uploader_id' => $loggedUser->id]);
        $torrents = TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);

        $this->actingAs($loggedUser);

        $response = $this->get(route('user-torrents.show-uploaded-torrents', $user));
        $response->assertStatus(200);
        $response->assertViewIs('user-torrents.show');
        $response->assertViewHas(['torrents', 'title', 'user']);

        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertSame(trans('messages.torrent.user.page_title'), $response->viewData('title'));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('torrents'));
        $this->assertSame(2, $response->viewData('torrents')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('torrents')->perPage());
        $this->assertTrue($torrents[1]->is($response->viewData('torrents')[0]));
        $this->assertTrue($torrents[0]->is($response->viewData('torrents')[1]));

        $response->assertSee($torrents[0]->name);
        $response->assertSee($torrents[1]->name);
        $response->assertSee(trans('messages.torrent.user.page_title'));
    }

    public function testShowSeedingTorrents(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $nonRelevantUser = UserFactory::new()->create();

        $torrents = TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);
        $torrents[] = TorrentFactory::new()->dead()->create(['uploader_id' => $user->id]);
        TorrentFactory::new()->count(2)->create(['uploader_id' => $nonRelevantUser->id]);

        $peerOne = PeerFactory::new()->seeder()->create(['user_id' => $user->id, 'torrent_id' => $torrents[0]->id, 'uploaded' => 1000]);
        $peerTwo = PeerFactory::new()->seeder()->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);

        PeerFactory::new()->leecher()->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);
        PeerFactory::new()->seeder()->create(['user_id' => $nonRelevantUser->id, 'torrent_id' => $torrents[0]->id]);

        $this->actingAs($user);

        $response = $this->get(route('user-torrents.show-seeding-torrents', $user));
        $response->assertStatus(200);
        $response->assertViewIs('user-torrents.show-peers');
        $response->assertViewHas(['peers', 'title', 'user', 'noTorrentsMessage']);

        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertSame(trans('messages.common.currently-seeding'), $response->viewData('title'));
        $this->assertSame(trans('messages.common.no-torrents-on-seed'), $response->viewData('noTorrentsMessage'));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('peers'));
        $this->assertSame(2, $response->viewData('peers')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('peers')->perPage());
        $this->assertTrue($peerTwo->is($response->viewData('peers')[0]));
        $this->assertTrue($peerOne->is($response->viewData('peers')[1]));

        $response->assertSee($peerOne->torrent->name);
        $response->assertSee($peerTwo->torrent->name);
        $response->assertSee($peerOne->uploaded);
        $response->assertSee($peerTwo->uploaded);
        $response->assertSee($peerOne->downloaded);
        $response->assertSee($peerTwo->downloaded);
        $response->assertSee($peerOne->user_agent);
        $response->assertSee($peerTwo->user_agent);
        $response->assertSee($peerOne->torrent->created_at->timezone($user->timezone));
        $response->assertSee($peerTwo->torrent->created_at->timezone($user->timezone));
        $response->assertSee(trans('messages.common.currently-seeding'));
        $response->assertDontSee(trans('messages.common.no-torrents-on-seed'));
    }

    public function testShowLeechingTorrents(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $nonRelevantUser = UserFactory::new()->create();

        $torrents = TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);
        $torrents[] = TorrentFactory::new()->dead()->create(['uploader_id' => $user->id]);
        TorrentFactory::new()->count(2)->create(['uploader_id' => $nonRelevantUser->id]);

        $peerOne = PeerFactory::new()->leecher()->create(['user_id' => $user->id, 'torrent_id' => $torrents[0]->id]);
        $peerTwo = PeerFactory::new()->leecher()->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);

        PeerFactory::new()->seeder()->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);
        PeerFactory::new()->leecher()->create(['user_id' => $nonRelevantUser->id, 'torrent_id' => $torrents[0]->id]);

        $this->actingAs($user);

        $response = $this->get(route('user-torrents.show-leeching-torrents', $user));
        $response->assertStatus(200);
        $response->assertViewIs('user-torrents.show-peers');
        $response->assertViewHas(['peers', 'title', 'user', 'noTorrentsMessage']);

        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertSame(trans('messages.common.currently-leeching'), $response->viewData('title'));
        $this->assertSame(trans('messages.common.no-torrents-on-leech'), $response->viewData('noTorrentsMessage'));
        $this->assertInstanceOf(LengthAwarePaginator::class, $response->viewData('peers'));
        $this->assertSame(2, $response->viewData('peers')->count());
        $this->assertSame($user->torrents_per_page, $response->viewData('peers')->perPage());
        $this->assertTrue($peerTwo->is($response->viewData('peers')[0]));
        $this->assertTrue($peerOne->is($response->viewData('peers')[1]));

        $response->assertSee($peerOne->torrent->name);
        $response->assertSee($peerTwo->torrent->name);
        $response->assertSee($peerOne->uploaded);
        $response->assertSee($peerTwo->uploaded);
        $response->assertSee($peerOne->downloaded);
        $response->assertSee($peerTwo->downloaded);
        $response->assertSee($peerOne->user_agent);
        $response->assertSee($peerTwo->user_agent);
        $response->assertSee($peerOne->torrent->created_at->timezone($user->timezone));
        $response->assertSee($peerTwo->torrent->created_at->timezone($user->timezone));
        $response->assertSee(trans('messages.common.currently-leeching'));
        $response->assertDontSee(trans('messages.common.no-torrents-on-leech'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheUploadedPage(): void
    {
        $user = UserFactory::new()->create();

        TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-uploaded-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheSeedingPage(): void
    {
        $user = UserFactory::new()->create();

        TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-seeding-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheLeechingPage(): void
    {
        $user = UserFactory::new()->create();

        TorrentFactory::new()->count(2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-leeching-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
