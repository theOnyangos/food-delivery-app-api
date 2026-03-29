<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PosReceiptMail;
use App\Models\PosSale;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendPosReceiptEmailJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $posSaleId
    ) {}

    public function uniqueId(): string
    {
        return 'send-pos-receipt-email-'.$this->posSaleId;
    }

    /**
     * Prevent duplicate queue jobs for the same sale (e.g. double event dispatch).
     */
    public int $uniqueFor = 3600;

    public function handle(): void
    {
        DB::transaction(function (): void {
            $sale = PosSale::query()->lockForUpdate()->find($this->posSaleId);
            if ($sale === null) {
                return;
            }

            if ($sale->receipt_email_sent_at !== null) {
                return;
            }

            $email = $sale->customer_email;
            if ($email === null || $email === '') {
                return;
            }

            $sale->load('soldByUser');

            Mail::to($email)->send(new PosReceiptMail($sale));

            $sale->forceFill(['receipt_email_sent_at' => now()])->save();
        });
    }
}
