<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class ResetPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanResetPasswordWithAValidToken(): void
    {
        $this->withoutExceptionHandling();

        $user = factory(User::class)->create();

        $eventFake = Event::fake(PasswordReset::class);

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
        $this->assertTrue($this->app->make(Hasher::class)->check($newPassword, $updatedUser->password));

        $eventFake->assertDispatched(PasswordReset::class, function (PasswordReset $event) use ($user) {
            $this->assertTrue($user->is($event->user));

            return true;
        });
    }

    public function testUserCannotResetPasswordWithAnInvalidToken(): void
    {
        $this->withoutExceptionHandling();

        $password = 'invalid-123';
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make($password),
        ]);

        $response = $this->from(route('password.reset', 'invalid-token'))->post(route('password.request'), [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'test123456789',
            'password_confirmation' => 'test123456789',
        ]);

        $response->assertRedirect(route('password.reset', 'invalid-token'));
        $this->assertSame($user->email, $user->fresh()->email);
        $this->assertTrue($this->app->make(Hasher::class)->check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    public function testUserCannotResetPasswordWithoutProvidingANewPassword(): void
    {
        $password = 'fesfgertgreze';
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make($password),
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
        $this->assertTrue($this->app->make(Hasher::class)->check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    public function testUserCannotResetPasswordWithoutProvingAnEmail(): void
    {
        $password = 'fesfgertgreze';
        $user = factory(User::class)->create([
            'password' => $this->app->make(Hasher::class)->make($password),
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
        $this->assertTrue($this->app->make(Hasher::class)->check($password, $user->fresh()->password));
        $this->assertGuest();
    }

    private function getValidToken(User $user): string
    {
        return Password::broker()->createToken($user);
    }
}
