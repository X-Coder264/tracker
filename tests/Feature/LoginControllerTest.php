<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use App\Http\Models\Locale;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\RefreshDatabase;

class LoginControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $response = $this->get(route('login'));

        $response->assertStatus(Response::HTTP_OK);
    }

    public function testLogin()
    {
        $locale = factory(Locale::class)->create();
        $email = 'test@gmail.com';
        $password = '12345678';

        $user = new User();
        $user->name = 'test';
        $user->email = $email;
        $user->password = Hash::make($password, ['rounds' => 15]);
        $user->locale_id = $locale->id;
        $user->timezone = 'Europe/Zagreb';
        $user->save();

        $response = $this->post(route('login'), [
            'email'    => $email,
            'password' => $password
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
        $this->assertAuthenticatedAs($user);
    }

    public function testEmailIsRequired()
    {
        $response = $this->from(route('login'))->post(route('login'), ['email' => '']);
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
    }

    public function testPasswordIsRequired()
    {
        $response = $this->from(route('login'))->post(route('login'), ['password' => '']);
        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('password');
    }
}
