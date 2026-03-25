<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewCategoryRequest;
use App\Http\Requests\UpdateReviewCategoryRequest;
use App\Models\ReviewCategory;
use App\Services\ReviewCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewCategoryController extends Controller
{
    public function __construct(
        private readonly ReviewCategoryService $reviewCategoryService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->reviewCategoryService->getDataTables($request);
    }

    public function list(): JsonResponse
    {
        $categories = $this->reviewCategoryService->getAllOrdered();

        return $this->apiSuccess(['categories' => $categories], 'Review categories fetched successfully.');
    }

    public function store(StoreReviewCategoryRequest $request): JsonResponse
    {
        $category = $this->reviewCategoryService->create($request->validated());

        return $this->apiSuccess($category, 'Review category created successfully.', 201);
    }

    public function show(ReviewCategory $review_category): JsonResponse
    {
        return $this->apiSuccess($review_category, 'Review category fetched successfully.');
    }

    public function update(UpdateReviewCategoryRequest $request, ReviewCategory $review_category): JsonResponse
    {
        $category = $this->reviewCategoryService->update($review_category, $request->validated());

        return $this->apiSuccess($category, 'Review category updated successfully.');
    }

    public function destroy(ReviewCategory $review_category): JsonResponse
    {
        $this->reviewCategoryService->delete($review_category);

        return $this->apiSuccess(null, 'Review category deleted.');
    }
}
