<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReviewTopicRequest;
use App\Http\Requests\UpdateReviewTopicRequest;
use App\Models\ReviewTopic;
use App\Services\ReviewTopicService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewTopicController extends Controller
{
    public function __construct(
        private readonly ReviewTopicService $reviewTopicService
    ) {}

    public function index(Request $request): mixed
    {
        return $this->reviewTopicService->getDataTables($request);
    }

    public function list(): JsonResponse
    {
        $topics = $this->reviewTopicService->getAllOrdered();

        return $this->apiSuccess(['topics' => $topics], 'Review topics fetched successfully.');
    }

    public function store(StoreReviewTopicRequest $request): JsonResponse
    {
        $topic = $this->reviewTopicService->create($request->validated());

        return $this->apiSuccess($topic, 'Review topic created successfully.', 201);
    }

    public function show(ReviewTopic $review_topic): JsonResponse
    {
        return $this->apiSuccess($review_topic, 'Review topic fetched successfully.');
    }

    public function update(UpdateReviewTopicRequest $request, ReviewTopic $review_topic): JsonResponse
    {
        $topic = $this->reviewTopicService->update($review_topic, $request->validated());

        return $this->apiSuccess($topic, 'Review topic updated successfully.');
    }

    public function destroy(ReviewTopic $review_topic): JsonResponse
    {
        $this->reviewTopicService->delete($review_topic);

        return $this->apiSuccess(null, 'Review topic deleted.');
    }
}
