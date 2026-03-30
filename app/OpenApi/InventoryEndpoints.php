<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/inventory/summary',
    operationId: 'adminInventorySummary',
    tags: ['Inventory'],
    summary: 'Inventory KPI summary (counts)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Summary counts'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/items/datatables',
    operationId: 'adminInventoryItemsDataTables',
    tags: ['Inventory'],
    summary: 'Inventory items (Yajra DataTables)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['good', 'low_stock', 'out_of_stock', 'expired'])),
        new OA\Parameter(name: 'location', in: 'query', required: false, description: 'Partial match on storage_location', schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'draw', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'start', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'length', in: 'query', schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'search[value]', in: 'query', schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/items/options',
    operationId: 'adminInventoryItemOptions',
    tags: ['Inventory'],
    summary: 'Lightweight item list for dropdowns (shift usage, etc.)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'List of { id, sku, name, quantity, unit }'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Post(
    path: '/api/admin/inventory/items',
    operationId: 'adminInventoryItemStore',
    tags: ['Inventory'],
    summary: 'Create inventory item',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'quantity', 'unit'],
            properties: [
                new OA\Property(property: 'sku', type: 'string', nullable: true),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'image_url', type: 'string', nullable: true),
                new OA\Property(property: 'quantity', type: 'number', format: 'float'),
                new OA\Property(property: 'unit', type: 'string', enum: ['g', 'kg', 'L', 'ml', 'pcs']),
                new OA\Property(property: 'storage_location', type: 'string', nullable: true),
                new OA\Property(property: 'storage_temperature_celsius', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'expiration_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'low_stock_threshold', type: 'number', format: 'float', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/items/{inventoryItem}',
    operationId: 'adminInventoryItemShow',
    tags: ['Inventory'],
    summary: 'Get inventory item',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'inventoryItem', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Item detail'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Put(
    path: '/api/admin/inventory/items/{inventoryItem}',
    operationId: 'adminInventoryItemUpdate',
    tags: ['Inventory'],
    summary: 'Update inventory item',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'inventoryItem', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'sku', type: 'string', nullable: true),
                new OA\Property(property: 'name', type: 'string'),
                new OA\Property(property: 'image_url', type: 'string', nullable: true),
                new OA\Property(property: 'quantity', type: 'number', format: 'float'),
                new OA\Property(property: 'unit', type: 'string'),
                new OA\Property(property: 'storage_location', type: 'string', nullable: true),
                new OA\Property(property: 'storage_temperature_celsius', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'expiration_date', type: 'string', format: 'date', nullable: true),
                new OA\Property(property: 'low_stock_threshold', type: 'number', format: 'float', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/inventory/items/{inventoryItem}',
    operationId: 'adminInventoryItemDestroy',
    tags: ['Inventory'],
    summary: 'Delete inventory item',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'inventoryItem', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/items/{inventoryItem}/movements',
    operationId: 'adminInventoryItemMovements',
    tags: ['Inventory'],
    summary: 'Movement history for an item',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'inventoryItem', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Movement rows'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/items/{inventoryItem}/analytics',
    operationId: 'adminInventoryItemAnalytics',
    tags: ['Inventory'],
    summary: 'Aggregated analytics for one item (daily usage, by user, movement volume by type)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'inventoryItem', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'from', in: 'query', required: true, description: 'YYYY-MM-DD start (inclusive)', schema: new OA\Schema(type: 'string', format: 'date')),
        new OA\Parameter(name: 'to', in: 'query', required: true, description: 'YYYY-MM-DD end (inclusive), max 366 days span', schema: new OA\Schema(type: 'string', format: 'date')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Analytics payload: item, range, summary, daily_usage, cumulative_usage, usage_by_user, movement_volume_by_type'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/admin/inventory/usage',
    operationId: 'adminInventoryRecordUsage',
    tags: ['Inventory'],
    summary: 'Record shift-close usage (decrements stock, ledger entries)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['lines'],
            properties: [
                new OA\Property(property: 'occurred_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(property: 'notes', type: 'string', nullable: true),
                new OA\Property(
                    property: 'lines',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'inventory_item_id', type: 'string', format: 'uuid', nullable: true),
                            new OA\Property(property: 'sku', type: 'string', nullable: true),
                            new OA\Property(property: 'quantity_used', type: 'number', format: 'float'),
                        ],
                        type: 'object'
                    )
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Usage correlation id and item ids'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/export.csv',
    operationId: 'adminInventoryExportCsv',
    tags: ['Inventory'],
    summary: 'Download current inventory as CSV',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'CSV file'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Get(
    path: '/api/admin/inventory/template.csv',
    operationId: 'adminInventoryTemplateCsv',
    tags: ['Inventory'],
    summary: 'Download CSV import template',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'CSV file'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Post(
    path: '/api/admin/inventory/import',
    operationId: 'adminInventoryImportCsv',
    tags: ['Inventory'],
    summary: 'Import inventory CSV (multipart file)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Import summary { imported, updated, errors, batch_id }'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
class InventoryEndpoints {}
