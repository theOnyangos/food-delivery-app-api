<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReviewTopic;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ReviewTopicService
{
    public function __construct(
        private readonly RedisService $redis
    ) {}

    public function getDataTables(Request $request): mixed
    {
        $query = ReviewTopic::query()->ordered();

        return DataTables::eloquent($query)->toJson();
    }

    public function getAllOrdered(): Collection
    {
        return ReviewTopic::query()->ordered()->get(['id', 'name', 'slug', 'sort_order']);
    }

    public function create(array $data): ReviewTopic
    {
        $slug = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), null);

        $created = ReviewTopic::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        $this->flushMealReviewsAggregateCache();

        return $created;
    }

    public function update(ReviewTopic $topic, array $data): ReviewTopic
    {
        $slug = $this->uniqueSlug(
            $data['slug'] ?? Str::slug($data['name'] ?? $topic->name),
            $topic->id
        );

        $topic->update([
            'name' => $data['name'] ?? $topic->name,
            'slug' => $slug,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $topic->sort_order,
        ]);

        $fresh = $topic->fresh();
        $this->flushMealReviewsAggregateCache();

        return $fresh;
    }

    public function delete(ReviewTopic $topic): bool
    {
        $deleted = $topic->delete();
        if ($deleted) {
            $this->flushMealReviewsAggregateCache();
        }

        return $deleted;
    }

    private function flushMealReviewsAggregateCache(): void
    {
        $tag = (string) config('reviews.all_meal_reviews_tag', 'meal_reviews_all');
        $this->redis->flushTag($tag);
    }

    private function uniqueSlug(string $base, ?string $excludeId): string
    {
        $slug = $base;
        $count = 0;
        while (true) {
            $query = ReviewTopic::query()->where('slug', $slug);
            if ($excludeId !== null) {
                $query->where('id', '!=', $excludeId);
            }
            if (! $query->exists()) {
                return $slug;
            }
            $count++;
            $slug = $base.'-'.$count;
        }
    }
}
