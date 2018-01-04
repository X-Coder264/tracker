<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Http\Models\User;
use Illuminate\Http\Response;
use App\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ForgotPasswordControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testIndex()
    {
        $this->withoutExceptionHandling();

        $response = $this->get(route('password.request'));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('auth.passwords.email');
    }

    public function testNotificationIsSent()
    {
        $this->withoutExceptionHandling();

        Notification::fake();

        $user = factory(User::class)->create();

        $response = $this->from(route('password.request'))->post(route('password.email'), [
            'email' => $user->email,
        ]);

        $response->assertStatus(Response::HTTP_FOUND);
        $response->assertRedirect(route('password.request'));
        $response->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user) {
            $mailData = $notification->toMail($user)->toArray();

            $this->assertSame('info', $mailData['level']);
            $this->assertSame(__('messages.reset_password.subject'), $mailData['subject']);
            $this->assertSame(
                __('messages.reset_password.greeting', ['name' => $user->name]),
                $mailData['greeting']
            );
            $this->assertSame(
                __('messages.reset_password.salutation', ['site' => config('app.name')]),
                $mailData['salutation']
            );
            $this->assertSame(__('messages.reset_password.email-line-1'), $mailData['introLines'][0]);
            $this->assertSame(__('messages.reset_password.email-line-2'), $mailData['outroLines'][0]);
            $this->assertSame(__('messages.reset_password.action'), $mailData['actionText']);
            $this->assertSame(route('password.reset', $notification->token), $mailData['actionUrl']);

            return true;
        });
    }
}
