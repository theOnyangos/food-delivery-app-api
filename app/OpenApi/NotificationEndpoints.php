<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/notifications/stream',
    operationId: 'notificationStream',
    tags: ['Notifications'],
    summary: 'Server-Sent Events stream for real-time notifications',
    description: 'Opens a persistent SSE connection that pushes unread notifications as `event: notification` messages and heartbeat comments every 5 seconds. Authenticate by passing a Sanctum token as a Bearer header or via the `api_token` query parameter.',
    parameters: [
        new OA\Parameter(
            name: 'api_token',
            in: 'query',
            required: false,
            description: 'Sanctum personal access token (alternative to Authorization header for SSE clients)',
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'SSE stream opened successfully',
            content: new OA\MediaType(
                mediaType: 'text/event-stream',
                schema: new OA\Schema(type: 'string', example: "event: notification\ndata: {\"success\":true,\"data\":[],\"count\":1,\"timestamp\":\"2026-03-15 12:00:00\"}\n\n")
            )
        ),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
    ]
)]
#[OA\Get(
    path: '/api/notifications/datatable',
    operationId: 'listNotificationsDatatable',
    tags: ['Notifications'],
    summary: 'Yajra DataTables list of notifications for the authenticated user',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'draw', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'length', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'search[value]', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'unread_only', in: 'query', required: false, schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Notifications fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
    ]
)]
#[OA\Get(
    path: '/api/notifications/preferences',
    operationId: 'getNotificationPreferences',
    tags: ['Notifications'],
    summary: 'Get notification preferences for authenticated user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Notification preferences fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Put(
    path: '/api/notifications/preferences',
    operationId: 'updateNotificationPreferences',
    tags: ['Notifications'],
    summary: 'Update notification preferences for authenticated user',
    description: 'Supports enabling/disabling all notifications, selecting notification types, toggling email notifications, and toggling SMS notifications. When SMS notifications are enabled, a valid phone number is required in E.164-like format (e.g. +254712345678).',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'notifications_enabled', type: 'boolean', example: true),
                new OA\Property(
                    property: 'notification_types',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['system', 'security', 'transaction']
                ),
                new OA\Property(property: 'email_notifications_enabled', type: 'boolean', example: true),
                new OA\Property(property: 'sms_notifications_enabled', type: 'boolean', example: false),
                new OA\Property(property: 'sms_phone_number', type: 'string', nullable: true, example: '+254712345678'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Notification preferences updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/notifications/preferences',
    operationId: 'patchNotificationPreferences',
    tags: ['Notifications'],
    summary: 'Partially update notification preferences for authenticated user',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: false,
        content: new OA\JsonContent(
            type: 'object',
            properties: [
                new OA\Property(property: 'notifications_enabled', type: 'boolean', example: true),
                new OA\Property(
                    property: 'notification_types',
                    type: 'array',
                    items: new OA\Items(type: 'string'),
                    example: ['system', 'security', 'transaction']
                ),
                new OA\Property(property: 'email_notifications_enabled', type: 'boolean', example: true),
                new OA\Property(property: 'sms_notifications_enabled', type: 'boolean', example: false),
                new OA\Property(property: 'sms_phone_number', type: 'string', nullable: true, example: '+254712345678'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Notification preferences updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
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
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_NOTIFICATIONS),
    ]
)]
class NotificationEndpoints {}
