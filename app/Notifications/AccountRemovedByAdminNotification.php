<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountRemovedByAdminNotification extends Notification
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
            ->subject('Your account has been closed')
            ->line('Your account has been closed by an administrator ('.$this->actorName.').')
            ->line('If you did not expect this message, contact support.');
    }
}
