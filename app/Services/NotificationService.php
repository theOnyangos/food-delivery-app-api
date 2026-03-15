<?php

namespace App\Services;

use App\Events\NewNotificationEvent;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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

    /**
     * @return \Illuminate\Support\Collection<int, User>
     */
    public function getAdminAndPartnerUsers(): \Illuminate\Support\Collection
    {
        return User::role(['Super Admin', 'Admin', 'Partner'])->get();
    }

    public function create(User $user, string $type, array $data): Notification
    {
        $notificationData = array_merge($data, [
            'title' => $data['title'] ?? ucfirst(str_replace('_', ' ', $type)),
            'message' => $data['message'] ?? 'You have a new notification.',
        ]);

        $notification = Notification::query()->create([
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

    public function markAsRead(Notification $notification): void
    {
        $notification->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
                'updated_at' => now(),
            ]);
    }

    public function getUnreadCount(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();
    }

    public function delete(Notification $notification): bool
    {
        return (bool) $notification->delete();
    }
}
