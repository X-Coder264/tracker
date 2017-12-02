<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegisterControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        factory(Locale::class)->create();
        $response = $this->get(route('register'));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testRegister()
    {
        $name = 'test name';
        $email = 'test@gmail.com';
        $locale = factory(Locale::class)->create();
        $timezone = 'Europe/Zagreb';

        $response = $this->post(action('Auth\RegisterController@register'), [
            'name'                  => $name,
            'password'              => 'test password',
            'password_confirmation' => 'test password',
            'email'                 => $email,
            'locale'                => $locale->id,
            'timezone'              => $timezone,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));

        $user = User::findOrFail(1);
        $this->assertSame($user->name, $name);
        $this->assertSame($user->email, $email);
        $this->assertSame(60, strlen($user->password));
        $this->assertSame((int) $user->locale_id, $locale->id);
        $this->assertSame($user->timezone, $timezone);
        $this->assertNull($user->passkey);
        $this->assertNotNull($user->slug);
    }
}
