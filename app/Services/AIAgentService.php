<?php

namespace App\Services;

use App\Models\AiConversation;
use App\Models\AiDailyUsage;
use App\Models\AiMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use OpenAI;

class AIAgentService
{
    protected ?object $openai = null;

    protected AIContextService $contextService;

    public function __construct(?AIContextService $contextService = null)
    {
        $this->contextService = $contextService ?? new AIContextService;
        $apiKey = config('ai_agent.api_key');
        if (! empty($apiKey)) {
            try {
                $this->openai = OpenAI::client(trim($apiKey, " \t\n\r\0\xB'\""));
            } catch (\Throwable $e) {
                Log::error('OpenAI client initialization failed: '.$e->getMessage());
                $this->openai = null;
            }
        }
    }

    /**
     * Send message to AI agent. Returns ['success' => bool, ...].
     *
     * @param  array{message: string, conversation_id?: string}  $input
     * @param  array{user: User|null, session_id: string|null, ip: string|null, user_type: 'vendor'|'admin'|'customer', use_context?: bool, skip_access_check?: bool, daily_limit_override?: int}  $context
     */
    public function sendMessage(array $input, array $context): array
    {
        $message = trim((string) ($input['message'] ?? ''));
        $conversationId = $input['conversation_id'] ?? null;

        if (! config('ai_agent.enabled') || empty(config('ai_agent.api_key'))) {
            return [
                'success' => false,
                'error' => 'AI agent is not enabled or configured',
            ];
        }

        if ($message === '') {
            return [
                'success' => false,
                'error' => 'Message is required',
            ];
        }

        $user = $context['user'] ?? null;
        $sessionId = $context['session_id'] ?? null;
        $ipAddress = $context['ip'] ?? null;
        $userType = $context['user_type'] ?? 'vendor';
        $useContext = $context['use_context'] ?? false;
        $skipAccessCheck = $context['skip_access_check'] ?? false;
        $dailyLimitOverride = $context['daily_limit_override'] ?? null;

        if (! $skipAccessCheck && $user && ! $user->hasAiAssistantAccess()) {
            return [
                'success' => false,
                'error' => 'You do not have access to the AI assistant. Upgrade your plan or contact support.',
            ];
        }

        $dailyResult = $this->enforceDailyLimit($userType, $user, $sessionId, $ipAddress, $dailyLimitOverride);
        if (! ($dailyResult['success'] ?? true)) {
            return [
                'success' => false,
                'error' => $dailyResult['error'] ?? 'Daily limit reached.',
                'code' => $dailyResult['code'] ?? null,
                'limit' => $dailyResult['limit'] ?? null,
                'remaining' => $dailyResult['remaining'] ?? 0,
            ];
        }

        $rateLimitKey = $user ? "ai_agent_user_{$user->id}" : 'ai_agent_ip_'.md5($sessionId ?? $ipAddress ?? 'guest');
        if (! $this->checkRateLimit($rateLimitKey)) {
            return [
                'success' => false,
                'error' => 'Rate limit exceeded. Please try again later.',
            ];
        }

        try {
            $conversation = $this->getOrCreateConversation($user?->id, $sessionId, $userType, $conversationId);
            if (! $conversation) {
                return ['success' => false, 'error' => 'Failed to get or create conversation.'];
            }

            AiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'user',
                'content' => $message,
            ]);

            $history = AiMessage::query()
                ->where('conversation_id', $conversation->id)
                ->orderBy('created_at')
                ->limit(config('ai_agent.max_history_messages', 10))
                ->get()
                ->map(fn (AiMessage $m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $contextArray = [];
            if ($useContext) {
                $kbContext = $this->contextService->getKnowledgeBaseContext($message, 5);
                $appContext = $this->contextService->getAppContentContext($message, 5);
                $contextArray = [
                    'kb_sources' => $kbContext,
                    'meals' => $appContext['meals'] ?? [],
                    'meal_categories' => $appContext['meal_categories'] ?? [],
                ];
            }

            $systemPrompt = $this->buildSystemPrompt($userType, $contextArray);
            $messages = array_merge(
                [['role' => 'system', 'content' => $systemPrompt]],
                $history
            );

            $response = $this->callOpenAI($messages);
            if (! $response['success']) {
                return $response;
            }

            AiMessage::query()->create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response['content'],
                'metadata' => $response['metadata'] ?? [],
            ]);

            $conversation->touch();

            return [
                'success' => true,
                'conversation_id' => $conversation->id,
                'message' => $response['content'],
                'metadata' => $response['metadata'] ?? [],
            ];
        } catch (\Throwable $e) {
            Log::error('AI Agent error: '.$e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $debug = config('app.env') !== 'production' ? ' ('.$e->getMessage().')' : '';

            return [
                'success' => false,
                'error' => 'An error occurred while processing your request. Please try again later.'.$debug,
                'error_id' => bin2hex(random_bytes(6)),
            ];
        }
    }

    protected function buildSystemPrompt(string $userType, array $context = []): string
    {
        $prompts = config('ai_agent.system_prompts', []);
        $base = $prompts[$userType] ?? $prompts['vendor'] ?? 'You are a helpful assistant for Amazing Souls.';

        if (! empty($context)) {
            $base .= "\n\nGrounding rules:\n"
                ."- Use ONLY the provided context and sources below when answering.\n"
                ."- If the answer cannot be determined from the provided context/sources, say you don't have enough information and suggest the Help Center or contacting support.\n"
                ."- Do not guess, invent details, or cite sources that are not provided.\n";
            $contextText = $this->contextService->formatContextForPrompt($context);
            if ($contextText !== '') {
                $base .= "\n\nRelevant context:\n".$contextText;
            }
        }

        return $base;
    }

    protected function getOrCreateConversation(?string $userId, ?string $sessionId, string $userType, ?string $conversationId): ?AiConversation
    {
        if ($conversationId) {
            $conv = AiConversation::query()->find($conversationId);
            if ($conv && $conv->status === 'active') {
                if (($userId && $conv->user_id === $userId) || ($sessionId && $conv->session_id === $sessionId)) {
                    return $conv;
                }
            }
        }

        return AiConversation::query()->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
            'type' => $userType,
            'status' => 'active',
        ]);
    }

    protected function callOpenAI(array $messages): array
    {
        if (! $this->openai) {
            return ['success' => false, 'error' => 'OpenAI client not initialized'];
        }

        try {
            $response = $this->openai->chat()->create([
                'model' => config('ai_agent.default_model', 'gpt-3.5-turbo'),
                'messages' => $messages,
                'max_tokens' => config('ai_agent.max_tokens', 1000),
                'temperature' => config('ai_agent.temperature', 0.7),
            ]);

            $choice = $response->choices[0] ?? null;
            $usage = $response->usage;
            $content = $choice?->message?->content ?? '';

            return [
                'success' => true,
                'content' => $content,
                'metadata' => [
                    'model' => $response->model,
                    'usage' => [
                        'prompt_tokens' => $usage?->promptTokens ?? 0,
                        'completion_tokens' => $usage?->completionTokens ?? 0,
                        'total_tokens' => $usage?->totalTokens ?? 0,
                    ],
                    'finish_reason' => $choice?->finishReason ?? 'stop',
                ],
            ];
        } catch (\Throwable $e) {
            Log::error('OpenAI API error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Failed to get AI response: '.$e->getMessage(),
            ];
        }
    }

    protected function enforceDailyLimit(string $userType, ?User $user, ?string $sessionId, ?string $ipAddress, ?int $dailyLimitOverride = null): array
    {
        $limit = $dailyLimitOverride !== null
            ? $dailyLimitOverride
            : ($user ? $user->getAiAssistantDailyLimit() : (int) config('ai_agent.daily_limit_customer', 5));
        if ($limit <= 0) {
            return ['success' => true];
        }

        [$identityType, $identity] = $this->resolveDailyIdentity($user, $sessionId, $ipAddress);
        if ($identityType === '' || $identity === '') {
            return ['success' => true];
        }

        $today = now()->toDateString();
        $now = now();

        try {
            return DB::transaction(function () use ($today, $userType, $identityType, $identity, $limit, $now) {
                $row = AiDailyUsage::query()
                    ->where('usage_date', $today)
                    ->where('user_type', $userType)
                    ->where('identity_type', $identityType)
                    ->where('identity', $identity)
                    ->lockForUpdate()
                    ->first();

                if ($row) {
                    $current = (int) $row->message_count;
                    if ($current >= $limit) {
                        return [
                            'success' => false,
                            'error' => "Daily limit reached. You can ask up to {$limit} questions per day. Please try again tomorrow.",
                            'code' => 'DAILY_LIMIT_REACHED',
                            'limit' => $limit,
                            'remaining' => 0,
                        ];
                    }
                    $row->increment('message_count');
                    $row->update(['updated_at' => $now]);
                    $remaining = max(0, $limit - $current - 1);
                } else {
                    AiDailyUsage::query()->create([
                        'usage_date' => $today,
                        'user_type' => $userType,
                        'identity_type' => $identityType,
                        'identity' => $identity,
                        'message_count' => 1,
                    ]);
                    $remaining = max(0, $limit - 1);
                }

                return [
                    'success' => true,
                    'daily_limit' => $limit,
                    'daily_remaining' => $remaining,
                ];
            });
        } catch (\Throwable $e) {
            Log::error('AI daily limit check failed: '.$e->getMessage());

            return ['success' => true];
        }
    }

    /** @return array{0: string, 1: string} */
    protected function resolveDailyIdentity(?User $user, ?string $sessionId, ?string $ipAddress): array
    {
        if ($user) {
            return ['user', (string) $user->id];
        }
        if (! empty($sessionId)) {
            return ['session', $sessionId];
        }
        if (! empty($ipAddress)) {
            return ['ip', $ipAddress];
        }

        return ['', ''];
    }

    protected function checkRateLimit(string $key): bool
    {
        $limit = str_starts_with($key, 'ai_agent_user_')
            ? config('ai_agent.rate_limit_per_user', 30)
            : config('ai_agent.rate_limit_per_ip', 60);
        $cacheKey = 'ai_agent_rate:'.$key;
        $entry = Cache::get($cacheKey, ['count' => 0, 'window' => time()]);
        $count = (int) ($entry['count'] ?? 0);
        $window = (int) ($entry['window'] ?? time());
        if (time() - $window >= 60) {
            $count = 0;
            $window = time();
        }
        if ($count >= $limit) {
            return false;
        }
        Cache::put($cacheKey, ['count' => $count + 1, 'window' => $window], 120);

        return true;
    }

    public function getConversationWithMessages(string $conversationId, ?string $userId, ?string $sessionId): ?AiConversation
    {
        $conv = AiConversation::query()->find($conversationId);
        if (! $conv) {
            return null;
        }
        if (($userId && $conv->user_id !== $userId) && ($sessionId && $conv->session_id !== $sessionId)) {
            return null;
        }
        $conv->setRelation('messages', $conv->messages()->orderBy('created_at')->get());

        return $conv;
    }

    public function getConversationsForUser(?string $userId, ?string $sessionId, string $userType, string $status = 'active'): Collection
    {
        if ($userId) {
            return AiConversation::query()->forUser($userId, $userType, $status)->get();
        }

        return AiConversation::query()->forSession($sessionId ?? '', $userType, $status)->get();
    }
}
