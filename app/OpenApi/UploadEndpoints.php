<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Post(
    path: '/api/uploads/image',
    operationId: 'uploadPrivateImage',
    tags: ['Uploads'],
    summary: 'Upload private image',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['image'],
                properties: [
                    new OA\Property(property: 'image', type: 'string', format: 'binary'),
                    new OA\Property(property: 'width', type: 'integer', nullable: true),
                    new OA\Property(property: 'height', type: 'integer', nullable: true),
                    new OA\Property(property: 'watermark', type: 'boolean', nullable: true),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Image uploaded successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_UPLOADS),
    ]
)]
#[OA\Post(
    path: '/api/uploads/public-asset',
    operationId: 'uploadPublicAsset',
    tags: ['Uploads'],
    summary: 'Upload public image asset',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'width', type: 'integer', nullable: true),
                    new OA\Property(property: 'height', type: 'integer', nullable: true),
                    new OA\Property(property: 'watermark', type: 'boolean', nullable: true),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Public asset uploaded successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_UPLOADS),
    ]
)]
#[OA\Post(
    path: '/api/uploads/private-asset',
    operationId: 'uploadPrivateAsset',
    tags: ['Uploads'],
    summary: 'Upload private file/image asset',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\MediaType(
            mediaType: 'multipart/form-data',
            schema: new OA\Schema(
                required: ['file'],
                properties: [
                    new OA\Property(property: 'file', type: 'string', format: 'binary'),
                    new OA\Property(property: 'category', type: 'string', example: 'general'),
                ]
            )
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'File uploaded successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_UPLOADS),
    ]
)]
#[OA\Get(
    path: '/api/uploads/{media}/url',
    operationId: 'getMediaSignedUrl',
    tags: ['Uploads'],
    summary: 'Get signed URL for a media item',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'media', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Signed URL generated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_UPLOADS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Delete(
    path: '/api/uploads/{media}',
    operationId: 'deleteMediaById',
    tags: ['Uploads'],
    summary: 'Delete media by id',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'media', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'File deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_UPLOADS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Delete(
    path: '/api/uploads',
    operationId: 'deleteMediaByPath',
    tags: ['Uploads'],
    summary: 'Delete media by path or URL',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['path'],
            properties: [
                new OA\Property(property: 'path', type: 'string', example: 'uploads/originals/2026/03/15/file.jpg'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'File deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/uploads/serve/{media}',
    operationId: 'serveMediaBySignedUrl',
    tags: ['Uploads'],
    summary: 'Serve media file using signed URL',
    parameters: [
        new OA\Parameter(name: 'media', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'signature', in: 'query', required: true, schema: new OA\Schema(type: 'string')),
        new OA\Parameter(name: 'expires', in: 'query', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Media stream response'),
        new OA\Response(response: 403, description: 'Invalid signature'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
class UploadEndpoints {}
