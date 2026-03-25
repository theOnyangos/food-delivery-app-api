<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiConversation extends Model
{
    use HasUuids;

    protected $table = 'asl_ai_conversations';

    protected $fillable = [
        'user_id',
        'session_id',
        'type',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(AiMessage::class, 'conversation_id');
    }

    public function scopeForUser($query, string $userId, ?string $type = null, string $status = 'active')
    {
        $query->where('user_id', $userId)->where('status', $status);
        if ($type !== null) {
            $query->where('type', $type);
        }

        return $query->orderByDesc('updated_at');
    }

    public function scopeForSession($query, string $sessionId, string $type = 'vendor', string $status = 'active')
    {
        return $query->where('session_id', $sessionId)
            ->where('type', $type)
            ->where('status', $status)
            ->orderByDesc('updated_at');
    }

    public function archive(): bool
    {
        return $this->update(['status' => 'archived']);
    }
}
