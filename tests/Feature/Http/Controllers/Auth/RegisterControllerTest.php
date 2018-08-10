<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanViewTheRegistrationForm()
    {
        $this->withoutExceptionHandling();

        factory(Locale::class)->create();
        $response = $this->get(route('register'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.register');
        $response->assertViewHas('locales');
    }

    public function testUserCanRegister()
    {
        $this->withoutExceptionHandling();

        $realDispatcher = $this->app->make(Dispatcher::class);

        Event::fake();

        // this is needed as the slug for the user is generated in an observer event, otherwise the INSERT query would fail
        // as the user slug cannot be null
        Model::setEventDispatcher($realDispatcher);

        $name = 'test name';
        $email = 'test@gmail.com';
        $password = 'test password';
        $locale = factory(Locale::class)->create();
        $timezone = 'Europe/Zagreb';

        $response = $this->post(route('register'), [
            'name'                  => $name,
            'password'              => $password,
            'password_confirmation' => $password,
            'email'                 => $email,
            'locale'                => $locale->id,
            'timezone'              => $timezone,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));

        $this->assertSame(1, User::count());

        $user = User::firstOrFail();
        $this->assertSame($user->name, $name);
        $this->assertSame($user->email, $email);
        $this->assertTrue(Hash::check($password, $user->password));
        $this->assertSame($user->timezone, $timezone);
        $this->assertTrue($user->language->is($locale));
        $this->assertNull($user->passkey);
        $this->assertNotNull($user->slug);
        $this->assertAuthenticatedAs($user);
        Event::assertDispatched(Registered::class, function (Registered $event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function testNameIsRequired()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'name' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testNameMustBeLessThan256CharsLong()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'name' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testNameMustBeUnique()
    {
        $user = factory(User::class)->create();
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'name' => $user->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('name');
        $this->assertSessionHasOldInput();
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function testEmailIsRequired()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'email' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeLessThan256CharsLong()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'email' => str_repeat('X', 256),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeUnique()
    {
        $user = factory(User::class)->create();
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'email' => $user->name,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(1, User::count());
        $this->assertGuest();
    }

    public function testEmailMustBeAValidEmail()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'email' => 'xyz',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('email');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordIsRequired()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'password' => '',
            'password_confirmation' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordMustHaveAtLeast8Chars()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'password' => str_repeat('X', 7),
            'password_confirmation' => str_repeat('X', 7),
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testPasswordMustBeConfirmed()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'password_confirmation' => '1234567',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('password');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLocaleIsRequired()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'locale' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('locale');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLocaleMustBeAValidLocale()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'locale' => 2,
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('locale');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testTimezoneIsRequired()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'timezone' => '',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('timezone');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testTimezoneMustBeAValidTimezone()
    {
        $response = $this->from(route('register'))->post(route('register'), $this->validParams([
            'timezone' => 'Europe/Zagre',
        ]));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('register'));
        $response->assertSessionHasErrors('timezone');
        $this->assertSessionHasOldInput();
        $this->assertSame(0, User::count());
        $this->assertGuest();
    }

    public function testLoggedInUserGetsRedirectedToTheHomePage()
    {
        $user = factory(User::class)->create();
        $this->actingAs($user);
        $response = $this->get(route('register'));
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
    }

    private function assertSessionHasOldInput()
    {
        $this->assertTrue(session()->hasOldInput('name'));
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
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
            'name'                  => 'test name',
            'email'                 => 'test@gmail.com',
            'password'              => '12345678',
            'password_confirmation' => '12345678',
            'locale'                => $locale->id,
            'timezone'              => 'Europe/Zagreb',
        ], $overrides);
    }
}
