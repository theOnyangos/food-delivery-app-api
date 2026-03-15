<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Models\Notification;
use App\Models\PersonalAccessToken;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class NotificationController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function index(Request $request): StreamedResponse
    {
        set_time_limit(0);

        $this->validateToken($request);
        $user = $request->user();

        return response()->stream(function () use ($user): void {
            $lastSentAt = null;

            while (true) {
                if (connection_aborted()) {
                    break;
                }

                $query = Notification::query()
                    ->where('user_id', $user->id)
                    ->where('is_read', false)
                    ->orderByDesc('created_at');

                if ($lastSentAt !== null) {
                    $query->where('created_at', '>', $lastSentAt);
                }

                $notifications = $query->limit(10)->get();

                if ($notifications->isNotEmpty()) {
                    echo "event: notification\n";
                    echo 'data: '.json_encode([
                        'success' => true,
                        'data' => NotificationResource::collection($notifications)->resolve(),
                        'count' => $notifications->count(),
                        'timestamp' => now()->toDateTimeString(),
                    ])."\n\n";

                    if (ob_get_level() > 0) {
                        @ob_flush();
                    }

                    @flush();

                    $lastSentAt = $notifications->first()->created_at;
                }

                echo ": heartbeat\n\n";

                if (ob_get_level() > 0) {
                    @ob_flush();
                }

                @flush();
                sleep(5);
            }
        }, 200, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Content-Type' => 'text/event-stream',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function datatable(Request $request): JsonResponse
    {
        $user = $request->user();
        $query = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->boolean('unread_only')) {
            $query->where('is_read', false);
        }

        /** @var JsonResponse $response */
        $response = DataTables::eloquent($query)
            ->setTransformer(fn (Notification $notification) => (new NotificationResource($notification))->resolve())
            ->toJson();

        return $response;
    }

    public function getUnreadNotifications(Request $request): JsonResponse
    {
        $notifications = Notification::query()
            ->where('user_id', $request->user()->id)
            ->where('is_read', false)
            ->orderByDesc('created_at')
            ->get();

        return $this->apiSuccess([
            'items' => NotificationResource::collection($notifications),
            'count' => $notifications->count(),
        ], 'Unread notifications fetched successfully.');
    }

    public function getUnreadCount(Request $request): JsonResponse
    {
        return $this->apiSuccess([
            'count' => $this->notificationService->getUnreadCount($request->user()),
        ], 'Unread count fetched successfully.');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $this->notificationService->markAllAsRead($request->user());

        return $this->apiSuccess(['marked_count' => $count], "Marked {$count} notification(s) as read.");
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = Notification::query()->findOrFail($notificationId);

        if ((string) $notification->user_id !== (string) $request->user()->id) {
            return $this->apiError('Unauthorized to mark this notification as read.', 403);
        }

        $this->notificationService->markAsRead($notification);

        return $this->apiSuccess(new NotificationResource($notification->fresh()), 'Notification marked as read.');
    }

    public function delete(Request $request, string $notificationId): JsonResponse
    {
        $notification = Notification::query()->findOrFail($notificationId);

        if ((string) $notification->user_id !== (string) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this notification.',
            ], 403);
        }

        $this->notificationService->delete($notification);

        return response()->json([
            'success' => true,
            'message' => 'Notification deleted successfully',
        ]);
    }

    public function testNotification(Request $request): JsonResponse
    {
        $user = $request->user();

        $notification = $this->notificationService->create($user, 'test_notification', [
            'title' => 'Test Notification',
            'message' => 'This is a test notification to verify the notification system is working correctly.',
            'test_data' => [
                'timestamp' => now()->toDateTimeString(),
                'user_id' => $user->id,
                'user_name' => $user->full_name,
            ],
            'action_url' => config('app.url').'/notifications',
        ]);

        return $this->apiSuccess([
            'notification' => new NotificationResource($notification),
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ], 'Test notification created and broadcasted successfully.');
    }

    private function validateToken(Request $request): void
    {
        if (! $request->user() && $token = $request->bearerToken()) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken !== null && $accessToken->tokenable !== null) {
                auth()->setUser($accessToken->tokenable);
                $request->setUserResolver(fn () => $accessToken->tokenable);
            }
        }

        if (! $request->user() && $token = $request->query('api_token')) {
            $accessToken = PersonalAccessToken::findToken($token);

            if ($accessToken !== null && $accessToken->tokenable !== null) {
                auth()->setUser($accessToken->tokenable);
                $request->setUserResolver(fn () => $accessToken->tokenable);
            }
        }

        if (! $request->user()) {
            Log::warning('Unauthorized notification stream attempt.');
            abort(401, 'Unauthorized');
        }

        $user = $request->user();

        if (! $user->hasAnyRole(['Super Admin', 'Admin', 'Partner']) && ! $user->canAny(['view notifications', 'manage notifications'])) {
            abort(403, 'Forbidden');
        }
    }
}