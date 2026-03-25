<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class BlogListingService
{
    /**
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function getPublishedBlogs(Request $request): LengthAwarePaginator
    {
        $perPage = min(max((int) $request->query('per_page', 15), 1), 50);
        $page = max((int) $request->query('page', 1), 1);

        $query = Blog::query()
            ->published()
            ->with(['category', 'author']);

        if ($request->filled('category_id')) {
            $query->where('blog_category_id', $request->input('category_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%'.$search.'%')
                    ->orWhere('excerpt', 'like', '%'.$search.'%')
                    ->orWhere('body', 'like', '%'.$search.'%');
            });
        }

        $query->latest('published_at');

        $blogs = $query->paginate($perPage, ['*'], 'page', $page);

        $items = $blogs->getCollection()->map(function (Blog $blog): array {
            return $this->formatBlogForListing($blog);
        })->all();

        return new LengthAwarePaginator(
            $items,
            $blogs->total(),
            $blogs->perPage(),
            $blogs->currentPage(),
            ['path' => $request->url(), 'query' => $request->query()]
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function formatBlogForListing(Blog $blog): array
    {
        $body = $blog->body ?? '';
        $wordCount = str_word_count(strip_tags($body));
        $readTimeMin = $wordCount > 0 ? (int) max(1, ceil($wordCount / 200)) : null;

        return [
            'id' => $blog->id,
            'title' => $blog->title,
            'excerpt' => $blog->excerpt,
            'image_url' => $blog->image ?: null,
            'category' => $blog->category?->slug ?? $blog->category?->name ?? 'uncategorized',
            'author' => $blog->author?->full_name ?? $blog->author?->email ?? null,
            'published_at' => $blog->published_at?->toIso8601String(),
            'read_time_min' => $readTimeMin,
        ];
    }
}
