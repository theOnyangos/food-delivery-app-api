<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AIAgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AIAgentController extends Controller
{
    public function __construct(
        protected AIAgentService $aiAgentService
    ) {}

    /**
     * POST /api/ai/chat
     */
    public function chat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid|exists:asl_ai_conversations,id',
        ]);

        $user = $request->user();
        $userType = $user && $user->hasAnyRole(['Super Admin', 'Admin']) ? 'admin' : 'vendor';
        $context = [
            'user' => $user,
            'session_id' => $request->header('X-Session-Id') ?: session()->getId(),
            'ip' => $request->ip(),
            'user_type' => $userType,
        ];

        $result = $this->aiAgentService->sendMessage($request->only('message', 'conversation_id'), $context);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'conversation_id' => $result['conversation_id'],
                'message' => $result['message'],
                'metadata' => $result['metadata'] ?? [],
            ]);
        }

        $status = ($result['code'] ?? null) === 'DAILY_LIMIT_REACHED' ? 429 : 400;

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to process message',
            'code' => $result['code'] ?? null,
            'limit' => $result['limit'] ?? null,
            'remaining' => $result['remaining'] ?? null,
        ], $status);
    }

    /**
     * GET /api/ai/conversations
     */
    public function getConversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $userType = $user && $user->hasAnyRole(['Super Admin', 'Admin']) ? 'admin' : 'vendor';
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $status = $request->query('status', 'active');

        $conversations = $this->aiAgentService->getConversationsForUser(
            $user?->id,
            $sessionId,
            $userType,
            $status
        );

        return response()->json([
            'success' => true,
            'conversations' => $conversations,
        ]);
    }

    /**
     * GET /api/ai/conversations/{id}
     */
    public function getConversation(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $conversation = $this->aiAgentService->getConversationWithMessages($id, $user?->id, $sessionId);

        if (! $conversation) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation not found or access denied',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'conversation' => $conversation,
        ]);
    }

    /**
     * DELETE /api/ai/conversations/{id}
     */
    public function deleteConversation(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $conversation = $this->aiAgentService->getConversationWithMessages($id, $user?->id, $sessionId);

        if (! $conversation) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation not found or access denied',
            ], 404);
        }

        $conversation->archive();

        return response()->json([
            'success' => true,
            'message' => 'Conversation archived successfully',
        ]);
    }

    /**
     * POST /api/ai/conversations/{id}/regenerate
     */
    public function regenerate(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $conversation = $this->aiAgentService->getConversationWithMessages($id, $user?->id, $sessionId);

        if (! $conversation) {
            return response()->json([
                'success' => false,
                'error' => 'Conversation not found or access denied',
            ], 404);
        }

        $messages = $conversation->messages()->orderBy('created_at')->get();
        $lastUserMessage = $messages->reverse()->first(fn ($m) => $m->role === 'user');
        if (! $lastUserMessage) {
            return response()->json([
                'success' => false,
                'error' => 'No user message found to regenerate',
            ], 400);
        }

        $lastMessage = $messages->last();
        if ($lastMessage && $lastMessage->role === 'assistant') {
            $lastMessage->delete();
        }

        $userType = $user && $user->hasAnyRole(['Super Admin', 'Admin']) ? 'admin' : 'vendor';
        $context = [
            'user' => $user,
            'session_id' => $sessionId,
            'ip' => $request->ip(),
            'user_type' => $userType,
        ];

        $result = $this->aiAgentService->sendMessage([
            'message' => $lastUserMessage->content,
            'conversation_id' => $conversation->id,
        ], $context);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'conversation_id' => $result['conversation_id'],
                'message' => $result['message'],
                'metadata' => $result['metadata'] ?? [],
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to regenerate message',
        ], 400);
    }

    /**
     * POST /api/ai/assistant/chat
     */
    public function assistantChat(Request $request): JsonResponse
    {
        $request->validate([
            'message' => 'required|string|max:10000',
            'conversation_id' => 'nullable|uuid|exists:asl_ai_conversations,id',
        ]);

        $user = $request->user();
        $context = [
            'user' => $user,
            'session_id' => $request->header('X-Session-Id') ?: session()->getId(),
            'ip' => $request->ip(),
            'user_type' => 'customer',
            'use_context' => true,
            'skip_access_check' => true,
            'daily_limit_override' => (int) config('ai_agent.daily_limit_customer', 5),
        ];

        $result = $this->aiAgentService->sendMessage($request->only('message', 'conversation_id'), $context);

        if ($result['success']) {
            return response()->json([
                'success' => true,
                'conversation_id' => $result['conversation_id'],
                'message' => $result['message'],
                'metadata' => $result['metadata'] ?? [],
            ]);
        }

        $status = ($result['code'] ?? null) === 'DAILY_LIMIT_REACHED' ? 429 : 400;

        return response()->json([
            'success' => false,
            'error' => $result['error'] ?? 'Failed to process message',
            'code' => $result['code'] ?? null,
            'limit' => $result['limit'] ?? null,
            'remaining' => $result['remaining'] ?? null,
        ], $status);
    }

    /**
     * GET /api/ai/assistant/conversations
     */
    public function getAssistantConversations(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $status = $request->query('status', 'active');
        $conversations = $this->aiAgentService->getConversationsForUser($user?->id, $sessionId, 'customer', $status);

        return response()->json(['success' => true, 'conversations' => $conversations]);
    }

    /**
     * GET /api/ai/assistant/conversations/{id}
     */
    public function getAssistantConversation(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $conversation = $this->aiAgentService->getConversationWithMessages($id, $user?->id, $sessionId);
        if (! $conversation || $conversation->type !== 'customer') {
            return response()->json(['success' => false, 'error' => 'Conversation not found or access denied'], 404);
        }

        return response()->json(['success' => true, 'conversation' => $conversation]);
    }

    /**
     * DELETE /api/ai/assistant/conversations/{id}
     */
    public function deleteAssistantConversation(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $sessionId = $request->header('X-Session-Id') ?: session()->getId();
        $conversation = $this->aiAgentService->getConversationWithMessages($id, $user?->id, $sessionId);
        if (! $conversation || $conversation->type !== 'customer') {
            return response()->json(['success' => false, 'error' => 'Conversation not found or access denied'], 404);
        }
        $conversation->archive();

        return response()->json(['success' => true, 'message' => 'Conversation archived successfully']);
    }
}
