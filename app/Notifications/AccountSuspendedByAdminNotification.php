<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountSuspendedByAdminNotification extends Notification
{

    public function __construct(
        public string $actorName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your account has been suspended')
            ->line('Your account has been suspended by an administrator ('.$this->actorName.').')
            ->line('You will not be able to sign in until your account is restored. If you believe this is a mistake, contact support.');
    }
}
