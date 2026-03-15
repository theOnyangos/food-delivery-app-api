<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VerifyAccountNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $verificationUrl
    ) {}

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
            ->subject('Verify your ASL account')
            ->view('emails.verify-account', [
                'verificationUrl' => $this->verificationUrl,
                'firstName' => $firstName,
                'user' => $notifiable,
            ]);
    }
}
