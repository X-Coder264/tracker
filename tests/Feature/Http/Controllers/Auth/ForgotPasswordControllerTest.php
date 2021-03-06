<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Auth;

use App\Notifications\ResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Hashing\Hasher;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ForgotPasswordControllerTest extends TestCase
{
    use DatabaseTransactions;

    public function testUserCanViewTheEmailPasswordForm(): void
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('password.request'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.passwords.email');
    }

    public function testUserCannotViewTheEmailPasswordFormWhenAuthenticated(): void
    {
        $user = UserFactory::new()->make();
        $response = $this->actingAs($user)->get(route('password.request'));
        $response->assertRedirect(route('home'));
    }

    public function testUserReceivesAnEmailWithAPasswordResetLink(): void
    {
        $this->withoutExceptionHandling();

        $notificationFake = Notification::fake();

        $user = UserFactory::new()->create();

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        $token = DB::table('password_resets')->first();
        $this->assertNotNull($token);

        $notificationFake->assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user, $token) {
            $mailData = $notification->toMail($user)->toArray();

            $this->assertSame('info', $mailData['level']);
            $this->assertSame(trans('messages.reset_password.subject'), $mailData['subject']);
            $this->assertSame(
                trans('messages.reset_password.greeting', ['name' => $user->name]),
                $mailData['greeting']
            );
            $this->assertSame(
                trans('messages.reset_password.salutation', ['site' => config('app.name')]),
                $mailData['salutation']
            );
            $this->assertSame(trans('messages.reset_password.email-line-1'), $mailData['introLines'][0]);
            $this->assertSame(trans('messages.reset_password.email-line-2'), $mailData['outroLines'][0]);
            $this->assertSame(trans('messages.reset_password.action'), $mailData['actionText']);
            $this->assertSame(route('password.reset', $notification->token), $mailData['actionUrl']);

            $this->assertTrue($this->app->make(Hasher::class)->check($notification->token, $token->token));

            return true;
        });
    }

    public function testUserDoesNotReceiveEmailWhenNotRegistered(): void
    {
        $notificationFake = Notification::fake();

        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');

        $notificationFake->assertNothingSent();
    }

    public function testEmailIsRequired(): void
    {
        $response = $this->from(route('password.email'))->post(route('password.email'), []);
        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
    }

    public function testEmailIsAValidEmail(): void
    {
        $response = $this->from(route('password.email'))->post(route('password.email'), [
            'email' => 'testxyz',
        ]);

        $response->assertRedirect(route('password.email'));
        $response->assertSessionHasErrors('email');
    }
}
