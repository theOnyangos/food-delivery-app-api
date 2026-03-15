<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/notifications',
    operationId: 'listNotifications',
    tags: ['Notifications'],
    summary: 'List notifications for the authenticated user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Notifications fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/notifications/unread',
    operationId: 'listUnreadNotifications',
    tags: ['Notifications'],
    summary: 'List unread notifications',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Unread notifications fetched successfully'),
    ]
)]
#[OA\Get(
    path: '/api/notifications/unread-count',
    operationId: 'countUnreadNotifications',
    tags: ['Notifications'],
    summary: 'Get unread notifications count',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Unread count fetched successfully'),
    ]
)]
#[OA\Post(
    path: '/api/notifications/mark-all-read',
    operationId: 'markAllNotificationsRead',
    tags: ['Notifications'],
    summary: 'Mark all notifications as read',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Notifications marked as read'),
    ]
)]
#[OA\Post(
    path: '/api/notifications/{notificationId}/read',
    operationId: 'markNotificationRead',
    tags: ['Notifications'],
    summary: 'Mark a notification as read',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'notificationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Notification marked as read'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Delete(
    path: '/api/notifications/{notificationId}',
    operationId: 'deleteNotification',
    tags: ['Notifications'],
    summary: 'Delete a notification',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'notificationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Notification deleted successfully'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Post(
    path: '/api/notifications/test',
    operationId: 'testNotification',
    tags: ['Notifications'],
    summary: 'Create a test notification',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Test notification created successfully'),
    ]
)]
class NotificationEndpoints
{
}