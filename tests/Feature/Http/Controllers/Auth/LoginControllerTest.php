<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Contracts\Translation\Translator;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanViewTheLoginForm(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get($this->app->make(UrlGenerator::class)->route('login'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.login');
    }

    public function testUserWhoHas2FADisabledCanLoginWithCorrectCredentials(): void
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('login'), [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('home'));
        $this->assertAuthenticatedAs($user);
    }

    public function testUserWhoHas2FAEnabledGetsRedirectedTo2FAFormWhenInputtingCorrectCredentials(): void
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->is_two_factor_enabled = true;
        $user->save();

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('login'), [
            'email'    => $email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('2fa.show_form'));
        $response->assertSessionHas('2fa_user_id', function ($encryptedUserId) use ($user) {
            return ! empty($encryptedUserId) && $this->app->make(Encrypter::class)->decrypt($encryptedUserId) === $user->id;
        });
        $response->assertSessionHas('2fa_remember_me', true);
        $this->assertGuest();
    }

    public function testBannedUserCannotLogin(): void
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->banned = true;
        $user->save();

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('login'), [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect($urlGenerator->route('login'));
        $this->assertGuest();
        $response->assertSessionHas('error', $this->app->make(Translator::class)->trans('messages.user.banned'));
    }

    public function testUserCannotLoginWithIncorrectPassword(): void
    {
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make('test123'),
        ]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('login'))->post($urlGenerator->route('login'), [
            'email' => $user->email,
            'password' => 'invalid-xyz',
        ]);

        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testUserCannotLoginWithEmailThatDoesNotExist(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('login'))->post($urlGenerator->route('login'), [
            'email' => 'test123@gmail.com',
            'password' => 'xyz-invalid-password',
        ]);

        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testRememberMeFunctionality(): void
    {
        $password = 'test1234';

        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make($password),
        ]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->post($urlGenerator->route('login'), [
            'email' => $user->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('home'));
        $response->assertCookie(auth()->guard()->getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
        $this->assertAuthenticatedAs($user);
    }

    public function testEmailIsRequired(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('login'))->post($urlGenerator->route('login'), $this->validParams([
            'email' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testPasswordIsRequired(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $response = $this->from($urlGenerator->route('login'))->post($urlGenerator->route('login'), $this->validParams([
            'password' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testLoggedInUserGetsRedirectedToTheHomePage(): void
    {
        $urlGenerator = $this->app->make(UrlGenerator::class);

        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get($urlGenerator->route('login'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect($urlGenerator->route('home'));
    }

    public function testUserCanLogout(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->post($this->app->make(UrlGenerator::class)->route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function testUserCannotMakeMoreThanFiveAttemptsInOneMinute(): void
    {
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make('test123'),
        ]);

        $urlGenerator = $this->app->make(UrlGenerator::class);

        foreach (range(0, 5) as $x) {
            $response = $this->from($urlGenerator->route('login'))->post($urlGenerator->route('login'), [
                'email' => $user->email,
                'password' => 'test123-invalid-password',
            ]);
        }

        $response->assertRedirect($urlGenerator->route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertStringContainsString(
            'Too many login attempts.',
            collect(
                $response
                ->baseResponse
                ->getSession()
                ->get('errors')
                ->getBag('default')
                ->get('email')
            )->first()
        );
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    private function validParams(array $overrides = []): array
    {
        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $this->app->make(Hasher::class)->make($password);
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        return array_merge([
            'email'    => $user->email,
            'password' => $password,
        ], $overrides);
    }
}
