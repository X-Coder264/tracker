<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Http\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testPasswordReset()
    {
        $this->withoutExceptionHandling();

        Event::fake();

        $user = factory(User::class)->create();
        $text = Str::random();
        $token = Hash::make($text);
        $newPassword = '1234567899';

        DB::table('password_resets')->insert(
            [
                'email'      => $user->email,
                'token'      => $token,
                'created_at' => Carbon::now(),
            ]
        );

        $response = $this->post(route('password.request'), [
            'email'                 => $user->email,
            'token'                 => $text,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home.index'));
        $response->assertSessionHas('status');
        $this->assertAuthenticatedAs($user);

        $updatedUser = User::findOrFail(1);
        $this->assertNotSame($user->password, $updatedUser->password);
        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));

        Event::assertDispatched(PasswordReset::class, function (PasswordReset $event) use ($user) {
            return $event->user->id === $user->id;
        });
    }
}
