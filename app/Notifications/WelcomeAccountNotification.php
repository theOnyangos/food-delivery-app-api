<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeAccountNotification extends Notification
{
    use Queueable;

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $notifiable */
        $firstName = $notifiable->first_name ?? 'there';

        return (new MailMessage)
            ->subject('Welcome to ASL')
            ->view('emails.welcome-account', [
                'firstName' => $firstName,
                'user' => $notifiable,
            ]);
    }
}
