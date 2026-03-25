<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiKbSource;
use App\Services\AIKnowledgeBaseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAiAgentKbController extends Controller
{
    public function __construct(
        protected AIKnowledgeBaseService $kbService
    ) {}

    /**
     * GET /api/admin/ai-agent/kb/sources
     */
    public function index(Request $request): JsonResponse
    {
        $sources = AiKbSource::query()
            ->orderByDesc('updated_at')
            ->paginate($request->input('per_page', 15));

        return response()->json(['success' => true, 'data' => $sources]);
    }

    /**
     * POST /api/admin/ai-agent/kb/sources
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:text,url,file',
            'title' => 'required|string|max:255',
            'source_url' => 'required_if:type,url|nullable|url|max:2000',
            'file_path' => 'required_if:type,file|nullable|string|max:500',
            'content_raw' => 'required_if:type,text|nullable|string',
            'status' => 'nullable|in:active,disabled',
        ]);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['created_by'] = $request->user()?->id;
        $source = AiKbSource::query()->create($validated);

        return response()->json(['success' => true, 'data' => $source], 201);
    }

    /**
     * GET /api/admin/ai-agent/kb/sources/{id}
     */
    public function show(string $id): JsonResponse
    {
        $source = AiKbSource::query()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }

        return response()->json(['success' => true, 'data' => $source]);
    }

    /**
     * PUT/PATCH /api/admin/ai-agent/kb/sources/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $source = AiKbSource::query()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'source_url' => 'nullable|url|max:2000',
            'file_path' => 'nullable|string|max:500',
            'content_raw' => 'nullable|string',
            'status' => 'nullable|in:active,disabled',
        ]);
        $source->update(array_filter($validated, fn ($v) => $v !== null));

        return response()->json(['success' => true, 'data' => $source->fresh()]);
    }

    /**
     * DELETE /api/admin/ai-agent/kb/sources/{id}
     */
    public function destroy(string $id): JsonResponse
    {
        $source = AiKbSource::query()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $source->delete();

        return response()->json(['success' => true, 'message' => 'Source deleted']);
    }

    /**
     * POST /api/admin/ai-agent/kb/sources/{id}/ingest
     */
    public function ingest(string $id): JsonResponse
    {
        $source = AiKbSource::query()->find($id);
        if (! $source) {
            return response()->json(['success' => false, 'message' => 'Source not found'], 404);
        }
        $result = $this->kbService->ingestSource($id);
        if ($result['success']) {
            return response()->json(['success' => true, 'chunks' => $result['chunks'], 'source_id' => $id]);
        }

        return response()->json(['success' => false, 'error' => $result['error'] ?? 'Ingest failed'], 400);
    }

    /**
     * POST /api/admin/ai-agent/kb/ingest-all
     */
    public function ingestAll(Request $request): JsonResponse
    {
        $includeDisabled = $request->boolean('include_disabled', false);
        $result = $this->kbService->ingestAll($includeDisabled);

        return response()->json($result);
    }
}
