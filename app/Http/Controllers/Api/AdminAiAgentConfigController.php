<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAgentSetting;
use App\Services\RedisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI;

class AdminAiAgentConfigController extends Controller
{
    private const OPENAI_MODELS_CACHE_TTL = 3600;

    public function __construct(
        private readonly RedisService $redis
    ) {}

    /**
     * GET /api/admin/ai-agent/config
     */
    public function show(): JsonResponse
    {
        $apiKey = config('ai_agent.api_key');
        $config = [
            'api_key_set' => ! empty(trim($apiKey ?? '', " \t\n\r\0\xB'\"")),
            'default_model' => AiAgentSetting::getValue('default_model', config('ai_agent.default_model')),
            'enabled' => filter_var(AiAgentSetting::getValue('enabled', config('ai_agent.enabled')), FILTER_VALIDATE_BOOLEAN),
            'daily_limit_customer' => (int) (AiAgentSetting::getValue('daily_limit_customer') ?? config('ai_agent.daily_limit_customer')),
            'daily_limit_admin' => (int) (AiAgentSetting::getValue('daily_limit_admin') ?? config('ai_agent.daily_limit_admin')),
            'max_tokens' => (int) (AiAgentSetting::getValue('max_tokens') ?? config('ai_agent.max_tokens')),
            'temperature' => (float) (AiAgentSetting::getValue('temperature') ?? config('ai_agent.temperature')),
            'system_prompts' => AiAgentSetting::getValue('system_prompts') ?? config('ai_agent.system_prompts'),
        ];

        return response()->json(['success' => true, 'config' => $config]);
    }

    /**
     * PUT /api/admin/ai-agent/config
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'default_model' => 'nullable|string|max:100',
            'enabled' => 'nullable|boolean',
            'daily_limit_customer' => 'nullable|integer|min:0',
            'daily_limit_admin' => 'nullable|integer|min:0',
            'max_tokens' => 'nullable|integer|min:1|max:128000',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'system_prompts' => 'nullable|array',
            'system_prompts.vendor' => 'nullable|string|max:2000',
            'system_prompts.admin' => 'nullable|string|max:2000',
            'system_prompts.customer' => 'nullable|string|max:2000',
        ]);

        foreach ($validated as $key => $value) {
            if ($value !== null) {
                AiAgentSetting::setValue($key, $value);
            }
        }

        return response()->json(['success' => true, 'message' => 'Settings updated successfully']);
    }

    /**
     * GET /api/admin/ai-agent/openai/models
     */
    public function listOpenAIModels(): JsonResponse
    {
        $apiKey = config('ai_agent.api_key');
        if (empty(trim($apiKey ?? '', " \t\n\r\0\xB'\""))) {
            return response()->json(['success' => false, 'message' => 'OpenAI API key is not configured'], 400);
        }
        try {
            $models = $this->redis->remember('ai_agent:openai:models', self::OPENAI_MODELS_CACHE_TTL, function () use ($apiKey) {
                $client = OpenAI::client(trim($apiKey, " \t\n\r\0\xB'\""));
                $response = $client->models()->list();

                return collect($response->data ?? [])
                    ->map(fn ($m) => ['id' => $m->id ?? '', 'owned_by' => $m->ownedBy ?? ''])
                    ->filter(fn ($m) => ! empty($m['id']))
                    ->values()
                    ->all();
            });

            return response()->json(['success' => true, 'models' => $models]);
        } catch (\Throwable $e) {
            Log::error('OpenAI models list error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch OpenAI models'], 500);
        }
    }

    /**
     * GET /api/admin/ai-agent/openai/assistants
     */
    public function listOpenAIAssistants(): JsonResponse
    {
        $apiKey = config('ai_agent.api_key');
        if (empty(trim($apiKey ?? '', " \t\n\r\0\xB'\""))) {
            return response()->json(['success' => false, 'message' => 'OpenAI API key is not configured'], 400);
        }
        try {
            $client = OpenAI::client(trim($apiKey, " \t\n\r\0\xB'\""));
            $response = $client->assistants()->list();
            $assistants = collect($response->data ?? [])
                ->map(fn ($a) => ['id' => $a->id ?? '', 'name' => $a->name ?? ''])
                ->filter(fn ($a) => ! empty($a['id']))
                ->values()
                ->all();

            return response()->json(['success' => true, 'assistants' => $assistants]);
        } catch (\Throwable $e) {
            Log::error('OpenAI assistants list error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Failed to fetch OpenAI assistants'], 500);
        }
    }
}
