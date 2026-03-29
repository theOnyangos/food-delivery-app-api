<?php

namespace App\Jobs;

use App\Notifications\AccountRemovedByAdminNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Notification;

class SendUserDeletedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $email,
        public string $actorName
    ) {}

    public function handle(): void
    {
        Notification::route('mail', $this->email)
            ->notify(new AccountRemovedByAdminNotification($this->actorName));
    }
}
