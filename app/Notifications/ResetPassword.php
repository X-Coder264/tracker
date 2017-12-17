<?php

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

    /**
     * Create a notification instance.
     *
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's channels.
     *
     * @param mixed $notifiable
     *
     * @return array
     */
    public function via($notifiable): array
    {
        return ['mail'];
    }

    /**
     * Build the mail representation of the notification.
     *
     * @param mixed $notifiable
     *
     * @return MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject(__('messages.reset_password.subject'))
            ->greeting(__('messages.reset_password.greeting', ['name' => $notifiable->name]))
            ->salutation(__('messages.reset_password.salutation', ['site' => config('app.name')]))
            ->line(__('messages.reset_password.email-line-1'))
            ->action(__('messages.reset_password.action'), route('password.reset', $this->token))
            ->line(__('messages.reset_password.email-line-2'));
    }
}
