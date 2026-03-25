<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewRequest;
use App\Models\Meal;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MealReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * Latest approved meal reviews (aggregate feed).
     */
    public function all(Request $request): JsonResponse
    {
        $data = $this->reviewService->listAllMealReviews($request);

        return $this->apiSuccess($data, 'Meal reviews fetched successfully.');
    }

    public function index(Request $request, Meal $meal): JsonResponse
    {
        try {
            $paginator = $this->reviewService->listForListing($meal, $request, $request->user());

            return $this->apiSuccess($paginator, 'Reviews fetched successfully.');
        } catch (RuntimeException $e) {
            return $this->apiError($e->getMessage(), 404);
        }
    }

    public function store(StoreReviewRequest $request, Meal $meal): JsonResponse
    {
        try {
            $review = $this->reviewService->createReview($meal, $request->user(), $request->validated());

            return $this->apiSuccess($review, 'Review submitted successfully.', 201);
        } catch (RuntimeException $e) {
            return $this->apiError($e->getMessage(), 422);
        }
    }
}
