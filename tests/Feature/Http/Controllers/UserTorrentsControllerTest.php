<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Torrent;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserTorrentsControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testShowUploadedTorrents(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $nonRelevantUser = factory(User::class)->create();

        $torrents = factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);
        $torrents[] = factory(Torrent::class)->states('dead')->create(['uploader_id' => $user->id]);
        factory(Torrent::class, 2)->create(['uploader_id' => $nonRelevantUser->id]);

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

        $loggedUser = factory(User::class)->create();
        $user = factory(User::class)->create();

        factory(Torrent::class, 2)->create(['uploader_id' => $loggedUser->id]);
        factory(Torrent::class)->states('dead')->create(['uploader_id' => $loggedUser->id]);
        $torrents = factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);

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

        $user = factory(User::class)->create();
        $nonRelevantUser = factory(User::class)->create();

        $torrents = factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);
        $torrents[] = factory(Torrent::class)->states('dead')->create(['uploader_id' => $user->id]);
        factory(Torrent::class, 2)->create(['uploader_id' => $nonRelevantUser->id]);

        $peerOne = factory(Peer::class)->states('seeder')->create(['user_id' => $user->id, 'torrent_id' => $torrents[0]->id, 'uploaded' => 1000]);
        $peerTwo = factory(Peer::class)->states('seeder')->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);

        factory(Peer::class)->states('leecher')->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);
        factory(Peer::class)->states('seeder')->create(['user_id' => $nonRelevantUser->id, 'torrent_id' => $torrents[0]->id]);

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
        $response->assertSee($peerOne->userAgent);
        $response->assertSee($peerTwo->userAgent);
        $response->assertSee($peerOne->torrent->created_at->timezone($user->timezone));
        $response->assertSee($peerTwo->torrent->created_at->timezone($user->timezone));
        $response->assertSee(trans('messages.common.currently-seeding'));
        $response->assertDontSee(trans('messages.common.no-torrents-on-seed'));
    }

    public function testShowLeechingTorrents(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $nonRelevantUser = factory(User::class)->create();

        $torrents = factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);
        $torrents[] = factory(Torrent::class)->states('dead')->create(['uploader_id' => $user->id]);
        factory(Torrent::class, 2)->create(['uploader_id' => $nonRelevantUser->id]);

        $peerOne = factory(Peer::class)->states('leecher')->create(['user_id' => $user->id, 'torrent_id' => $torrents[0]->id]);
        $peerTwo = factory(Peer::class)->states('leecher')->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);

        factory(Peer::class)->states('seeder')->create(['user_id' => $user->id, 'torrent_id' => $torrents[2]->id]);
        factory(Peer::class)->states('leecher')->create(['user_id' => $nonRelevantUser->id, 'torrent_id' => $torrents[0]->id]);

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
        $response->assertSee($peerOne->userAgent);
        $response->assertSee($peerTwo->userAgent);
        $response->assertSee($peerOne->torrent->created_at->timezone($user->timezone));
        $response->assertSee($peerTwo->torrent->created_at->timezone($user->timezone));
        $response->assertSee(trans('messages.common.currently-leeching'));
        $response->assertDontSee(trans('messages.common.no-torrents-on-leech'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheUploadedPage(): void
    {
        $user = factory(User::class)->create();

        factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-uploaded-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheSeedingPage(): void
    {
        $user = factory(User::class)->create();

        factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-seeding-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function testGuestGetsRedirectedToTheLoginPageWhenTryingToAccessTheLeechingPage(): void
    {
        $user = factory(User::class)->create();

        factory(Torrent::class, 2)->create(['uploader_id' => $user->id]);

        $response = $this->get(route('user-torrents.show-leeching-torrents', $user));

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }
}
