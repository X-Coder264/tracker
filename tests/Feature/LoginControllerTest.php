<?php

namespace Tests\Feature;

use App\Http\Models\Locale;
use App\Http\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use Illuminate\Http\Response;

class LoginControllerTest extends TestCase
{
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

        $response = $this->post(action('Auth\LoginController@login'), [
            'email'    => $email,
            'password' => $password
        ]);
    }
}
