<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Newsletter', description: 'Public subscription and Super Admin newsletter management')]
#[OA\Post(
    path: '/api/newsletter/subscribe',
    operationId: 'newsletterSubscribe',
    tags: ['Newsletter'],
    summary: 'Subscribe to newsletter (public, no auth)',
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['email'],
            properties: [
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'reader@example.com'),
                new OA\Property(property: 'name', type: 'string', nullable: true, example: 'Jane'),
                new OA\Property(property: 'source', type: 'string', nullable: true, example: 'landing'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Subscribed successfully'),
        new OA\Response(response: 200, description: 'Already subscribed'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/admin/newsletter/send',
    operationId: 'adminNewsletterSend',
    tags: ['Newsletter'],
    summary: 'Queue newsletter email to all subscribed addresses (requires manage newsletter)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['subject', 'body'],
            properties: [
                new OA\Property(property: 'subject', type: 'string', example: 'Monthly update'),
                new OA\Property(property: 'body', type: 'string', format: 'html', example: '<p>Hello!</p>'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 202, description: 'Send queued'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_NEWSLETTER),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/newsletter/subscribers',
    operationId: 'adminNewsletterSubscribersIndex',
    tags: ['Newsletter'],
    summary: 'List subscribers (Yajra DataTables; requires manage newsletter)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'draw', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'length', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['subscribed', 'unsubscribed'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_NEWSLETTER),
    ]
)]
#[OA\Get(
    path: '/api/admin/newsletter/subscribers/{subscriber}',
    operationId: 'adminNewsletterSubscriberShow',
    tags: ['Newsletter'],
    summary: 'Show one subscriber (requires manage newsletter)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'subscriber', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Subscriber fetched'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_NEWSLETTER),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/newsletter/subscribers/{subscriber}/unsubscribe',
    operationId: 'adminNewsletterSubscriberUnsubscribe',
    tags: ['Newsletter'],
    summary: 'Mark subscriber as unsubscribed (requires manage newsletter)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'subscriber', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_NEWSLETTER),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/newsletter/subscribers/{subscriber}',
    operationId: 'adminNewsletterSubscriberDestroy',
    tags: ['Newsletter'],
    summary: 'Delete subscriber (requires manage newsletter)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'subscriber', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_NEWSLETTER),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
class NewsletterEndpoints {}
