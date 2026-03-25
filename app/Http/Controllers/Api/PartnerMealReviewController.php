<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class PartnerMealReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * List reviews for a meal owned by the current partner (or Super Admin).
     */
    public function index(Request $request, Meal $meal): JsonResponse
    {
        try {
            $paginator = $this->reviewService->listForPartnerListing($meal, $request->user(), $request);

            return $this->apiSuccess($paginator, 'Reviews fetched successfully.');
        } catch (RuntimeException $e) {
            return $this->apiError($e->getMessage(), 403);
        }
    }
}
