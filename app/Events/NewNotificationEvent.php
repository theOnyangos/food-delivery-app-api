<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class NewNotificationEvent implements ShouldBroadcastNow
{
    use SerializesModels;

    public Notification $notification;

    public string $userId;

    public function __construct(Notification $notification)
    {
        $this->notification = $notification;
        $this->userId = (string) $notification->user_id;
    }

    public function broadcastOn(): Channel
    {
        return new PrivateChannel("notifications.{$this->userId}");
    }

    public function broadcastWith(): array
    {
        return [
            'success' => true,
            'data' => [
                'id' => $this->notification->id,
                'user_id' => $this->notification->user_id,
                'type' => $this->notification->type,
                'data' => $this->notification->data,
                'is_read' => $this->notification->is_read,
                'read_at' => $this->notification->read_at,
                'created_at' => $this->notification->created_at,
            ],
            'timestamp' => now()->toDateTimeString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.new';
    }
}
