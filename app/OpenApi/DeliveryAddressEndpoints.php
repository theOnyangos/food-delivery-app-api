<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/delivery-addresses',
    operationId: 'listDeliveryAddresses',
    tags: ['Delivery Addresses'],
    summary: 'List authenticated user delivery addresses (requires manage delivery addresses or staff/customer roles)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Delivery addresses fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ADDRESSES),
    ]
)]
#[OA\Post(
    path: '/api/delivery-addresses',
    operationId: 'createDeliveryAddress',
    tags: ['Delivery Addresses'],
    summary: 'Create delivery address for authenticated user',
    description: 'Creates a delivery address and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['address_line', 'city', 'zip_code', 'longitude', 'latitude'],
            properties: [
                new OA\Property(property: 'label', type: 'string', nullable: true, example: 'Home'),
                new OA\Property(property: 'address_line', type: 'string', example: '123 Koinange Street'),
                new OA\Property(property: 'city', type: 'string', example: 'Nairobi'),
                new OA\Property(property: 'zip_code', type: 'string', example: '00100'),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 36.8219),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -1.2921),
                new OA\Property(property: 'delivery_notes', type: 'string', nullable: true, example: 'Call on arrival'),
                new OA\Property(property: 'is_default', type: 'boolean', nullable: true, example: true),
                new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['active', 'inactive'], example: 'active'),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Delivery address created successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ADDRESSES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Get(
    path: '/api/delivery-addresses/{deliveryAddress}',
    operationId: 'showDeliveryAddress',
    tags: ['Delivery Addresses'],
    summary: 'Get a specific delivery address for authenticated user',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryAddress', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Delivery address fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ADDRESSES),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Put(
    path: '/api/delivery-addresses/{deliveryAddress}',
    operationId: 'updateDeliveryAddress',
    tags: ['Delivery Addresses'],
    summary: 'Update delivery address for authenticated user',
    description: 'Updates a delivery address and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryAddress', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'label', type: 'string', nullable: true, example: 'Office'),
                new OA\Property(property: 'address_line', type: 'string', example: '456 Ngong Road'),
                new OA\Property(property: 'city', type: 'string', example: 'Nairobi'),
                new OA\Property(property: 'zip_code', type: 'string', example: '00100'),
                new OA\Property(property: 'longitude', type: 'number', format: 'float', example: 36.8065),
                new OA\Property(property: 'latitude', type: 'number', format: 'float', example: -1.3032),
                new OA\Property(property: 'delivery_notes', type: 'string', nullable: true, example: 'Leave at reception'),
                new OA\Property(property: 'is_default', type: 'boolean', nullable: true, example: false),
                new OA\Property(property: 'status', type: 'string', nullable: true, enum: ['active', 'inactive'], example: 'active'),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Delivery address updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ADDRESSES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/delivery-addresses/{deliveryAddress}',
    operationId: 'deleteDeliveryAddress',
    tags: ['Delivery Addresses'],
    summary: 'Delete delivery address for authenticated user',
    description: 'Deletes a delivery address and sends in-app notifications to users with Admin and Super Admin roles.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'deliveryAddress', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Delivery address deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_DELIVERY_ADDRESSES),
    ]
)]
class DeliveryAddressEndpoints {}
