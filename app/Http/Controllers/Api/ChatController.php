<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Requests\SendChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessageAttachment;
use App\Services\ChatService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ChatController extends Controller
{
    public function __construct(
        protected ChatService $chatService,
        protected NotificationService $notificationService
    ) {}

    /**
     * Staff: list partner users eligible for live chat.
     */
    public function indexVendorUsers(Request $request): JsonResponse
    {
        $user = $request->user();
        if (! $user || ! $user->hasAnyRole(['Super Admin', 'Admin'])) {
            return response()->json(['success' => false, 'message' => 'Forbidden'], 403);
        }

        try {
            $search = $request->query('search');
            $search = is_string($search) ? $search : null;
            $perPage = min(50, max(5, (int) $request->query('per_page', 30)));
            $paginator = $this->chatService->listEligiblePartnerUsersForChat($search, $perPage);

            $data = collect($paginator->items())->map(function ($vendorUser) {
                return [
                    'id' => $vendorUser->id,
                    'first_name' => $vendorUser->first_name,
                    'last_name' => $vendorUser->last_name,
                    'email' => $vendorUser->email,
                    'company_name' => null,
                ];
            })->values()->all();

            return response()->json([
                'success' => true,
                'data' => $data,
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error listing chat vendor users: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to list vendor users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroyConversation(Request $request, string $id): JsonResponse
    {
        try {
            $this->chatService->deleteConversation($id, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Conversation deleted.',
            ], 200);
        } catch (HttpException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Exception $e) {
            Log::error('Error deleting chat conversation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexConversations(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = (int) $request->query('per_page', config('chat.conversations_per_page', 15));
            $paginator = $this->chatService->listConversationsForUser($user, $perPage);

            return response()->json([
                'success' => true,
                'message' => 'Conversations retrieved successfully',
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error listing chat conversations: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to list conversations',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeConversation(Request $request): JsonResponse
    {
        try {
            $user = $request->user();
            if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
                $vendorUserId = $request->input('vendor_user_id');
                if (! $vendorUserId) {
                    return response()->json([
                        'success' => false,
                        'message' => 'vendor_user_id is required for support to create a conversation',
                    ], 422);
                }
                $conversation = $this->chatService->createConversation($vendorUserId, $user);
            } else {
                $conversation = $this->chatService->getOrCreateConversationForPartner($user);
            }

            $conversation->load(['vendorUser:id,first_name,middle_name,last_name,email', 'participants.user:id,first_name,middle_name,last_name,email']);

            if ($conversation->messages()->count() === 0) {
                $this->notifySupportOfNewConversation($conversation);
            }

            return response()->json([
                'success' => true,
                'message' => 'Conversation created or retrieved successfully',
                'data' => $conversation,
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error creating chat conversation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showConversation(Request $request, string $id): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = $this->chatService->getConversation($id, $user);
            if (! $conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $perPage = (int) $request->query('per_page', config('chat.messages_per_page', 20));
            $messages = $this->chatService->getMessages($id, $user, $perPage, (int) $request->query('page', 1));

            return response()->json([
                'success' => true,
                'message' => 'Conversation retrieved successfully',
                'data' => [
                    'conversation' => $conversation,
                    'messages' => $messages->items(),
                    'messages_meta' => [
                        'current_page' => $messages->currentPage(),
                        'last_page' => $messages->lastPage(),
                        'per_page' => $messages->perPage(),
                        'total' => $messages->total(),
                    ],
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error showing chat conversation: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve conversation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function indexMessages(Request $request, string $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $perPage = (int) $request->query('per_page', config('chat.messages_per_page', 20));
            $page = (int) $request->query('page', 1);
            $messages = $this->chatService->getMessages($conversationId, $user, $perPage, $page);

            return response()->json([
                'success' => true,
                'message' => 'Messages retrieved successfully',
                'data' => $messages->items(),
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error listing chat messages: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to list messages',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function storeMessage(SendChatMessageRequest $request, string $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = $this->chatService->getConversation($conversationId, $user);
            if (! $conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $body = $request->input('body');
            $attachmentIds = $request->input('attachment_ids', []);
            if (trim((string) $body) === '' && $attachmentIds === []) {
                return response()->json([
                    'success' => false,
                    'message' => 'Message must have body or attachments',
                ], 422);
            }

            $message = $this->chatService->addMessage(
                $conversation,
                $user,
                $body ? trim($body) : null,
                $attachmentIds
            );

            event(new MessageSent($message));
            $this->notifyParticipantsOfNewMessage($conversation, $message, $user);

            return response()->json([
                'success' => true,
                'message' => 'Message sent successfully',
                'data' => $this->formatMessageForResponse($message),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Exception $e) {
            Log::error('Error sending chat message: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send message',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function markRead(Request $request, string $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $conversation = $this->chatService->getConversation($conversationId, $user);
            if (! $conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            $this->chatService->markConversationRead($conversationId, $user);

            return response()->json([
                'success' => true,
                'message' => 'Conversation marked as read',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error marking conversation read: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as read',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function notifySupportOfNewConversation(ChatConversation $conversation): void
    {
        $vendorUserId = $conversation->vendor_user_id;
        $vendorUser = $conversation->vendorUser;
        $vendorName = $vendorUser ? $vendorUser->full_name : 'A vendor';

        $conversation->load('participants.user');
        $supportParticipants = $conversation->participants->filter(fn ($p) => (string) $p->user_id !== (string) $vendorUserId);

        foreach ($supportParticipants as $participant) {
            if ($participant->user) {
                $this->notificationService->create($participant->user, 'chat_conversation_started', [
                    'title' => 'New support conversation',
                    'message' => 'Conversation with '.$vendorName.' is available.',
                    'conversation_id' => $conversation->id,
                    'vendor_user_id' => $vendorUserId,
                ]);
            }
        }
    }

    private function notifyParticipantsOfNewMessage(ChatConversation $conversation, $message, $sender): void
    {
        $senderName = $sender->full_name ?: $sender->email;
        $preview = $message->body
            ? (strlen($message->body) > 80 ? substr($message->body, 0, 80).'...' : $message->body)
            : 'Sent an attachment';

        $conversation->load('participants.user');
        $otherParticipants = $conversation->participants->filter(fn ($p) => (string) $p->user_id !== (string) $sender->id);

        foreach ($otherParticipants as $participant) {
            if ($participant->user) {
                $this->notificationService->create($participant->user, 'chat_new_message', [
                    'title' => 'New chat message',
                    'message' => $senderName.': '.$preview,
                    'conversation_id' => $conversation->id,
                    'message_id' => $message->id,
                    'sender_id' => $sender->id,
                    'sender_name' => $senderName,
                ]);
            }
        }
    }

    public function getAttachmentServeUrl(Request $request, string $conversationId, string $mediaId): JsonResponse
    {
        $user = $request->user();
        $conversation = $this->chatService->getConversation($conversationId, $user);
        if (! $conversation) {
            return response()->json(['message' => 'Conversation not found.'], 404);
        }

        $attachment = ChatMessageAttachment::query()->where('media_id', $mediaId)
            ->whereHas('message', fn ($q) => $q->where('conversation_id', $conversationId))
            ->with('media')
            ->first();

        if (! $attachment || ! $attachment->media) {
            return response()->json(['message' => 'Attachment not found.'], 404);
        }

        $media = $attachment->media;
        $url = (string) preg_replace(
            '#(?<!:)//+#',
            '/',
            (string) URL::temporarySignedRoute(
                'uploads.serve',
                now()->addHour(),
                ['media' => $media->id],
                absolute: true
            )
        );

        return response()->json([
            'url' => $url,
            'expires_in_seconds' => 3600,
        ], 200, [], JSON_UNESCAPED_SLASHES);
    }

    private function formatMessageForResponse($message): array
    {
        $message->load(['user:id,first_name,middle_name,last_name,email', 'attachments.media']);
        $attachments = [];
        foreach ($message->attachments as $att) {
            $media = $att->media;
            $attachments[] = [
                'id' => $media->id,
                'file_name' => $media->file_name,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'serve_url_path' => '/api/chat/conversations/'.$message->conversation_id.'/attachments/'.$media->id.'/serve-url',
            ];
        }

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'user_id' => $message->user_id,
            'user' => $message->user ? [
                'id' => $message->user->id,
                'first_name' => $message->user->first_name,
                'last_name' => $message->user->last_name,
                'email' => $message->user->email,
            ] : null,
            'body' => $message->body,
            'attachments' => $attachments,
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
