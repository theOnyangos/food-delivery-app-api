<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/daily-menus/effective',
    operationId: 'dailyMenusEffective',
    tags: ['Daily menus'],
    summary: 'Effective daily menu for a date (recycles last published if none for that day)',
    description: 'Authenticated users. Returns published menu for the given calendar date, or the latest prior published menu with is_recycled=true. Cached via Redis (tagged).',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date'), description: 'Defaults to today (app timezone)'),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Resolved effective menu payload'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Invalid date'),
    ]
)]
#[OA\Get(
    path: '/api/admin/daily-menus',
    operationId: 'adminDailyMenusDataTables',
    tags: ['Daily menus'],
    summary: 'Daily menus (Yajra DataTables; Super Admin or Admin only)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/admin/daily-menus/stats/summary',
    operationId: 'adminDailyMenusStatsSummary',
    tags: ['Daily menus'],
    summary: 'Daily menu stats summary (phase-1; cached briefly)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'missing_horizon_days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 14)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Stats JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Invalid parameters'),
    ]
)]
#[OA\Get(
    path: '/api/admin/daily-menus/{daily_menu}',
    operationId: 'adminDailyMenusShow',
    tags: ['Daily menus'],
    summary: 'Get one daily menu with items and creator (cached)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Menu detail'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/admin/daily-menus',
    operationId: 'adminDailyMenusStore',
    tags: ['Daily menus'],
    summary: 'Create draft daily menu',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['menu_date'],
            properties: [
                new OA\Property(property: 'menu_date', type: 'string', format: 'date'),
                new OA\Property(property: 'notes', type: 'string', nullable: true),
                new OA\Property(
                    property: 'items',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'meal_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'sort_order', type: 'integer', nullable: true),
                            new OA\Property(property: 'servings_available', type: 'integer', example: 20),
                            new OA\Property(property: 'max_per_order', type: 'integer', nullable: true),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/admin/daily-menus/{daily_menu}',
    operationId: 'adminDailyMenusUpdate',
    tags: ['Daily menus'],
    summary: 'Update daily menu metadata and/or replace items',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'menu_date', type: 'string', format: 'date'),
                new OA\Property(property: 'notes', type: 'string', nullable: true),
                new OA\Property(property: 'items', type: 'array', items: new OA\Items(type: 'object')),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/admin/daily-menus/{daily_menu}/publish',
    operationId: 'adminDailyMenusPublish',
    tags: ['Daily menus'],
    summary: 'Publish daily menu',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Published'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Post(
    path: '/api/admin/daily-menus/{daily_menu}/duplicate',
    operationId: 'adminDailyMenusDuplicate',
    tags: ['Daily menus'],
    summary: 'Duplicate menu as a new draft for another date (copies line items)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['menu_date'],
            properties: [
                new OA\Property(property: 'menu_date', type: 'string', format: 'date'),
                new OA\Property(property: 'notes', type: 'string', nullable: true, description: 'Optional; omit to copy notes from source'),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'New draft created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation failed (e.g. menu_date already exists)'),
    ]
)]
#[OA\Post(
    path: '/api/admin/daily-menus/{daily_menu}/archive',
    operationId: 'adminDailyMenusArchive',
    tags: ['Daily menus'],
    summary: 'Archive daily menu',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Archived'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/daily-menus/{daily_menu}',
    operationId: 'adminDailyMenusDestroy',
    tags: ['Daily menus'],
    summary: 'Delete draft daily menu only',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'daily_menu', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Not a draft'),
    ]
)]
class DailyMenuEndpoints {}
