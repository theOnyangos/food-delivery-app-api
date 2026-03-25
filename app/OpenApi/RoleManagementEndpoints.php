<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/roles/datatables',
    operationId: 'listRolesDataTables',
    tags: ['Role Management'],
    summary: 'List roles in DataTables format',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Roles fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
    ]
)]
#[OA\Get(
    path: '/api/admin/roles',
    operationId: 'listRoles',
    tags: ['Role Management'],
    summary: 'List available roles',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Roles fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
    ]
)]
#[OA\Post(
    path: '/api/admin/roles',
    operationId: 'createRole',
    tags: ['Role Management'],
    summary: 'Create a role',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Auditor'),
                new OA\Property(property: 'guard_name', type: 'string', example: 'web'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Role created successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/roles/{role}',
    operationId: 'showRole',
    tags: ['Role Management'],
    summary: 'Show a role',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Role fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
        new OA\Response(response: 404, description: 'Role not found'),
    ]
)]
#[OA\Put(
    path: '/api/admin/roles/{role}',
    operationId: 'updateRole',
    tags: ['Role Management'],
    summary: 'Update a role',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['name'],
            properties: [
                new OA\Property(property: 'name', type: 'string', example: 'Admin'),
                new OA\Property(property: 'guard_name', type: 'string', example: 'web'),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Role updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/admin/roles/{role}',
    operationId: 'deleteRole',
    tags: ['Role Management'],
    summary: 'Delete a role',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Role deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
    ]
)]
#[OA\Put(
    path: '/api/admin/roles/{role}/permissions',
    operationId: 'syncRolePermissions',
    tags: ['Role Management'],
    summary: 'Sync role permissions',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'role', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'permission_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                new OA\Property(property: 'permission_names', type: 'array', items: new OA\Items(type: 'string')),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Permissions synced successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/permissions',
    operationId: 'listPermissions',
    tags: ['Role Management'],
    summary: 'List permissions',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Permissions fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
    ]
)]
#[OA\Put(
    path: '/api/admin/users/{user}/roles',
    operationId: 'updateUserRoles',
    tags: ['Role Management'],
    summary: 'Update a user roles',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'role_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                new OA\Property(property: 'role_names', type: 'array', items: new OA\Items(type: 'string')),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'User roles updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_ROLES),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
class RoleManagementEndpoints {}
