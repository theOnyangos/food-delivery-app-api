<?php

namespace App\Events;

use App\Models\ChatMessage;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use SerializesModels;

    public function __construct(
        public ChatMessage $message
    ) {}

    public function broadcastOn(): Channel
    {
        return new PrivateChannel('chat.conversation.'.$this->message->conversation_id);
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->load(['user:id,first_name,middle_name,last_name,email', 'attachments.media']);
        $attachments = [];
        foreach ($this->message->attachments as $att) {
            $media = $att->media;
            $attachments[] = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'serve_url_path' => '/api/chat/conversations/'.$this->message->conversation_id.'/attachments/'.$media->id.'/serve-url',
            ];
        }

        $user = $this->message->user;

        return [
            'id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'user_id' => $this->message->user_id,
            'user' => $user ? [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
            ] : null,
            'body' => $this->message->body,
            'attachments' => $attachments,
            'created_at' => $this->message->created_at?->toIso8601String(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
