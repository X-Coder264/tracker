<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Models\Peer;
use App\Models\User;
use App\Models\Locale;
use App\Models\Snatch;
use App\Models\Torrent;
use Illuminate\Http\Response;
use App\Services\SizeFormatter;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testEdit()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('users.edit');
        $response->assertViewHas(['user', 'locales']);
    }

    public function testLoggedInUserCanSeeOnlyHisEditPage(): void
    {
        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('users.edit', $anotherUser));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('users.edit', $user));
    }

    public function testNonLoggedInUserCannotSeeAnyUserEditPage(): void
    {
        $user = factory(User::class)->create();
        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['timezone' => 'Europe/Zagreb']);

        $torrentOne = factory(Torrent::class)->create(['size' => 500, 'uploader_id' => $user->id]);
        $torrentTwo = factory(Torrent::class)->create(['size' => 2500]);
        $torrentThree = factory(Torrent::class)->create(['size' => 1200, 'uploader_id' => $user->id]);
        $torrentFour = factory(Torrent::class)->create(['size' => 1800]);

        factory(Peer::class)->states('seeder')->create(['user_id' => $user->id, 'torrent_id' => $torrentOne->id]);
        factory(Peer::class)->states('seeder')->create(['user_id' => $user->id, 'torrent_id' => $torrentThree->id]);
        factory(Peer::class)->states('leecher')->create(['user_id' => $user->id, 'torrent_id' => $torrentTwo->id]);

        factory(Peer::class)->states('seeder')->create();
        factory(Peer::class)->states('leecher')->create();

        factory(Snatch::class)->states('snatched')->create(['user_id' => $user->id, 'torrent_id' => $torrentOne->id]);
        factory(Snatch::class)->create(['user_id' => $user->id, 'torrent_id' => $torrentTwo->id, 'left' => 5]);
        factory(Snatch::class)->states('snatched')->create(['user_id' => $user->id, 'torrent_id' => $torrentThree->id]);
        factory(Snatch::class)->states('snatched')->create(['user_id' => $user->id, 'torrent_id' => $torrentFour->id]);

        factory(Snatch::class)->states('snatched')->create(['torrent_id' => $torrentFour->id]);
        factory(Snatch::class)->create(['torrent_id' => $torrentFour->id, 'left' => 10005]);

        $this->actingAs($user);
        $response = $this->get(route('users.show', $user));

        $response->assertStatus(200);
        $response->assertViewIs('users.show');
        $response->assertViewHas(['user', 'timezone', 'totalSeedingSize', 'uploadedTorrentsCount', 'seedingTorrentPeersCount', 'leechingTorrentPeersCount', 'snatchesCount']);
        $this->assertTrue($user->is($response->viewData('user')));
        $this->assertSame(
            $this->app->make(SizeFormatter::class)->getFormattedSize(1700),
            $response->viewData('totalSeedingSize')
        );
        $this->assertSame(2, $response->viewData('uploadedTorrentsCount'));
        $this->assertSame(2, $response->viewData('seedingTorrentPeersCount'));
        $this->assertSame(1, $response->viewData('leechingTorrentPeersCount'));
        $this->assertSame(3, $response->viewData('snatchesCount'));
        $response->assertSee($user->uploaded);
        $response->assertSee($user->downloaded);
        $response->assertSee($user->last_seen_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i'));
        $response->assertSee($user->created_at->timezone('Europe/Zagreb')->format('d.m.Y. H:i'));
        $response->assertSee(route('user-torrents.show-uploaded-torrents', $user));
        $response->assertSee(route('user-torrents.show-seeding-torrents', $user));
        $response->assertSee(route('user-torrents.show-leeching-torrents', $user));
        $response->assertSee(route('user-snatches.show', $user));
    }

    public function testLoggedInUsersCanSeeProfilePagesOfOtherUsers(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['timezone' => 'US/Central']);
        $anotherUser = factory(User::class)->create(['timezone' => 'Europe/Zagreb']);
        $this->actingAs($user);
        $response = $this->get(route('users.show', $anotherUser));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('users.show');
        $response->assertViewHas(['user', 'timezone']);
        $this->assertTrue($anotherUser->is($response->original->user));
        $response->assertSee($anotherUser->uploaded);
        $response->assertSee($anotherUser->downloaded);
        $response->assertSee($anotherUser->last_seen_at->timezone('US/Central')->format('d.m.Y. H:i'));
        $response->assertSee($anotherUser->created_at->timezone('US/Central')->format('d.m.Y. H:i'));
    }

    public function testNonLoggedInUserCannotSeeAnyUserProfilePage(): void
    {
        $user = factory(User::class)->create();
        $response = $this->get(route('users.show', $user));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testUpdate()
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create(['torrents_per_page' => 20]);
        $locale = factory(Locale::class)->create();
        $this->actingAs($user);
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';
        $torrentsPerPage = 40;

        /** @var Repository $cache */
        $cache = $this->app->make(Repository::class);
        $cache->put('user.' . $user->id, 'test', 5);
        $cache->put('user.' . $user->slug . '.locale', 'test', 5);
        $cache->put('user.' . $user->passkey, 'test', 5);

        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
                'torrents_per_page' => $torrentsPerPage,
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHas('success');
        $updatedUser = User::findOrFail($user->id);
        $this->assertSame($user->name, $updatedUser->name);
        $this->assertSame($email, $updatedUser->email);
        $this->assertSame($locale->id, (int) $updatedUser->locale_id);
        $this->assertSame($timezone, $updatedUser->timezone);
        $this->assertSame($torrentsPerPage, (int) $updatedUser->torrents_per_page);
        $this->assertSame($user->passkey, $updatedUser->passkey);
        $this->assertSame($user->remember_token, $updatedUser->remember_token);
        $this->assertSame($user->slug, $updatedUser->slug);
        $this->assertSame($locale->localeShort, $this->app->getLocale());
        $this->assertSame($locale->localeShort, $this->app->make(Translator::class)->getLocale());

        $this->assertFalse($cache->has('user.' . $user->id));
        $this->assertFalse($cache->has('user.' . $user->slug . '.locale'));
        $this->assertFalse($cache->has('user.' . $user->passkey));
    }

    public function testNonLoggedInUserCannotUpdateAnything(): void
    {
        $this->withoutMiddleware(SetUserLocale::class);

        $user = factory(User::class)->create(['torrents_per_page' => 20]);
        $locale = factory(Locale::class)->create();
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';
        $torrentsPerPage = 40;

        Cache::shouldReceive('forget')->never();

        $response = $this->from(route('login'))->put(
            route('users.update', $user),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
                'torrents_per_page' => $torrentsPerPage,
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $updatedUser = User::findOrFail($user->id);
        $this->assertSame($user->name, $updatedUser->name);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertSame($user->locale_id, (int) $updatedUser->locale_id);
        $this->assertSame($user->timezone, $updatedUser->timezone);
        $this->assertSame($user->torrents_per_page, (int) $updatedUser->torrents_per_page);
        $this->assertSame($user->passkey, $updatedUser->passkey);
        $this->assertSame($user->remember_token, $updatedUser->remember_token);
        $this->assertSame($user->slug, $updatedUser->slug);
    }

    public function testUserCanUpdateOnlyHisOwnData(): void
    {
        $this->withoutMiddleware(SetUserLocale::class);

        $user = factory(User::class)->create();
        $anotherUser = factory(User::class)->create();
        $locale = factory(Locale::class)->create();
        $this->actingAs($user);
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';
        $torrentsPerPage = 40;

        Cache::shouldReceive('forget')->never();

        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $anotherUser),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
                'torrents_per_page' => $torrentsPerPage,
            ]
        );

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $anotherUserFresh = $anotherUser->fresh();
        $this->assertSame($anotherUser->name, $anotherUserFresh->name);
        $this->assertSame($anotherUser->email, $anotherUserFresh->email);
        $this->assertSame($anotherUser->locale_id, (int) $anotherUserFresh->locale_id);
        $this->assertSame($anotherUser->timezone, $anotherUserFresh->timezone);
        $this->assertSame($anotherUser->torrents_per_page, (int) $anotherUserFresh->torrents_per_page);
        $this->assertSame($anotherUser->passkey, $anotherUserFresh->passkey);
        $this->assertSame($anotherUser->remember_token, $anotherUserFresh->remember_token);
        $this->assertSame($anotherUser->slug, $anotherUserFresh->slug);
    }

    public function testEmailIsRequired()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'email' => '',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('email');
    }

    public function testEmailMustBeValid()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'email' => 'test xyz',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('email');
    }

    public function testEmailMustBeUnique()
    {
        $users = factory(User::class, 2)->create();
        $this->actingAs($users[0]);
        $response = $this->from(route('users.edit', $users[0]))->put(
            route('users.update', $users[0]),
            $this->validParams([
                'email' => $users[1]->email,
            ])
        );

        $response->assertRedirect(route('users.edit', $users[0]));
        $response->assertSessionHasErrors('email');
    }

    public function testLocaleIsRequired()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'locale_id' => '',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('locale_id');
    }

    public function testLocaleMustBeValid()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'locale_id' => 54841,
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('locale_id');
    }

    public function testTimezoneIsRequired()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'timezone' => '',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('timezone');
    }

    public function testTimezoneMustBeValid()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'timezone' => 'Europe/Zagre',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('timezone');
    }

    public function testTorrentsPerPageIsRequired()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'torrents_per_page' => '',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('torrents_per_page');
    }

    public function testTorrentsPerPageMustBeAValidInteger()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'torrents_per_page' => 'wtf',
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('torrents_per_page');
    }

    public function testTorrentsPerPageMustBeGreaterThanZero(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'torrents_per_page' => 0,
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('torrents_per_page');
    }

    public function testTorrentsPerPageMaxIsFifty(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            $this->validParams([
                'torrents_per_page' => 51,
            ])
        );

        $response->assertRedirect(route('users.edit', $user));
        $response->assertSessionHasErrors('torrents_per_page');
    }

    private function validParams(array $overrides = []): array
    {
        $locale = factory(Locale::class)->create();

        return array_merge([
            'email' => 'test@gmail.com',
            'locale_id' => $locale->id,
            'timezone' => 'Europe/Zagreb',
            'torrents_per_page' => 20,
        ], $overrides);
    }
}
