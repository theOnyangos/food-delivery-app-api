<?php

use App\Models\ChatConversationParticipant;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('notifications.{id}', function ($user, $id) {
    return (string) $user->id === (string) $id;
});

Broadcast::channel('chat.conversation.{id}', function ($user, $id) {
    return ChatConversationParticipant::query()
        ->where('conversation_id', $id)
        ->where('user_id', $user->id)
        ->exists();
});
