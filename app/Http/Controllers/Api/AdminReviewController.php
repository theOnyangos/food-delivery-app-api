<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->reviewService->listAllForAdmin($request);
    }

    public function show(Review $review): JsonResponse
    {
        $review = $this->reviewService->showForAdmin($review);

        return $this->apiSuccess($review, 'Review fetched successfully.');
    }

    public function updateStatus(Request $request, Review $review): JsonResponse
    {
        $status = $request->input('status', 'pending');
        if (! in_array($status, ['approved', 'pending', 'rejected'], true)) {
            return $this->apiError('Invalid status. Use approved, pending, or rejected.', 422);
        }
        $review = $this->reviewService->updateStatus($review, $status);

        return $this->apiSuccess($review, 'Review status updated.');
    }

    public function destroy(Review $review): JsonResponse
    {
        $this->reviewService->delete($review);

        return $this->apiSuccess(null, 'Review deleted.');
    }
}
