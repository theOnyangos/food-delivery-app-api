<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BlogCacheService;
use App\Services\BlogCategoryService;
use App\Services\BlogListingService;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class PublicBlogController extends Controller
{
    public function __construct(
        private readonly BlogCacheService $blogCache,
        private readonly BlogCategoryService $blogCategoryService,
        private readonly BlogListingService $blogListingService,
        private readonly BlogService $blogService
    ) {}

    public function recent(Request $request): JsonResponse
    {
        $limit = min(max((int) $request->query('limit', 12), 1), 24);
        $ttl = (int) config('blogs.cache_ttl', 300);
        $key = 'blogs_recent_'.$limit;
        $data = $this->blogCache->remember($key, $ttl, fn (): array => $this->blogService->getRecentBlogs($limit));

        return $this->apiSuccess($data, 'Recent blogs fetched successfully.');
    }

    public function categories(): JsonResponse
    {
        $ttl = (int) config('blogs.cache_ttl', 300);
        $key = 'blogs_categories_v1';
        $categories = $this->blogCache->remember($key, $ttl, function (): array {
            return $this->blogCategoryService->getAll()->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'slug' => $c->slug,
                'description' => $c->description,
                'image' => $c->image,
            ])->all();
        });

        return $this->apiSuccess(['categories' => $categories], 'Blog categories fetched successfully.');
    }

    public function index(Request $request): JsonResponse
    {
        $ttl = (int) config('blogs.cache_ttl', 300);
        $queryString = $request->getQueryString() ?? '';
        $key = 'blogs_index_'.md5($queryString);
        /** @var LengthAwarePaginator<int, array<string, mixed>> $paginator */
        $paginator = $this->blogCache->remember($key, $ttl, fn (): LengthAwarePaginator => $this->blogListingService->getPublishedBlogs($request));

        return $this->apiSuccess([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 'Blogs fetched successfully.');
    }

    public function show(string $slugOrId): JsonResponse
    {
        $ttl = (int) config('blogs.cache_ttl', 300);
        $key = 'blogs_show_'.md5($slugOrId);
        $data = $this->blogCache->remember($key, $ttl, fn (): ?array => $this->blogService->findPublishedArrayBySlugOrId($slugOrId));

        if ($data === null) {
            return $this->apiError('Blog not found.', 404);
        }

        return $this->apiSuccess($data, 'Blog fetched successfully.');
    }
}
