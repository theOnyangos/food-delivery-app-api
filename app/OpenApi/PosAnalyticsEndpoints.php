<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/pos/analytics/summary',
    operationId: 'adminPosAnalyticsSummary',
    tags: ['POS'],
    summary: 'POS sales analytics summary (KPIs, optional daily series)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, description: 'Start date (YYYY-MM-DD); default last 30 days', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, description: 'End date (YYYY-MM-DD); default today', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'include_daily_series', in: 'query', required: false, description: 'Include daily revenue and order counts', schema: new OA\Schema(type: 'boolean')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Summary payload'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Invalid date range or exceeds max days'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/analytics/by-menu',
    operationId: 'adminPosAnalyticsByMenu',
    tags: ['POS'],
    summary: 'POS sales aggregated by daily menu',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Rows by daily menu'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Invalid date range'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/analytics/by-salesperson',
    operationId: 'adminPosAnalyticsBySalesperson',
    tags: ['POS'],
    summary: 'POS sales aggregated by salesperson',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Rows by user'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Invalid date range'),
    ]
)]
#[OA\Get(
    path: '/api/admin/pos/analytics/meals',
    operationId: 'adminPosAnalyticsMeals',
    tags: ['POS'],
    summary: 'Top meals by revenue or quantity from POS line snapshots',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'from', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'sort', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['revenue', 'quantity'], default: 'revenue')),
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 100, default: 20)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Top meals rows'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Invalid date range'),
    ]
)]
class PosAnalyticsEndpoints {}
