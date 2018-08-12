<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ResetPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testUserCanResetPasswordWithAValidToken()
    {
        $this->withoutExceptionHandling();

        Event::fake();

        // all events are faked so the sluggable observer won't be fired and the slug cannot be null
        $user = factory(User::class)->create(['slug' => 'test']);
        $token = $this->getValidToken($user);
        $newPassword = '1234567899';

        $response = $this->post(route('password.request'), [
            'email'                 => $user->email,
            'token'                 => $token,
            'password'              => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status');
        $this->assertAuthenticatedAs($user);

        $updatedUser = User::firstOrFail();
        $this->assertNotSame($user->password, $updatedUser->password);
        $this->assertSame($user->email, $updatedUser->email);
        $this->assertTrue(Hash::check($newPassword, $updatedUser->password));

        Event::assertDispatched(PasswordReset::class, function (PasswordReset $event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function testUserCannotResetPasswordWithAnInvalidToken()
    {
        $this->withoutExceptionHandling();

        $password = 'invalid-123';
        $user = factory(User::class)->create([
            'password' => $password,
        ]);

        $response = $this->from(route('password.reset', 'invalid-token'))->post(route('password.request'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'test123456789',
            'password_confirmation' => 'test123456789',
        ]);

        $response->assertRedirect(route('password.reset', 'invalid-token'));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    public function testUserCannotResetPasswordWithoutProvidingANewPassword()
    {
        $password = 'fesfgertgreze';
        $user = factory(User::class)->create([
            'password' => $password,
        ]);

        $token = $this->getValidToken($user);

        $response = $this->from(route('password.reset', $token))->post(route('password.request'), [
            'token' => $token,
            'email' => $user->email,
            'password' => '',
            'password_confirmation' => '',
        ]);

        $response->assertRedirect(route('password.reset', $token));
        $response->assertSessionHasErrors('password');
        $this->assertTrue(session()->hasOldInput('email'));
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    public function testUserCannotResetPasswordWithoutProvingAnEmail()
    {
        $password = 'fesfgertgreze';
        $user = factory(User::class)->create([
            'password' => $password,
        ]);

        $token = $this->getValidToken($user);

        $response = $this->from(route('password.reset', $token))->post(route('password.request'), [
            'token' => $token,
            'email' => '',
            'password' => 'gfhtjrtuj6urtjfjhfhfg',
            'password_confirmation' => 'gfhtjrtuj6urtjfjhfhfg',
        ]);

        $response->assertRedirect(route('password.reset', $token));
        $response->assertSessionHasErrors('email');
        $this->assertFalse(session()->hasOldInput('password'));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue(Hash::check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    /**
     * @param User $user
     *
     * @return string
     */
    private function getValidToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }
}
