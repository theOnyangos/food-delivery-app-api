<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public string $conversationId,
        public string $userId,
        public bool $typing
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat.conversation.'.$this->conversationId);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'typing' => $this->typing,
        ];
    }

    public function broadcastAs(): string
    {
        return 'user.typing';
    }
}
