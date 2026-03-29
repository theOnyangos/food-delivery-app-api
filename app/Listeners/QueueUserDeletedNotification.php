<?php

namespace App\Listeners;

use App\Events\UserDeletedByAdmin;
use App\Jobs\SendUserDeletedNotificationJob;

class QueueUserDeletedNotification
{
    public function handle(UserDeletedByAdmin $event): void
    {
        SendUserDeletedNotificationJob::dispatch($event->target->email, $event->actor->full_name);
    }
}
