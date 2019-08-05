<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Models\User;
use App\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class LoginControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanViewTheLoginForm(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('login'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.login');
    }

    public function testUserCanLoginWithCorrectCredentials(): void
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

        $response = $this->post(route('login'), [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);
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

        $response = $this->post(route('login'), [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('error', trans('messages.user.banned'));
    }

    public function testUserCannotLoginWithIncorrectPassword(): void
    {
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make('test123'),
        ]);

        $response = $this->from(route('login'))->post(route('login'), [
            'email' => $user->email,
            'password' => 'invalid-xyz',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testUserCannotLoginWithEmailThatDoesNotExist(): void
    {
        $response = $this->from(route('login'))->post(route('login'), [
            'email' => 'test123@gmail.com',
            'password' => 'xyz-invalid-password',
        ]);

        $response->assertRedirect(route('login'));
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

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home'));
        $response->assertCookie(auth()->guard()->getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
        $this->assertAuthenticatedAs($user);
    }

    public function testEmailIsRequired(): void
    {
        $response = $this->from(route('login'))->post(route('login'), $this->validParams([
            'email' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testPasswordIsRequired(): void
    {
        $response = $this->from(route('login'))->post(route('login'), $this->validParams([
            'password' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertGuest();
    }

    public function testLoggedInUserGetsRedirectedToTheHomePage(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('login'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home'));
    }

    public function testUserCanLogout(): void
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function testUserCannotMakeMoreThanFiveAttemptsInOneMinute(): void
    {
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make('test123'),
        ]);

        foreach (range(0, 5) as $x) {
            $response = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'test123-invalid-password',
            ]);
        }

        $response->assertRedirect(route('login'));
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
