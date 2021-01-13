<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Http\Middleware\SetUserLocale;
use App\Models\User;
use App\Services\SizeFormatter;
use Database\Factories\LocaleFactory;
use Database\Factories\PeerFactory;
use Database\Factories\SnatchFactory;
use Database\Factories\TorrentFactory;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Session\SessionManager;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testEdit()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create();
        $this->actingAs($user);
        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('users.edit');
        $response->assertViewHas(['user', 'locales']);
    }

    public function testLoggedInUserCanSeeOnlyHisEditPage(): void
    {
        $user = UserFactory::new()->create();
        $anotherUser = UserFactory::new()->create();
        $this->actingAs($user);
        $response = $this->get(route('users.edit', $anotherUser));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('users.edit', $user));
    }

    public function testNonLoggedInUserCannotSeeAnyUserEditPage(): void
    {
        $user = UserFactory::new()->create();
        $response = $this->get(route('users.edit', $user));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testShow(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['timezone' => 'Europe/Zagreb']);

        $torrentOne = TorrentFactory::new()->create(['size' => 500, 'uploader_id' => $user->id]);
        $torrentTwo = TorrentFactory::new()->create(['size' => 2500]);
        $torrentThree = TorrentFactory::new()->create(['size' => 1200, 'uploader_id' => $user->id]);
        $torrentFour = TorrentFactory::new()->create(['size' => 1800]);

        PeerFactory::new()->seeder()->create(['user_id' => $user->id, 'torrent_id' => $torrentOne->id]);
        PeerFactory::new()->seeder()->create(['user_id' => $user->id, 'torrent_id' => $torrentThree->id]);
        PeerFactory::new()->leecher()->create(['user_id' => $user->id, 'torrent_id' => $torrentTwo->id]);

        PeerFactory::new()->seeder()->create();
        PeerFactory::new()->leecher()->create();

        SnatchFactory::new()->snatched()->create(['user_id' => $user->id, 'torrent_id' => $torrentOne->id]);
        SnatchFactory::new()->create(['user_id' => $user->id, 'torrent_id' => $torrentTwo->id, 'left' => 5]);
        SnatchFactory::new()->snatched()->create(['user_id' => $user->id, 'torrent_id' => $torrentThree->id]);
        SnatchFactory::new()->snatched()->create(['user_id' => $user->id, 'torrent_id' => $torrentFour->id]);

        SnatchFactory::new()->snatched()->create(['torrent_id' => $torrentFour->id]);
        SnatchFactory::new()->create(['torrent_id' => $torrentFour->id, 'left' => 10005]);

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
        $response->assertSee(route('user-torrents.show-uploaded-torrents', $user));
        $response->assertSee(route('user-torrents.show-seeding-torrents', $user));
        $response->assertSee(route('user-torrents.show-leeching-torrents', $user));
        $response->assertSee(route('user-snatches.show', $user));
    }

    public function testLoggedInUsersCanSeeProfilePagesOfOtherUsers(): void
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['timezone' => 'US/Central']);
        $anotherUser = UserFactory::new()->create(['timezone' => 'Europe/Zagreb']);
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
        $user = UserFactory::new()->create();
        $response = $this->get(route('users.show', $user));

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
    }

    public function testUpdate()
    {
        $this->withoutExceptionHandling();

        $user = UserFactory::new()->create(['torrents_per_page' => 20]);
        $locale = LocaleFactory::new()->create();
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

        $user = UserFactory::new()->create(['torrents_per_page' => 20]);
        $locale = LocaleFactory::new()->create();
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';
        $torrentsPerPage = 40;

        // resolve the real cache implementation before we mock it to avoid
        // getting that no expectations were specified on the mock driver method which the middleware calls
        $cacheFactory = $this->app->make(CacheFactory::class);
        $startSessionMiddleware = new StartSession(
            $this->app->make(SessionManager::class),
            function () use ($cacheFactory): CacheFactory {
                return $cacheFactory;
            }
        );
        $this->app->instance(StartSession::class, $startSessionMiddleware);

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

        $user = UserFactory::new()->create();
        $anotherUser = UserFactory::new()->create();
        $locale = LocaleFactory::new()->create();
        $this->actingAs($user);
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';
        $torrentsPerPage = 40;

        // resolve the real cache implementation before we mock it to avoid
        // getting that no expectations were specified on the mock driver method which the middleware calls
        $cacheFactory = $this->app->make(CacheFactory::class);
        $startSessionMiddleware = new StartSession(
            $this->app->make(SessionManager::class),
            function () use ($cacheFactory): CacheFactory {
                return $cacheFactory;
            }
        );
        $this->app->instance(StartSession::class, $startSessionMiddleware);

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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $users = UserFactory::new()->count(2)->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $user = UserFactory::new()->create();
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
        $locale = LocaleFactory::new()->create();

        return array_merge([
            'email' => 'test@gmail.com',
            'locale_id' => $locale->id,
            'timezone' => 'Europe/Zagreb',
            'torrents_per_page' => 20,
        ], $overrides);
    }
}
