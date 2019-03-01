<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPassword extends Notification
{
    /**
     * The password reset token.
     *
     * @var string
     */
    public $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('messages.reset_password.subject'))
            ->greeting(__('messages.reset_password.greeting', ['name' => $notifiable->name]))
            ->salutation(__('messages.reset_password.salutation', ['site' => config('app.name')]))
            ->line(__('messages.reset_password.email-line-1'))
            ->action(__('messages.reset_password.action'), route('password.reset', ['token' => $this->token]))
            ->line(__('messages.reset_password.email-line-2'));
    }
}
