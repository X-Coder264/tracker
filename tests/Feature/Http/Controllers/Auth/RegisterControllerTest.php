<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\Invite;
use App\Models\User;
use Database\Factories\ConfigurationFactory;
use Database\Factories\InviteFactory;
use Database\Factories\LocaleFactory;
use Database\Factories\UserFactory;
use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegisterControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanViewTheRegistrationFormWhenTheRegistrationIsNotInviteOnly(): void
    {
        $this->withoutExceptionHandling();

        LocaleFactory::new()->create();
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();
        $response = $this->get($this->app->make(UrlGenerator::class)->route('register'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.register');
        $response->assertViewHas('locales');
        $response->assertViewHas('isRegistrationInviteOnly', false);
    }

    public function testUserCanViewTheRegistrationFormWhenTheRegistrationIsInviteOnly(): void
    {
        $this->withoutExceptionHandling();

        LocaleFactory::new()->create();
        ConfigurationFactory::new()->inviteOnlySignup()->create();
        $response = $this->get($this->app->make(UrlGenerator::class)->route('register'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.register');
        $response->assertViewHas('locales');
        $response->assertViewHas('isRegistrationInviteOnly', true);
    }

    public function testUserCanRegisterWhenTheRegistrationIsNotInviteOnly(): void
    {
        $this->withoutExceptionHandling();

        $realDispatcher = $this->app->make(Dispatcher::class);

        $fakeDispatcher = Event::fake();

        // this is needed as the slug for the user is generated in an observer event, otherwise the INSERT query would fail
        // as the user slug cannot be null
        Model::setEventDispatcher($realDispatcher);

        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $this->assertSame(0, User::count());

        $name = 'test name';
        $email = 'test@gmail.com';
        $password = 'test password';
        $locale = LocaleFactory::new()->create();
        $timezone = 'Europe/Zagreb';

        $response = $this->post($this->app->make(UrlGenerator::class)->route('register'), [
            'name'                  => $name,
            'password'              => $password,
            'password_confirmation' => $password,
            'email'                 => $email,
            'locale'                => $locale->id,
            'timezone'              => $timezone,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('home'));

        $this->assertSame(1, User::count());

        /** @var User $user */
        $user = User::firstOrFail();
        $this->assertSame($user->name, $name);
        $this->assertSame($user->email, $email);
        $this->assertTrue($this->app->make(Hasher::class)->check($password, $user->password));
        $this->assertSame($user->timezone, $timezone);
        $this->assertSame(0, $user->invites_amount);
        $this->assertTrue($user->language->is($locale));
        $this->assertNotEmpty($user->passkey);
        $this->assertSame(64, strlen($user->passkey));
        $this->assertNull($user->inviter_user_id);
        $this->assertNotNull($user->slug);
        $this->assertAuthenticatedAs($user);
        $fakeDispatcher->assertDispatched(Registered::class, function (Registered $event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function testUserCanRegisterWhenTheRegistrationIsInviteOnlyWithAValidInvite(): void
    {
        $this->withoutExceptionHandling();

        $realDispatcher = $this->app->make(Dispatcher::class);

        $fakeDispatcher = Event::fake();

        // this is needed as the slug for the user is generated in an observer event, otherwise the INSERT query would fail
        // as the user slug cannot be null
        Model::setEventDispatcher($realDispatcher);

        ConfigurationFactory::new()->inviteOnlySignup()->create();

        /** @var Invite $invite */
        $invite = InviteFactory::new()->create();

        $this->assertSame(1, User::count());

        $name = 'test name';
        $email = 'test@gmail.com';
        $password = 'test password';
        $locale = LocaleFactory::new()->create();
        $timezone = 'Europe/Zagreb';

        $response = $this->post($this->app->make(UrlGenerator::class)->route('register'), [
            'name'                  => $name,
            'password'              => $password,
            'password_confirmation' => $password,
            'email'                 => $email,
            'locale'                => $locale->id,
            'timezone'              => $timezone,
            'invite'                => $invite->code,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('home'));

        $this->assertSame(2, User::count());

        /** @var User $user */
        $user = User::latest('id')->firstOrFail();
        $this->assertSame($user->name, $name);
        $this->assertSame($user->email, $email);
        $this->assertTrue($this->app->make(Hasher::class)->check($password, $user->password));
        $this->assertSame($user->timezone, $timezone);
        $this->assertSame(0, $user->invites_amount);
        $this->assertTrue($user->language->is($locale));
        $this->assertNotEmpty($user->passkey);
        $this->assertSame(64, strlen($user->passkey));
        $this->assertTrue($user->inviter->is($invite->user));
        $this->assertNotNull($user->slug);
        $this->assertAuthenticatedAs($user);
        $fakeDispatcher->assertDispatched(Registered::class, function (Registered $event) use ($user) {
            return $event->user->id === $user->id;
        });

        $this->assertNull(Invite::where('code', '=', $invite->code)->first());
    }

    public function testNameIsRequired(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'name' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testNameMustBeLessThan256CharsLong(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'name' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testNameMustBeUnique(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $user = UserFactory::new()->create();
        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'name' => $user->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSessionHasOldInput();
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function testEmailIsRequired(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'email' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeLessThan256CharsLong(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'email' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeUnique(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $user = UserFactory::new()->create();
        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'email' => $user->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeAValidEmail(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'email' => 'xyz',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordIsRequired(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'password' => '',
            'password_confirmation' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordMustHaveAtLeast8Chars(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'password' => str_repeat('X', 7),
            'password_confirmation' => str_repeat('X', 7),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordMustBeConfirmed(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'password_confirmation' => '1234567',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLocaleIsRequired(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'locale' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('locale');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLocaleMustBeAValidLocale(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'locale' => 2,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('locale');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testTimezoneIsRequired(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'timezone' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('timezone');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testTimezoneMustBeAValidTimezone(): void
    {
        ConfigurationFactory::new()->nonInviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'timezone' => 'Europe/Zagre',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('timezone');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testInviteIsRequiredIfTheRegistrationIsInviteOnly(): void
    {
        ConfigurationFactory::new()->inviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'invite' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('invite');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testInviteMustBeValidIfTheRegistrationIsInviteOnly(): void
    {
        ConfigurationFactory::new()->inviteOnlySignup()->create();

        $response = $this->from($this->app->make(UrlGenerator::class)->route('register'))->post($this->app->make(UrlGenerator::class)->route('register'), $this->validParams([
            'invite' => 'foo bar invalid',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertSessionHasErrors('invite');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLoggedInUserGetsRedirectedToTheHomePage(): void
    {
        $user = UserFactory::new()->create();
        $this->actingAs($user);
        $response = $this->get($this->app->make(UrlGenerator::class)->route('register'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($this->app->make(UrlGenerator::class)->route('home'));
    }

    private function assertSessionHasOldInput(): void
    {
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
    }

    private function validParams(array $overrides = []): array
    {
        $locale = LocaleFactory::new()->create();

        return array_merge([
            'name'                  => 'test name',
            'email'                 => 'test@gmail.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
            'locale'                => $locale->id,
            'timezone'              => 'Europe/Zagreb',
        ], $overrides);
    }
}
