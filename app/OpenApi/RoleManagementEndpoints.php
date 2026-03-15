<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/roles',
    operationId: 'listRoles',
    tags: ['Role Management'],
    summary: 'List available roles',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Roles fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
    ]
)]
#[OA\Patch(
    path: '/api/admin/users/{user}/role',
    operationId: 'assignUserRole',
    tags: ['Role Management'],
    summary: 'Assign a role to a user',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['role'],
            properties: [
                new OA\Property(property: 'role', type: 'string', example: 'Admin'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Role assigned successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: 'Forbidden'),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
class RoleManagementEndpoints
{
}
