<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SubscribeNewsletterRequest;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * Public subscribe to newsletter. Idempotent for already-subscribed emails; resubscribes if previously unsubscribed.
     */
    public function subscribe(SubscribeNewsletterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $existing = NewsletterSubscriber::query()
            ->where('email', $data['email'])
            ->first();

        $wasSubscribed = $existing !== null && $existing->isSubscribed();
        $subscriber = $this->newsletterService->subscribe(
            $data['email'],
            $data['name'] ?? null,
            $data['source'] ?? null
        );

        $payload = [
            'subscriber' => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
            ],
        ];

        if ($wasSubscribed) {
            return $this->apiSuccess($payload, 'You are already subscribed to our newsletter.', 200);
        }

        return $this->apiSuccess($payload, 'Thank you for subscribing to our newsletter.', 201);
    }
}
