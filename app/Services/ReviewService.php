<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Meal;
use App\Models\Review;
use App\Models\ReviewCategory;
use App\Models\ReviewTopic;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use RuntimeException;
use Yajra\DataTables\Facades\DataTables;

class ReviewService
{
    public function __construct(
        private readonly RedisService $redis,
        private readonly NotificationService $notificationService
    ) {}

    public function createReview(Meal $meal, User $user, array $data): Review
    {
        $this->ensureMealPublished($meal);

        $rating = (int) ($data['rating'] ?? 0);
        if ($rating < 1 || $rating > 5) {
            throw new InvalidArgumentException('Rating must be between 1 and 5.');
        }

        $message = trim($data['message'] ?? '');
        if ($message === '') {
            throw new InvalidArgumentException('Message is required.');
        }

        $review = Review::query()->create([
            'user_id' => $user->id,
            'reviewable_type' => Meal::class,
            'reviewable_id' => $meal->id,
            'rating' => $rating,
            'message' => $message,
            'status' => $data['status'] ?? 'approved',
        ]);

        $categoryIds = $data['category_ids'] ?? [];
        if (is_array($categoryIds) && $categoryIds !== []) {
            $review->categories()->sync(
                ReviewCategory::query()->whereIn('id', $categoryIds)->pluck('id')->all()
            );
        }

        $topicIds = $data['topic_ids'] ?? [];
        if (is_array($topicIds) && $topicIds !== []) {
            $review->topics()->sync(
                ReviewTopic::query()->whereIn('id', $topicIds)->pluck('id')->all()
            );
        }

        $this->invalidateListingCache($meal);
        $this->invalidateAllMealReviewsCache();
        $this->notifyMealOwnerAndSuperAdminOfNewReview($review, $meal, $user);

        return $review->fresh(['user:id,first_name,middle_name,last_name,email', 'categories', 'topics']);
    }

    public function listForListing(Meal $meal, Request $request, User $viewer): LengthAwarePaginator
    {
        $this->ensureMealReviewsReadable($meal, $viewer);

        $tag = $this->reviewListingTag($meal);
        $queryString = $request->getQueryString() ?? '';
        $key = 'list_'.md5($queryString);
        $ttl = (int) config('reviews.cache_ttl', 300);

        return $this->redis->rememberWithTags($tag, $key, $ttl, function () use ($meal, $request): LengthAwarePaginator {
            $perPage = min((int) $request->query('per_page', 15), 50);
            $query = Review::query()
                ->where('reviewable_type', Meal::class)
                ->where('reviewable_id', $meal->id)
                ->approved()
                ->with(['user:id,first_name,middle_name,last_name,email', 'categories', 'topics'])
                ->latest('created_at');

            if ($request->filled('category_id')) {
                $query->whereHas('categories', fn ($q) => $q->where('asl_review_categories.id', $request->input('category_id')));
            }
            if ($request->filled('topic_id')) {
                $query->whereHas('topics', fn ($q) => $q->where('asl_review_topics.id', $request->input('topic_id')));
            }
            if ($request->filled('rating')) {
                $query->where('rating', (int) $request->input('rating'));
            }

            return $query->paginate($perPage);
        });
    }

    public function listForPartnerListing(Meal $meal, User $partner, Request $request): LengthAwarePaginator
    {
        $this->ensureMealBelongsToPartner($meal, $partner);

        return $this->listForListing($meal, $request, $partner);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listAllMealReviews(Request $request): array
    {
        $limit = min((int) $request->query('limit', 12), 50);
        $tag = (string) config('reviews.all_meal_reviews_tag', 'meal_reviews_all');
        $queryString = $request->getQueryString() ?? '';
        $key = 'all_meal_'.md5($queryString);
        $ttl = (int) config('reviews.cache_ttl', 300);

        return $this->redis->rememberWithTags($tag, $key, $ttl, function () use ($limit): array {
            $reviews = Review::query()
                ->where('reviewable_type', Meal::class)
                ->approved()
                ->with(['user:id,first_name,middle_name,last_name,email', 'reviewable:id,title,thumbnail_image'])
                ->latest('created_at')
                ->limit($limit)
                ->get();

            return $reviews->map(function (Review $r): array {
                $message = $r->message ?? '';
                $firstLine = str_contains($message, "\n") ? strstr($message, "\n", true) : $message;
                $title = trim((string) $firstLine) !== '' ? Str::limit(trim((string) $firstLine), 80) : 'Review';

                return [
                    'id' => $r->id,
                    'author_name' => $r->user?->full_name ?? 'Anonymous',
                    'rating' => (int) $r->rating,
                    'title' => $title,
                    'body' => $message,
                    'meal_id' => $r->reviewable_id,
                    'meal_title' => $r->reviewable?->title ?? null,
                    'meal_thumbnail' => $r->reviewable?->thumbnail_image ?? null,
                    'created_at' => $r->created_at?->toIso8601String(),
                ];
            })->all();
        });
    }

    public function listAllForAdmin(Request $request): mixed
    {
        $query = Review::query()
            ->with(['user:id,first_name,middle_name,last_name,email', 'reviewable', 'categories', 'topics'])
            ->latest('created_at');

        if ($request->filled('reviewable_type')) {
            $type = $request->input('reviewable_type');
            if ($type === 'meal') {
                $query->where('reviewable_type', Meal::class);
            }
        }
        if ($request->filled('reviewable_id')) {
            $query->where('reviewable_id', $request->input('reviewable_id'));
        }
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }
        if ($request->filled('category_id')) {
            $query->whereHas('categories', fn ($q) => $q->where('asl_review_categories.id', $request->input('category_id')));
        }
        if ($request->filled('topic_id')) {
            $query->whereHas('topics', fn ($q) => $q->where('asl_review_topics.id', $request->input('topic_id')));
        }
        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        return DataTables::eloquent($query)
            ->addColumn('reviewable_title', fn (Review $r) => $r->reviewable?->title ?? '-')
            ->toJson();
    }

    public function showForAdmin(Review $review): Review
    {
        $review->load(['user', 'reviewable.owner', 'categories', 'topics']);

        return $review;
    }

    public function updateStatus(Review $review, string $status): Review
    {
        if (! in_array($status, ['approved', 'pending', 'rejected'], true)) {
            $status = 'pending';
        }
        $review->update(['status' => $status]);

        $reviewable = $review->reviewable;
        if ($reviewable instanceof Meal) {
            $this->invalidateListingCache($reviewable);
        }
        $this->invalidateAllMealReviewsCache();
        $this->notifyReviewAuthorOfStatusChange($review, $status);

        return $review->fresh(['user', 'reviewable', 'categories', 'topics']);
    }

    public function delete(Review $review): bool
    {
        $reviewable = $review->reviewable;
        $deleted = $review->delete();
        if ($deleted && $reviewable instanceof Meal) {
            $this->invalidateListingCache($reviewable);
            $this->invalidateAllMealReviewsCache();
        }

        return $deleted;
    }

    private function ensureMealPublished(Meal $meal): void
    {
        if ($meal->status !== 'published') {
            throw new RuntimeException('Meal is not published.');
        }
    }

    private function ensureMealReviewsReadable(Meal $meal, User $viewer): void
    {
        if ($meal->status === 'published') {
            return;
        }
        if ((string) $meal->user_id === (string) $viewer->id) {
            return;
        }
        if ($viewer->hasRole('Super Admin')) {
            return;
        }

        throw new RuntimeException('Meal is not published.');
    }

    private function ensureMealBelongsToPartner(Meal $meal, User $partner): void
    {
        if ($partner->hasRole('Super Admin')) {
            return;
        }
        if ((string) $meal->user_id !== (string) $partner->id) {
            throw new RuntimeException('Meal does not belong to your account.');
        }
    }

    private function reviewListingTag(Meal $meal): string
    {
        $prefix = config('reviews.cache_tag_prefix', 'review_listing');

        return $prefix.':'.Meal::class.':'.$meal->id;
    }

    private function invalidateListingCache(Meal $meal): bool
    {
        return $this->redis->flushTag($this->reviewListingTag($meal));
    }

    private function invalidateAllMealReviewsCache(): bool
    {
        $tag = (string) config('reviews.all_meal_reviews_tag', 'meal_reviews_all');

        return $this->redis->flushTag($tag);
    }

    private function notifyMealOwnerAndSuperAdminOfNewReview(Review $review, Meal $meal, User $author): void
    {
        $meal->loadMissing('owner');
        $listingTitle = $meal->title ?? ('Meal '.$meal->id);
        $authorName = $author->full_name ?: $author->email;

        $owner = $meal->owner;
        if ($owner !== null && (string) $owner->id !== (string) $author->id) {
            $this->notificationService->create($owner, 'new_review', [
                'title' => 'New review on your meal',
                'message' => sprintf('%s left a %d-star review on "%s".', $authorName, $review->rating, $listingTitle),
                'review_id' => $review->id,
                'reviewable_type' => $review->reviewable_type,
                'reviewable_id' => $review->reviewable_id,
                'rating' => $review->rating,
                'author_id' => $author->id,
            ]);
        }

        $superAdmin = $this->notificationService->getSuperAdminUser();
        if ($superAdmin !== null) {
            $this->notificationService->create($superAdmin, 'admin_new_review', [
                'title' => 'New review submitted',
                'message' => sprintf('%s submitted a %d-star review for "%s".', $authorName, $review->rating, $listingTitle),
                'review_id' => $review->id,
                'reviewable_type' => $review->reviewable_type,
                'reviewable_id' => $review->reviewable_id,
                'user_id' => $author->id,
                'rating' => $review->rating,
            ]);
        }
    }

    private function notifyReviewAuthorOfStatusChange(Review $review, string $status): void
    {
        $author = $review->user;
        if ($author === null) {
            return;
        }

        $message = match ($status) {
            'approved' => 'Your review has been approved and is now visible.',
            'rejected' => 'Your review has been rejected.',
            default => 'Your review status was updated to '.$status.'.',
        };

        $this->notificationService->create($author, 'review_status_updated', [
            'title' => 'Review status updated',
            'message' => $message,
            'review_id' => $review->id,
            'status' => $status,
        ]);
    }
}
