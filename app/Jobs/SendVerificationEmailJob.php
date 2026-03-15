<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\VerifyAccountNotification;
use App\Services\NotificationService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendVerificationEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public User $user,
        public string $verificationToken
    ) {}

    public function handle(NotificationService $notificationService): void
    {
        $url = url('/api/auth/verify-email/'.$this->verificationToken);
        $this->user->notify(new VerifyAccountNotification($url));

        $notificationService->create($this->user, 'verification_email_sent', [
            'title' => 'Verification email sent',
            'message' => 'A verification link has been sent to your email.',
            'user_id' => $this->user->id,
        ]);
    }
}
