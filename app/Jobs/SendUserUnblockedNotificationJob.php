<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\AccountRestoredByAdminNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendUserUnblockedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $userId,
        public string $actorName
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        if ($user === null) {
            return;
        }

        $user->notify(new AccountRestoredByAdminNotification($this->actorName));
    }
}
