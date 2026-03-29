<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/pos/daily-menu/today',
    operationId: 'adminPosDailyMenuToday',
    tags: ['POS'],
    summary: 'Today\'s published daily menu for POS (404 if none)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Same shape as admin daily menu detail'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'No published menu for today'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/daily-menus/published',
    operationId: 'adminPosPublishedDailyMenus',
    tags: ['POS'],
    summary: 'List published daily menus for POS (newest first, capped)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Menus list'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Post(
    path: '/api/admin/pos/sales',
    operationId: 'adminPosSalesStore',
    tags: ['POS'],
    summary: 'Record a POS sale (validated against a published daily menu)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['order_type', 'lines'],
            properties: [
                new OA\Property(property: 'order_type', type: 'string'),
                new OA\Property(property: 'daily_menu_id', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'customer_email', type: 'string', format: 'email', nullable: true),
                new OA\Property(
                    property: 'lines',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'meal_id', type: 'string', format: 'uuid'),
                            new OA\Property(property: 'quantity', type: 'integer', minimum: 1),
                        ],
                        type: 'object'
                    )
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Sale created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'No matching published menu (today or daily_menu_id)'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/sales/datatables',
    operationId: 'adminPosSalesDataTables',
    tags: ['POS'],
    summary: 'POS sales for Yajra DataTables (server-side)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'filter_date', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'sold_by_name', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'draw', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'start', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'length', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Yajra DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/pos/sales/{posSale}',
    operationId: 'adminPosSalesDestroy',
    tags: ['POS'],
    summary: 'Delete a POS sale record',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'posSale', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/sales/{posSale}',
    operationId: 'adminPosSalesShow',
    tags: ['POS'],
    summary: 'POS sale detail (lines and totals)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'posSale', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Sale detail'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
class PosEndpoints {}
