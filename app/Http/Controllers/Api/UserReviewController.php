<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Meal;
use App\Models\Review;
use App\Services\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService
    ) {}

    /**
     * List the authenticated user's approved meal reviews.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = min((int) $request->query('per_page', 20), 50);

        $paginator = Review::query()
            ->where('user_id', $user->id)
            ->where('reviewable_type', Meal::class)
            ->approved()
            ->with(['reviewable' => function ($morphTo) {
                $morphTo->morphWith([
                    Meal::class => [],
                ]);
            }])
            ->latest('created_at')
            ->paginate($perPage);

        $paginator->setCollection(
            $paginator->getCollection()->map(function (Review $review): array {
                $meal = $review->reviewable;
                $title = $meal?->title ?? 'Unknown';

                return [
                    'id' => $review->id,
                    'item_type' => 'meal',
                    'item_id' => $review->reviewable_id,
                    'item_title' => $title,
                    'item_image_url' => $meal?->thumbnail_image,
                    'rating' => (int) $review->rating,
                    'comment' => $review->message ?? '',
                    'created_at' => $review->created_at?->toIso8601String(),
                    'is_verified' => false,
                ];
            })
        );

        return $this->apiSuccess($paginator, 'Reviews fetched successfully.');
    }

    public function destroy(Request $request, Review $review): JsonResponse
    {
        if ($review->user_id !== $request->user()->id) {
            throw new NotFoundHttpException('Review not found.');
        }

        if ($review->reviewable_type !== Meal::class) {
            throw new NotFoundHttpException('Review not found.');
        }

        $this->reviewService->delete($review);

        return $this->apiSuccess(null, 'Review deleted.');
    }
}
