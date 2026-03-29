<?php

namespace App\Listeners;

use App\Events\UserBlockedByAdmin;
use App\Jobs\SendUserBlockedNotificationJob;

class QueueUserBlockedNotification
{
    public function handle(UserBlockedByAdmin $event): void
    {
        SendUserBlockedNotificationJob::dispatch($event->target->id, $event->actor->full_name);
    }
}
