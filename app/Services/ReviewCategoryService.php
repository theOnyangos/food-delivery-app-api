<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ReviewCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class ReviewCategoryService
{
    public function __construct(
        private readonly RedisService $redis
    ) {}

    public function getDataTables(Request $request): mixed
    {
        $query = ReviewCategory::query()->ordered();

        return DataTables::eloquent($query)->toJson();
    }

    public function getAllOrdered(): Collection
    {
        return ReviewCategory::query()->ordered()->get(['id', 'name', 'slug', 'sort_order']);
    }

    public function create(array $data): ReviewCategory
    {
        $slug = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), null);

        $created = ReviewCategory::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'sort_order' => (int) ($data['sort_order'] ?? 0),
        ]);
        $this->flushMealReviewsAggregateCache();

        return $created;
    }

    public function update(ReviewCategory $category, array $data): ReviewCategory
    {
        $slug = $this->uniqueSlug(
            $data['slug'] ?? Str::slug($data['name'] ?? $category->name),
            $category->id
        );

        $category->update([
            'name' => $data['name'] ?? $category->name,
            'slug' => $slug,
            'sort_order' => array_key_exists('sort_order', $data) ? (int) $data['sort_order'] : $category->sort_order,
        ]);

        $fresh = $category->fresh();
        $this->flushMealReviewsAggregateCache();

        return $fresh;
    }

    public function delete(ReviewCategory $category): bool
    {
        $deleted = $category->delete();
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
            $query = ReviewCategory::query()->where('slug', $slug);
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
