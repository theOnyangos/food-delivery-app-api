<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiConversation;
use App\Models\AiMessage;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAiAgentConversationController extends Controller
{
    private static function toIso8601(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (is_string($value)) {
            return $value;
        }
        if ($value instanceof Carbon) {
            return $value->toIso8601String();
        }

        return (string) $value;
    }

    /**
     * GET /api/admin/ai-agent/conversations/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $days = min(max((int) $request->input('days', 30), 7), 90);
        $since = Carbon::now()->subDays($days)->startOfDay();

        $convTable = (new AiConversation)->getTable();
        $msgTable = (new AiMessage)->getTable();

        $totalConversations = AiConversation::query()->where('created_at', '>=', $since)->count();
        $totalMessages = AiMessage::query()
            ->join($convTable, $msgTable.'.conversation_id', '=', $convTable.'.id')
            ->where($convTable.'.created_at', '>=', $since)
            ->count();
        $uniqueUsers = (int) DB::table($convTable)
            ->where('created_at', '>=', $since)
            ->whereNotNull('user_id')
            ->selectRaw('count(distinct user_id) as cnt')
            ->value('cnt');

        $conversationsByDay = AiConversation::query()
            ->where('created_at', '>=', $since)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();

        $messagesByDay = AiMessage::query()
            ->join($convTable, $msgTable.'.conversation_id', '=', $convTable.'.id')
            ->where($msgTable.'.created_at', '>=', $since)
            ->select(DB::raw('DATE('.$msgTable.'.created_at) as date'), DB::raw('COUNT('.$msgTable.'.id) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->all();

        $trend = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $d = Carbon::now()->subDays($i)->format('Y-m-d');
            $trend[] = [
                'date' => $d,
                'conversations' => (int) ($conversationsByDay[$d] ?? 0),
                'messages' => (int) ($messagesByDay[$d] ?? 0),
            ];
        }

        return response()->json([
            'success' => true,
            'stats' => [
                'total_conversations' => $totalConversations,
                'total_messages' => $totalMessages,
                'unique_users' => $uniqueUsers,
                'period_days' => $days,
                'trend' => $trend,
            ],
        ]);
    }

    /**
     * GET /api/admin/ai-agent/conversations
     */
    public function index(Request $request): JsonResponse
    {
        $query = AiConversation::query()
            ->with('user:id,first_name,middle_name,last_name,email')
            ->orderByDesc('updated_at');

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $perPage = min((int) $request->input('per_page', 20), 100);
        $paginator = $query->paginate($perPage);

        $items = $paginator->getCollection()->map(function (AiConversation $conv) {
            $user = $conv->user;
            $userLabel = $user
                ? ($user->full_name ?: $user->email)
                : ($conv->session_id ? 'Guest' : '—');

            return [
                'id' => $conv->id,
                'type' => $conv->type,
                'status' => $conv->status,
                'user_id' => $conv->user_id,
                'user_label' => $userLabel,
                'user_email' => $user?->email,
                'messages_count' => $conv->messages()->count(),
                'created_at' => self::toIso8601($conv->created_at),
                'updated_at' => self::toIso8601($conv->updated_at),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    /**
     * GET /api/admin/ai-agent/conversations/{id}
     */
    public function show(string $id): JsonResponse
    {
        $conv = AiConversation::query()
            ->with(['user:id,first_name,middle_name,last_name,email', 'messages' => fn ($q) => $q->orderBy('created_at')])
            ->find($id);

        if (! $conv) {
            return response()->json(['success' => false, 'message' => 'Conversation not found'], 404);
        }

        $user = $conv->user;
        $userLabel = $user
            ? ($user->full_name ?: $user->email)
            : ($conv->session_id ? 'Guest' : '—');

        return response()->json([
            'success' => true,
            'conversation' => [
                'id' => $conv->id,
                'type' => $conv->type,
                'status' => $conv->status,
                'user_id' => $conv->user_id,
                'user_label' => $userLabel,
                'user_email' => $user?->email,
                'created_at' => self::toIso8601($conv->created_at),
                'updated_at' => self::toIso8601($conv->updated_at),
                'messages' => $conv->messages->map(fn ($m) => [
                    'id' => $m->id,
                    'role' => $m->role,
                    'content' => $m->content,
                    'created_at' => self::toIso8601($m->created_at),
                ]),
            ],
        ]);
    }
}
