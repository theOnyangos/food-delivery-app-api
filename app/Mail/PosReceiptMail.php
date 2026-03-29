<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\PosSale;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PosReceiptMail extends Mailable
{
    use SerializesModels;

    public function __construct(
        public readonly PosSale $sale
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'ASL Order — '.$this->sale->receipt_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.pos-receipt',
        );
    }
}
