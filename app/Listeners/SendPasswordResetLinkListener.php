<?php

namespace App\Listeners;

use App\Events\PasswordResetLinkRequested;
use App\Jobs\SendPasswordResetLinkJob;

class SendPasswordResetLinkListener
{
    public function handle(PasswordResetLinkRequested $event): void
    {
        SendPasswordResetLinkJob::dispatch($event->email);
    }
}
