<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanViewTheLoginForm()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('login'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.login');
    }

    public function testUserCanLoginWithCorrectCredentials()
    {
        $this->withoutExceptionHandling();

        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $password;
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $response = $this->post(route('login'), [
            'email'    => $email,
            'password' => $password,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function testUserCannotLoginWithIncorrectPassword()
    {
        $user = factory(User::class)->create([
            'password' => 'test123',
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

    public function testUserCannotLoginWithEmailThatDoesNotExist()
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

    public function testRememberMeFunctionality()
    {
        $password = 'test1234';

        $user = factory(User::class)->create([
            'password' => $password,
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => $password,
            'remember' => 'on',
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
        $response->assertCookie(auth()->guard()->getRecallerName(), vsprintf('%s|%s|%s', [
            $user->id,
            $user->getRememberToken(),
            $user->password,
        ]));
        $this->assertAuthenticatedAs($user);
    }

    public function testEmailIsRequired()
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

    public function testPasswordIsRequired()
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

    public function testLoggedInUserGetsRedirectedToTheHomePage()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('login'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
    }

    public function testUserCanLogout()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }

    public function testUserCannotMakeMoreThanFiveAttemptsInOneMinute()
    {
        $user = factory(User::class)->create([
            'password' => 'test123',
        ]);

        foreach (range(0, 5) as $x) {
            $response = $this->from(route('login'))->post(route('login'), [
                'email' => $user->email,
                'password' => 'test123-invalid-password',
            ]);
        }

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertContains(
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

    /**
     * @param array $overrides
     *
     * @return array
     */
    private function validParams($overrides = []): array
    {
        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = $password;
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        return array_merge([
            'email'    => $user->email,
            'password' => $password,
        ], $overrides);
    }
}
