<?php

namespace App\Listeners;

use App\Events\UserEmailVerified;
use App\Jobs\SendWelcomeEmailJob;

class SendWelcomeNotification
{
    public function handle(UserEmailVerified $event): void
    {
        SendWelcomeEmailJob::dispatch($event->user);
    }
}
