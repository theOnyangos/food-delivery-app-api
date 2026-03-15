<?php

namespace App\Services;

use App\Events\NewNotificationEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class NotificationService
{
    public function getSuperAdminUser(): ?User
    {
        $email = config('app.super_admin_email');

        if (empty($email)) {
            return null;
        }

        return User::query()->where('email', $email)->first();
    }

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getAdminUsers(): \Illuminate\Support\Collection
    {
        return User::role(['Super Admin', 'Admin'])->get();
    }

    public function create(User $user, string $type, array $data): Notification
    {
        $notificationData = array_merge($data, [
            'title' => $data['title'] ?? ucfirst(str_replace('_', ' ', $type)),
            'message' => $data['message'] ?? 'You have a new notification.',
        ]);

        $notification = Notification::query()->create([
            'id' => Str::uuid(),
            'user_id' => $user->id,
            'type' => $type,
            'data' => $notificationData,
            'is_read' => false,
            'read_at' => null,
        ]);

        try {
            broadcast(new NewNotificationEvent($notification));
        } catch (\Throwable $e) {
            Log::warning('Failed to broadcast notification: '.$e->getMessage());
        }

        return $notification;
    }
}
