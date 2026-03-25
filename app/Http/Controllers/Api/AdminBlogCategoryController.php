<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBlogCategoryRequest;
use App\Http\Requests\UpdateBlogCategoryRequest;
use App\Models\BlogCategory;
use App\Services\BlogCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminBlogCategoryController extends Controller
{
    public function __construct(
        private readonly BlogCategoryService $blogCategoryService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->blogCategoryService->getDataTables($request);
    }

    public function store(StoreBlogCategoryRequest $request): JsonResponse
    {
        $category = $this->blogCategoryService->create($request->validated());

        return $this->apiSuccess($category, 'Blog category created successfully.', 201);
    }

    public function show(BlogCategory $blog_category): JsonResponse
    {
        return $this->apiSuccess($blog_category, 'Blog category fetched successfully.');
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blog_category): JsonResponse
    {
        $category = $this->blogCategoryService->update($blog_category, $request->validated());

        return $this->apiSuccess($category, 'Blog category updated successfully.');
    }

    public function destroy(BlogCategory $blog_category): JsonResponse
    {
        $this->blogCategoryService->delete($blog_category);

        return $this->apiSuccess(null, 'Blog category deleted.');
    }
}
