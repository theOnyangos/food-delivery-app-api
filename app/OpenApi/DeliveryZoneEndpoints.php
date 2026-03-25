<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/delivery-zones',
    operationId: 'listDeliveryZones',
    tags: ['Delivery Zones'],
    summary: 'List delivery zones (Admin)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['active', 'inactive'])),
        new OA\Parameter(name: 'zip_code', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Delivery zones fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ZONES),
    ]
)]
#[OA\Post(
    path: '/api/admin/delivery-zones',
    operationId: 'createDeliveryZone',
    tags: ['Delivery Zones'],
    summary: 'Create a delivery zone (Admin)',
    description: 'Creates a delivery zone and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name', 'zip_code', 'delivery_fee'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Westlands'),
                new OA\Property(property: 'zip_code', type: 'string', example: '00100'),
                new OA\Property(property: 'delivery_fee', type: 'number', format: 'float', example: 150),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                new OA\Property(property: 'minimum_order_amount', type: 'integer', nullable: true, example: 1000),
                new OA\Property(property: 'estimated_delivery_minutes', type: 'integer', nullable: true, example: 45),
                new OA\Property(property: 'is_serviceable', type: 'boolean', example: true),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Delivery zone created successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ZONES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Get(
    path: '/api/admin/delivery-zones/{deliveryZone}',
    operationId: 'showDeliveryZone',
    tags: ['Delivery Zones'],
    summary: 'Get delivery zone details (Admin)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryZone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Delivery zone fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ZONES),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Put(
    path: '/api/admin/delivery-zones/{deliveryZone}',
    operationId: 'updateDeliveryZone',
    tags: ['Delivery Zones'],
    summary: 'Update delivery zone (Admin)',
    description: 'Updates a delivery zone and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryZone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Westlands'),
                new OA\Property(property: 'zip_code', type: 'string', example: '00100'),
                new OA\Property(property: 'delivery_fee', type: 'number', format: 'float', example: 200),
                new OA\Property(property: 'status', type: 'string', enum: ['active', 'inactive'], example: 'active'),
                new OA\Property(property: 'minimum_order_amount', type: 'integer', nullable: true, example: 1200),
                new OA\Property(property: 'estimated_delivery_minutes', type: 'integer', nullable: true, example: 40),
                new OA\Property(property: 'is_serviceable', type: 'boolean', example: true),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Delivery zone updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ZONES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/delivery-zones/{deliveryZone}',
    operationId: 'deleteDeliveryZone',
    tags: ['Delivery Zones'],
    summary: 'Delete a delivery zone (Admin)',
    description: 'Deletes a delivery zone and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryZone', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Delivery zone deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ZONES),
    ]
)]
#[OA\Get(
    path: '/api/delivery-zones/check-coverage',
    operationId: 'checkDeliveryCoverage',
    tags: ['Delivery Zones'],
    summary: 'Check if a zip code is covered for delivery',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'zip_code', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Coverage checked successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
class DeliveryZoneEndpoints {}
