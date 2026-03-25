<?php

namespace App\Http\Controllers\Api;

use App\Events\UserTyping;
use App\Http\Controllers\Controller;
use App\Services\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatTypingController extends Controller
{
    public function __construct(
        protected ChatService $chatService
    ) {}

    public function store(Request $request, string $conversationId): JsonResponse
    {
        try {
            $user = $request->user();
            $typing = $request->boolean('typing', true);

            $conversation = $this->chatService->getConversation($conversationId, $user);
            if (! $conversation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Conversation not found',
                ], 404);
            }

            event(new UserTyping($conversationId, $user->id, $typing));

            return response()->json([
                'success' => true,
                'message' => $typing ? 'Typing indicator sent' : 'Typing stopped',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error sending typing indicator: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to send typing indicator',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
