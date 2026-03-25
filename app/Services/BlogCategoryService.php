<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogCategory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BlogCategoryService
{
    public function __construct(
        private readonly BlogCacheService $blogCache
    ) {}

    public function getDataTables(Request $request): mixed
    {
        $query = BlogCategory::query()->orderBy('name');

        return DataTables::eloquent($query)
            ->addColumn('blogs_count', fn (BlogCategory $category) => $category->blogs()->count())
            ->toJson();
    }

    public function getAll(): Collection
    {
        return BlogCategory::query()->orderBy('name')->get();
    }

    public function create(array $data): BlogCategory
    {
        $slug = $this->uniqueSlug($data['slug'] ?? Str::slug($data['name']), null);

        $category = BlogCategory::query()->create([
            'name' => $data['name'],
            'slug' => $slug,
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
        ]);

        $this->blogCache->flushPublic();

        return $category;
    }

    public function update(BlogCategory $category, array $data): BlogCategory
    {
        $name = $data['name'] ?? $category->name;
        $slug = $this->uniqueSlug(
            $data['slug'] ?? Str::slug($name),
            $category->id
        );

        $category->update([
            'name' => $name,
            'slug' => $slug,
            'description' => array_key_exists('description', $data) ? $data['description'] : $category->description,
            'image' => $data['image'] ?? $category->image,
        ]);

        $this->blogCache->flushPublic();

        return $category->fresh();
    }

    public function delete(BlogCategory $category): bool
    {
        $deleted = $category->delete();
        if ($deleted) {
            $this->blogCache->flushPublic();
        }

        return $deleted;
    }

    private function uniqueSlug(string $base, ?string $excludeId): string
    {
        $slug = $base;
        $count = 0;
        while (true) {
            $query = BlogCategory::query()->where('slug', $slug);
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
