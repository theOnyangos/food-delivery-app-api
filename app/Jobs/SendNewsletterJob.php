<?php

namespace App\Jobs;

use App\Mail\NewsletterMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendNewsletterJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $bodyHtml
    ) {}

    public function handle(): void
    {
        Log::info('SendNewsletterJob: started', ['subject' => $this->subject]);

        try {
            $emails = NewsletterSubscriber::query()
                ->subscribed()
                ->pluck('email');

            $count = 0;
            foreach ($emails as $email) {
                Mail::to($email)->send(new NewsletterMail($this->subject, $this->bodyHtml));
                $count++;
            }

            Log::info('SendNewsletterJob: completed', ['subject' => $this->subject, 'sent_count' => $count]);
        } catch (\Throwable $e) {
            Log::error('SendNewsletterJob: failed', [
                'subject' => $this->subject,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
