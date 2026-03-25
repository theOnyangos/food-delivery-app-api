<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class BlogService
{
    public function __construct(
        private readonly BlogCacheService $blogCache
    ) {}

    public function getDataTables(Request $request): mixed
    {
        $query = Blog::query()->with('category', 'author')->latest('updated_at');

        if ($request->filled('blog_category_id')) {
            $query->where('blog_category_id', $request->input('blog_category_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('published_at_formatted', fn (Blog $blog) => $blog->published_at?->format('Y-m-d H:i'))
            ->orderColumn('published_at_formatted', fn ($q, $order) => $q->orderBy('published_at', $order))
            ->toJson();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRecentBlogs(int $limit = 12): array
    {
        $blogs = Blog::query()
            ->published()
            ->with(['category', 'author'])
            ->latest('published_at')
            ->limit($limit)
            ->get();

        return $blogs->map(function (Blog $blog): array {
            $body = $blog->body ?? '';
            $wordCount = str_word_count(strip_tags($body));
            $readTimeMin = $wordCount > 0 ? (int) max(1, ceil($wordCount / 200)) : null;

            return [
                'id' => $blog->id,
                'title' => $blog->title,
                'excerpt' => $blog->excerpt,
                'image_url' => $blog->image ?: null,
                'category' => $blog->category?->slug ?? 'uncategorized',
                'author' => $blog->author?->full_name ?? $blog->author?->email ?? null,
                'published_at' => $blog->published_at?->format('Y-m-d'),
                'read_time_min' => $readTimeMin,
            ];
        })->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findPublishedArrayBySlugOrId(string $value): ?array
    {
        $blog = Blog::query()->published()->with('category', 'author')
            ->where(function ($q) use ($value): void {
                $q->where('slug', $value)->orWhere('id', $value);
            })
            ->first();
        if ($blog === null) {
            return null;
        }

        $body = $blog->body ?? '';
        $wordCount = str_word_count(strip_tags($body));
        $readTimeMin = $wordCount > 0 ? (int) max(1, ceil($wordCount / 200)) : null;

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'slug' => $blog->slug,
            'excerpt' => $blog->excerpt,
            'body' => $blog->body,
            'image' => $blog->image,
            'seo_title' => $blog->seo_title,
            'seo_meta_description' => $blog->seo_meta_description,
            'seo_keywords' => $blog->seo_keywords,
            'status' => $blog->status,
            'published_at' => $blog->published_at?->toIso8601String(),
            'read_time_min' => $readTimeMin,
            'category' => $blog->category ? [
                'id' => $blog->category->id,
                'name' => $blog->category->name,
                'slug' => $blog->category->slug,
            ] : null,
            'author' => $blog->author ? [
                'id' => $blog->author->id,
                'name' => $blog->author->full_name,
                'email' => $blog->author->email,
            ] : null,
        ];
    }

    public function create(array $data, ?User $author = null): Blog
    {
        $slug = $this->uniqueSlug($data['slug'] ?? Str::slug($data['title']), null);
        $status = $data['status'] ?? 'draft';
        $publishedAt = ($status === 'published') ? now() : null;

        $blog = Blog::query()->create([
            'blog_category_id' => $data['blog_category_id'],
            'title' => $data['title'],
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? null,
            'seo_title' => $this->nullableString($data['seo_title'] ?? null),
            'seo_meta_description' => $this->nullableString($data['seo_meta_description'] ?? null),
            'seo_keywords' => $this->normalizeSeoKeywords($data['seo_keywords'] ?? null),
            'body' => $data['body'],
            'image' => $data['image'] ?? null,
            'author_id' => $author?->id,
            'status' => $status,
            'published_at' => $publishedAt,
        ]);

        $this->blogCache->flushPublic();

        return $blog->fresh(['category', 'author']);
    }

    public function update(Blog $blog, array $data, ?User $author = null): Blog
    {
        $slug = $this->uniqueSlug(
            $data['slug'] ?? Str::slug($data['title'] ?? $blog->title),
            $blog->id
        );

        $status = $data['status'] ?? $blog->status;
        $publishedAt = $blog->published_at;
        if ($status === 'published' && $blog->published_at === null) {
            $publishedAt = now();
        }

        $update = [
            'blog_category_id' => $data['blog_category_id'] ?? $blog->blog_category_id,
            'title' => $data['title'] ?? $blog->title,
            'slug' => $slug,
            'excerpt' => $data['excerpt'] ?? $blog->excerpt,
            'body' => $data['body'] ?? $blog->body,
            'image' => $data['image'] ?? $blog->image,
            'author_id' => $author?->id ?? $blog->author_id,
            'status' => $status,
            'published_at' => $publishedAt,
        ];
        if (array_key_exists('seo_title', $data)) {
            $update['seo_title'] = $this->nullableString($data['seo_title']);
        }
        if (array_key_exists('seo_meta_description', $data)) {
            $update['seo_meta_description'] = $this->nullableString($data['seo_meta_description']);
        }
        if (array_key_exists('seo_keywords', $data)) {
            $update['seo_keywords'] = $this->normalizeSeoKeywords($data['seo_keywords']);
        }
        $blog->update($update);

        $this->blogCache->flushPublic();

        return $blog->fresh(['category', 'author']);
    }

    public function delete(Blog $blog): bool
    {
        $deleted = $blog->delete();
        if ($deleted) {
            $this->blogCache->flushPublic();
        }

        return $deleted;
    }

    private function nullableString(?string $s): ?string
    {
        if ($s === null) {
            return null;
        }
        $t = trim($s);

        return $t === '' ? null : $t;
    }

    /**
     * @param  array<int, string>|null  $keywords
     * @return array<int, string>|null
     */
    private function normalizeSeoKeywords(?array $keywords): ?array
    {
        if ($keywords === null) {
            return null;
        }
        $out = [];
        foreach ($keywords as $k) {
            $t = is_string($k) ? trim($k) : '';
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out === [] ? null : array_values(array_unique($out));
    }

    private function uniqueSlug(string $base, ?string $excludeId): string
    {
        $slug = $base;
        $count = 0;
        while (true) {
            $query = Blog::query()->where('slug', $slug);
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
