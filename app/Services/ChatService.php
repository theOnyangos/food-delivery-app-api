<?php

namespace App\Services;

use App\Models\ChatConversation;
use App\Models\ChatConversationParticipant;
use App\Models\ChatMessage;
use App\Models\ChatMessageAttachment;
use App\Models\Media;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class ChatService
{
    public function __construct(
        protected ChatSettingsService $chatSettings,
        protected ChatSupportAllocationService $allocationService
    ) {}

    public function getOrCreateConversationForPartner(User $partnerUser): ChatConversation
    {
        if (! $partnerUser->hasLiveChatAccess() || ! $partnerUser->hasRole('Partner')) {
            throw new \InvalidArgumentException('Live chat is not available for your account.');
        }

        $conversation = ChatConversation::query()->where('vendor_user_id', $partnerUser->id)->first();
        if ($conversation) {
            return $conversation;
        }

        return $this->createConversation($partnerUser->id);
    }

    public function createConversation(string $partnerUserId, ?User $createdBy = null): ChatConversation
    {
        $partner = User::query()->find($partnerUserId);
        if (! $partner || ! $partner->hasRole('Partner')) {
            throw new \InvalidArgumentException('Partner user must have role Partner');
        }
        if (! $partner->hasLiveChatAccess()) {
            throw new \InvalidArgumentException('Live chat is not available for this partner account.');
        }

        $existing = ChatConversation::query()->where('vendor_user_id', $partnerUserId)->first();
        if ($existing) {
            if ($createdBy && $createdBy->hasAnyRole(['Super Admin', 'Admin'])) {
                $this->ensureParticipant($existing, $createdBy->id);
            }

            return $existing;
        }

        return DB::transaction(function () use ($partnerUserId, $createdBy) {
            $conversation = ChatConversation::query()->create([
                'vendor_user_id' => $partnerUserId,
                'status' => 'open',
            ]);

            $participantUserIds = $this->allocationService->getSupportUserIdsForVendor($partnerUserId);
            $participantUserIds[] = $partnerUserId;
            if ($createdBy && $createdBy->hasAnyRole(['Super Admin', 'Admin'])) {
                $participantUserIds[] = $createdBy->id;
            }
            $participantUserIds = array_unique($participantUserIds);

            foreach ($participantUserIds as $userId) {
                ChatConversationParticipant::query()->create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $userId,
                ]);
            }

            return $conversation;
        });
    }

    /**
     * Partners with role Partner and permission use live chat.
     */
    public function listEligiblePartnerUsersForChat(?string $search, int $perPage = 30): LengthAwarePaginator
    {
        $query = User::query()
            ->role('Partner')
            ->permission('use live chat');

        if ($search !== null && trim($search) !== '') {
            $raw = trim($search);
            $driver = DB::getDriverName();
            $query->where(function (Builder $w) use ($raw, $driver) {
                if ($driver === 'pgsql') {
                    $like = '%'.addcslashes($raw, '%_\\').'%';
                    $w->where('first_name', 'ilike', $like)
                        ->orWhere('last_name', 'ilike', $like)
                        ->orWhere('email', 'ilike', $like);
                } else {
                    $like = '%'.addcslashes(mb_strtolower($raw), '%_\\').'%';
                    $w->whereRaw('LOWER(first_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$like])
                        ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
                }
            });
        }

        return $query
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate($perPage);
    }

    public function deleteConversation(string $conversationId, User $actor): void
    {
        if (! $actor->hasAnyRole(['Super Admin', 'Admin'])) {
            abort(403, 'Only staff can delete conversations.');
        }

        $conversation = ChatConversation::query()->find($conversationId);
        if (! $conversation) {
            abort(404, 'Conversation not found.');
        }

        $conversation->delete();
    }

    private function ensureParticipant(ChatConversation $conversation, string $userId): void
    {
        $exists = ChatConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();
        if ($exists) {
            return;
        }

        ChatConversationParticipant::query()->create([
            'conversation_id' => $conversation->id,
            'user_id' => $userId,
        ]);
    }

    public function listConversationsForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        $participantIds = ChatConversationParticipant::query()
            ->where('user_id', $user->id)
            ->pluck('conversation_id');

        return ChatConversation::query()
            ->with(['vendorUser:id,first_name,middle_name,last_name,email', 'participants'])
            ->whereIn('id', $participantIds)
            ->orderByDesc('last_message_at')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function getConversation(string $conversationId, User $user): ?ChatConversation
    {
        $conversation = ChatConversation::query()
            ->with(['vendorUser', 'participants.user'])
            ->find($conversationId);
        if (! $conversation) {
            return null;
        }
        if (! $this->isParticipant($conversation, $user->id)) {
            return null;
        }

        return $conversation;
    }

    /**
     * @param  array<int, string>  $attachmentMediaIds
     */
    public function addMessage(ChatConversation $conversation, User $sender, ?string $body, array $attachmentMediaIds = []): ChatMessage
    {
        if (! $this->chatSettings->canSendMessage()) {
            throw new \InvalidArgumentException('Messages are not allowed outside working hours. '.($this->chatSettings->getSettings()['out_of_hours_message'] ?? ''));
        }
        if (! $this->isParticipant($conversation, $sender->id)) {
            throw new \InvalidArgumentException('You are not a participant of this conversation');
        }
        if ($sender->hasRole('Partner') && ! $sender->hasLiveChatAccess()) {
            throw new \InvalidArgumentException('Live chat is not available for your account.');
        }
        if (trim((string) $body) === '' && $attachmentMediaIds === []) {
            throw new \InvalidArgumentException('Message must have body or attachments');
        }

        return DB::transaction(function () use ($conversation, $sender, $body, $attachmentMediaIds) {
            $message = ChatMessage::query()->create([
                'conversation_id' => $conversation->id,
                'user_id' => $sender->id,
                'body' => $body ? trim($body) : null,
            ]);

            foreach ($attachmentMediaIds as $mediaId) {
                $media = Media::query()->where('id', $mediaId)->first();
                if ($media && $this->userOwnsMedia($sender, $media)) {
                    ChatMessageAttachment::query()->create([
                        'message_id' => $message->id,
                        'media_id' => $media->id,
                    ]);
                }
            }

            $conversation->update([
                'last_message_at' => $message->created_at,
            ]);

            return $message->load(['user:id,first_name,middle_name,last_name,email', 'attachments.media']);
        });
    }

    public function getMessages(string $conversationId, User $user, int $perPage = 20, ?int $page = null): LengthAwarePaginator
    {
        $conversation = $this->getConversation($conversationId, $user);
        if (! $conversation) {
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $perPage);
        }

        return ChatMessage::query()
            ->where('conversation_id', $conversation->id)
            ->with(['user:id,first_name,middle_name,last_name,email', 'attachments.media'])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function markConversationRead(string $conversationId, User $user): void
    {
        $participant = ChatConversationParticipant::query()
            ->where('conversation_id', $conversationId)
            ->where('user_id', $user->id)
            ->first();
        if ($participant) {
            $participant->update(['last_read_at' => now()]);
        }
    }

    public function isParticipant(ChatConversation $conversation, string $userId): bool
    {
        return ChatConversationParticipant::query()
            ->where('conversation_id', $conversation->id)
            ->where('user_id', $userId)
            ->exists();
    }

    private function userOwnsMedia(User $user, Media $media): bool
    {
        if ($media->model_type === User::class && $media->model_id === $user->id) {
            return true;
        }

        return false;
    }
}
