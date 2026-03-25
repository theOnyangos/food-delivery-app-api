<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNewsletterRequest;
use App\Models\NewsletterSubscriber;
use App\Services\NewsletterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminNewsletterController extends Controller
{
    public function __construct(
        private readonly NewsletterService $newsletterService
    ) {}

    /**
     * List newsletter subscribers (Yajra DataTables). Optional filter: status (subscribed | unsubscribed).
     */
    public function index(Request $request): mixed
    {
        return $this->newsletterService->getDataTables($request);
    }

    /**
     * Show a single subscriber.
     */
    public function show(NewsletterSubscriber $subscriber): JsonResponse
    {
        return $this->apiSuccess(['subscriber' => $subscriber], 'Subscriber fetched successfully.');
    }

    /**
     * Mark subscriber as unsubscribed.
     */
    public function unsubscribe(NewsletterSubscriber $subscriber): JsonResponse
    {
        $this->newsletterService->unsubscribe($subscriber);

        return $this->apiSuccess(
            ['subscriber' => $subscriber->fresh()],
            'Subscriber marked as unsubscribed.'
        );
    }

    /**
     * Delete a subscriber.
     */
    public function destroy(NewsletterSubscriber $subscriber): JsonResponse
    {
        $subscriber->delete();

        return $this->apiSuccess(null, 'Subscriber deleted.');
    }

    /**
     * Queue sending the newsletter to all subscribed emails. Returns 202.
     */
    public function send(SendNewsletterRequest $request): JsonResponse
    {
        $data = $request->validated();
        $this->newsletterService->sendToAll($data['subject'], $data['body']);

        return $this->apiSuccess(
            null,
            'Newsletter send queued. Emails will be sent to all subscribed addresses.',
            202
        );
    }
}
