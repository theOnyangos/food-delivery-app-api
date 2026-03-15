<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\WelcomeAccountNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWelcomeEmailJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public User $user) {}

    public function handle(): void
    {
        $this->user->notify(new WelcomeAccountNotification());
    }
}
