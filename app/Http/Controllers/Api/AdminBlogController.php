<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogRequest;
use App\Http\Requests\UpdateBlogRequest;
use App\Models\Blog;
use App\Services\BlogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogController extends Controller
{
    public function __construct(
        private readonly BlogService $blogService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->blogService->getDataTables($request);
    }

    public function store(StoreBlogRequest $request): JsonResponse
    {
        $blog = $this->blogService->create($request->validated(), $request->user());

        return $this->apiSuccess($blog, 'Blog created successfully.', 201);
    }

    public function show(Blog $blog): JsonResponse
    {
        $blog->load('category', 'author');

        return $this->apiSuccess($blog, 'Blog fetched successfully.');
    }

    public function update(UpdateBlogRequest $request, Blog $blog): JsonResponse
    {
        $blog = $this->blogService->update($blog, $request->validated(), $request->user());

        return $this->apiSuccess($blog, 'Blog updated successfully.');
    }

    public function destroy(Blog $blog): JsonResponse
    {
        $this->blogService->delete($blog);

        return $this->apiSuccess(null, 'Blog deleted.');
    }
}
