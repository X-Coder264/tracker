<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Http\Middleware\SetUserLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

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

    public function testUpdate()
    {
        $this->withoutExceptionHandling();

        $this->withoutMiddleware(SetUserLocale::class);
        $user = factory(User::class)->create();
        $locale = factory(Locale::class)->create();
        $this->actingAs($user);
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';

        Cache::shouldReceive('forget')->once()->with('user.' . $user->id);
        Cache::shouldReceive('forget')->once()->with('user.' . $user->slug . '.locale');
        Cache::shouldReceive('forget')->once()->with('user.' . $user->passkey);

        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $user),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
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
        $this->assertSame($user->passkey, $updatedUser->passkey);
        $this->assertSame($user->remember_token, $updatedUser->remember_token);
        $this->assertSame($user->slug, $updatedUser->slug);
        $this->assertSame($locale->localeShort, $this->app->getLocale());
        $this->assertSame($locale->localeShort, $this->app->make('translator')->getLocale());
    }

    public function testNonLoggedInUserCannotUpdateAnything(): void
    {
        $this->withoutMiddleware(SetUserLocale::class);
        $user = factory(User::class)->create();
        $locale = factory(Locale::class)->create();
        $email = 'testtttt@gmail.com';
        $timezone = 'Europe/Paris';

        Cache::shouldReceive('forget')->never();

        $response = $this->from(route('login'))->put(
            route('users.update', $user),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
            ]
        );

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $updatedUser = User::findOrFail($user->id);
        $this->assertSame($user->name, $updatedUser->name);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertSame($user->locale_id, (int) $updatedUser->locale_id);
        $this->assertSame($user->timezone, $updatedUser->timezone);
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

        Cache::shouldReceive('forget')->never();

        $response = $this->from(route('users.edit', $user))->put(
            route('users.update', $anotherUser),
            [
                'email' => $email,
                'locale_id' => $locale->id,
                'timezone' => $timezone,
            ]
        );

        $response->assertStatus(Response::HTTP_FORBIDDEN);
        $anotherUserFresh = $anotherUser->fresh();
        $this->assertSame($anotherUser->name, $anotherUserFresh->name);
        $this->assertSame($anotherUser->email, $anotherUserFresh->email);
        $this->assertSame($anotherUser->locale_id, (int) $anotherUserFresh->locale_id);
        $this->assertSame($anotherUser->timezone, $anotherUserFresh->timezone);
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

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function validParams($overrides = []): array
    {
        $locale = factory(Locale::class)->create();

        return array_merge([
            'email' => 'test@gmail.com',
            'locale_id' => $locale->id,
            'timezone' => 'Europe/Zagreb',
        ], $overrides);
    }
}
