<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/admin/users/role-options',
    operationId: 'adminUserRoleOptions',
    tags: ['Admin Users'],
    summary: 'List roles assignable when inviting users',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Roles fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_USERS),
    ]
)]
#[OA\Get(
    path: '/api/admin/users',
    operationId: 'adminListUsers',
    tags: ['Admin Users'],
    summary: 'Paginated list of users (for admin)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer', example: 15)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Users fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_USERS),
    ]
)]
#[OA\Post(
    path: '/api/admin/users',
    operationId: 'adminInviteUser',
    tags: ['Admin Users'],
    summary: 'Create user and send password-setup email (invite)',
    description: 'User is created then a reset/invite email is queued. Link base URL uses CLIENT_URL. Provide at least one of role_names or role_ids.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['first_name', 'last_name', 'email'],
            properties: [
                new OA\Property(property: 'first_name', type: 'string', example: 'Jane'),
                new OA\Property(property: 'middle_name', type: 'string', nullable: true),
                new OA\Property(property: 'last_name', type: 'string', example: 'Doe'),
                new OA\Property(property: 'email', type: 'string', format: 'email', example: 'jane@example.com'),
                new OA\Property(
                    property: 'role_names',
                    type: 'array',
                    items: new OA\Items(type: 'string', example: 'Customer')
                ),
                new OA\Property(
                    property: 'role_ids',
                    type: 'array',
                    items: new OA\Items(type: 'string', format: 'uuid')
                ),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'User created; invite email queued'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_USERS),
        new OA\Response(response: 422, description: 'Validation error'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
#[OA\Post(
    path: '/api/admin/users/{user}/resend-invite',
    operationId: 'adminResendUserInvite',
    tags: ['Admin Users'],
    summary: 'Resend password-setup email for an unverified user',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'user', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Invite email queued'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_USERS),
        new OA\Response(response: 422, description: 'Account already verified'),
        new OA\Response(response: 429, description: 'Too many requests'),
    ]
)]
class AdminUserEndpoints {}
