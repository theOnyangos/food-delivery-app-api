<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\PosSaleCompleted;
use App\Jobs\SendPosReceiptEmailJob;

class QueuePosReceiptEmail
{
    public function handle(PosSaleCompleted $event): void
    {
        $email = $event->sale->customer_email;
        if ($email === null || $email === '') {
            return;
        }

        SendPosReceiptEmailJob::dispatch((string) $event->sale->id);
    }
}
