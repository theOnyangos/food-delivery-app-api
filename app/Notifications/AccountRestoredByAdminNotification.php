<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRestoredByAdminNotification extends Notification
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
            ->subject('Your account access has been restored')
            ->line('Your account suspension has been lifted by an administrator ('.$this->actorName.').')
            ->line('You can sign in again using your existing credentials.');
    }
}
