<?php

namespace App\Listeners;

use App\Events\UserUnblockedByAdmin;
use App\Jobs\SendUserUnblockedNotificationJob;

class QueueUserUnblockedNotification
{
    public function handle(UserUnblockedByAdmin $event): void
    {
        SendUserUnblockedNotificationJob::dispatch($event->target->id, $event->actor->full_name);
    }
}
