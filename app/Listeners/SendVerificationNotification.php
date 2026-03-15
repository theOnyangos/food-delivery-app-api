<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Jobs\SendVerificationEmailJob;

class SendVerificationNotification
{
    public function handle(UserRegistered $event): void
    {
        SendVerificationEmailJob::dispatch($event->user, $event->verificationToken);
    }
}
