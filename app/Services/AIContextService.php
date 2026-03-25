<?php

namespace App\Services;

use App\Models\Meal;
use App\Models\MealCategory;
use Illuminate\Support\Facades\DB;

class AIContextService
{
    /**
     * Search KB chunks by query (LIKE on content and source title).
     *
     * @return array<int, array{chunk_id: string, source_id: string, content: string, citation: array{title: string, type: string, url: string|null, file_path: string|null}, snippet: string}>
     */
    public function getKnowledgeBaseContext(string $query, int $limit = 5): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $chunks = 'asl_ai_kb_chunks';
        $sources = 'asl_ai_kb_sources';

        try {
            $builder = DB::table($chunks)
                ->select([
                    $chunks.'.id as chunk_id',
                    $chunks.'.source_id',
                    $chunks.'.content',
                    $sources.'.type as source_type',
                    $sources.'.title as source_title',
                    $sources.'.source_url',
                    $sources.'.file_path',
                ])
                ->join($sources, $sources.'.id', '=', $chunks.'.source_id')
                ->where($sources.'.status', 'active');

            $builder->where(function ($q) use ($query, $chunks, $sources) {
                $q->where($chunks.'.content', 'like', '%'.$query.'%')
                    ->orWhere($sources.'.title', 'like', '%'.$query.'%');
            });

            $tokens = array_values(array_filter(
                array_map('trim', preg_split('/\s+/', $query) ?: []),
                fn ($t) => mb_strlen($t) >= 2
            ));
            $tokens = array_slice($tokens, 0, 6);
            foreach ($tokens as $t) {
                $builder->orWhere(function ($q) use ($t, $chunks, $sources) {
                    $q->where($chunks.'.content', 'like', '%'.$t.'%')
                        ->orWhere($sources.'.title', 'like', '%'.$t.'%');
                });
            }

            $rows = $builder->orderByDesc($sources.'.updated_at')
                ->orderBy($chunks.'.chunk_index')
                ->limit($limit)
                ->get();

            return collect($rows)->map(function ($r) {
                $content = (string) ($r->content ?? '');

                return [
                    'chunk_id' => (string) $r->chunk_id,
                    'source_id' => (string) $r->source_id,
                    'content' => $content,
                    'citation' => [
                        'title' => (string) ($r->source_title ?? ''),
                        'type' => (string) ($r->source_type ?? ''),
                        'url' => $r->source_url,
                        'file_path' => $r->file_path,
                    ],
                    'snippet' => mb_substr(trim($content), 0, 240),
                ];
            })->all();
        } catch (\Throwable $e) {
            report($e);

            return [];
        }
    }

    /**
     * Published meals and categories for grounding (public fields only).
     *
     * @return array{meals: array, meal_categories: array}
     */
    public function getAppContentContext(string $query, int $limit = 5): array
    {
        $query = trim($query);
        $like = $query !== '' ? '%'.$query.'%' : '%';

        $context = [
            'meals' => [],
            'meal_categories' => [],
        ];

        try {
            $meals = Meal::query()
                ->where('status', 'published')
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)
                        ->orWhere('description', 'like', $like)
                        ->orWhere('excerpt', 'like', $like);
                })
                ->limit($limit)
                ->get(['id', 'title', 'excerpt', 'description', 'status', 'published_at', 'category_id']);

            $context['meals'] = $meals->map(fn (Meal $m) => [
                'id' => $m->id,
                'title' => $m->title,
                'excerpt' => $m->excerpt ? mb_substr((string) $m->excerpt, 0, 200) : null,
                'published_at' => $m->published_at?->toIso8601String(),
                'status' => $m->status,
            ])->all();

            $categories = MealCategory::query()
                ->where(function ($q) use ($like) {
                    $q->where('title', 'like', $like)->orWhere('description', 'like', $like);
                })
                ->limit($limit)
                ->get(['id', 'title', 'description']);

            $context['meal_categories'] = $categories->map(fn (MealCategory $c) => [
                'id' => $c->id,
                'title' => $c->title,
                'description' => $c->description ? mb_substr((string) $c->description, 0, 200) : null,
            ])->all();
        } catch (\Throwable $e) {
            report($e);
        }

        return $context;
    }

    /**
     * Format context array into a single string for the system prompt.
     */
    public function formatContextForPrompt(array $context): string
    {
        $lines = [];

        if (! empty($context['kb_sources'])) {
            $lines[] = 'Knowledge Base (admin-managed):';
            foreach ($context['kb_sources'] as $kb) {
                $title = $kb['citation']['title'] ?? 'Source';
                $ref = $kb['citation']['url'] ?? ($kb['citation']['file_path'] ?? '');
                $lines[] = '- Source: '.$title.($ref ? ' ('.$ref.')' : '');
                $lines[] = '  Content: '.($kb['snippet'] ?? $kb['content'] ?? '');
            }
            $lines[] = '';
        }

        if (! empty($context['meals'])) {
            $lines[] = 'Meals:';
            foreach ($context['meals'] as $m) {
                $lines[] = '- '.($m['title'] ?? '').' | '.($m['excerpt'] ?? '');
            }
            $lines[] = '';
        }

        if (! empty($context['meal_categories'])) {
            $lines[] = 'Meal categories:';
            foreach ($context['meal_categories'] as $c) {
                $lines[] = '- '.($c['title'] ?? '').' | '.($c['description'] ?? '');
            }
            $lines[] = '';
        }

        return implode("\n", $lines);
    }
}
