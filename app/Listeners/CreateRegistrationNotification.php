<?php

namespace App\Listeners;

use App\Events\UserRegistered;
use App\Services\NotificationService;

class CreateRegistrationNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(UserRegistered $event): void
    {
        $this->notificationService->create($event->user, 'new_registration', [
            'title' => 'Welcome',
            'message' => 'Please verify your email to get started.',
        ]);

        $admins = $this->notificationService->getAdminUsers();

        foreach ($admins as $admin) {
            $this->notificationService->create($admin, 'admin_new_registration', [
                'title' => 'New registration',
                'message' => 'A new user has registered.',
                'user_id' => $event->user->id,
                'user_email' => $event->user->email,
            ]);
        }
    }
}
