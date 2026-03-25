<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Live chat', description: 'Support chat (Reverb private channel chat.conversation.{id})')]
#[OA\Get(
    path: '/api/chat/settings',
    operationId: 'chatSettingsGet',
    tags: ['Live chat'],
    summary: 'Get chat working-hours settings (manage chat)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
    ]
)]
#[OA\Put(
    path: '/api/chat/settings',
    operationId: 'chatSettingsPut',
    tags: ['Live chat'],
    summary: 'Update chat settings (manage chat)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'Validation'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/chat/support-allocations',
    operationId: 'chatSupportAllocationsIndex',
    tags: ['Live chat'],
    summary: 'List support allocations (manage chat)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
    ]
)]
#[OA\Post(
    path: '/api/chat/support-allocations',
    operationId: 'chatSupportAllocationsStore',
    tags: ['Live chat'],
    summary: 'Create support allocation (manage chat)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Delete(
    path: '/api/chat/support-allocations/{id}',
    operationId: 'chatSupportAllocationsDestroy',
    tags: ['Live chat'],
    summary: 'Remove support allocation (manage chat)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/chat/vendor-users',
    operationId: 'chatVendorUsersIndex',
    tags: ['Live chat'],
    summary: 'List partner users eligible for chat (staff, manage chat)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/chat/conversations',
    operationId: 'chatConversationsIndex',
    tags: ['Live chat'],
    summary: 'List conversations for current user',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
    ]
)]
#[OA\Post(
    path: '/api/chat/conversations',
    operationId: 'chatConversationsStore',
    tags: ['Live chat'],
    summary: 'Create or get conversation (partner or staff with vendor_user_id)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 400, description: 'Business rule'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
        new OA\Response(response: 422, description: 'Validation'),
    ]
)]
#[OA\Get(
    path: '/api/chat/conversations/{id}',
    operationId: 'chatConversationsShow',
    tags: ['Live chat'],
    summary: 'Get conversation with first page of messages',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
    ]
)]
#[OA\Delete(
    path: '/api/chat/conversations/{id}',
    operationId: 'chatConversationsDestroy',
    tags: ['Live chat'],
    summary: 'Delete conversation (manage chat)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CHAT),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/chat/conversations/{conversationId}/messages',
    operationId: 'chatMessagesIndex',
    tags: ['Live chat'],
    summary: 'Paginate messages',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
    ]
)]
#[OA\Post(
    path: '/api/chat/conversations/{conversationId}/messages',
    operationId: 'chatMessagesStore',
    tags: ['Live chat'],
    summary: 'Send a message (broadcasts message.sent on private channel)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 400, description: 'Outside hours or validation'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Patch(
    path: '/api/chat/conversations/{conversationId}/read',
    operationId: 'chatConversationsMarkRead',
    tags: ['Live chat'],
    summary: 'Mark conversation read',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/chat/conversations/{conversationId}/attachments/{mediaId}/serve-url',
    operationId: 'chatAttachmentServeUrl',
    tags: ['Live chat'],
    summary: 'Get temporary signed URL for an attachment',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'mediaId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/chat/conversations/{conversationId}/typing',
    operationId: 'chatTypingStore',
    tags: ['Live chat'],
    summary: 'Broadcast typing indicator (user.typing)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'conversationId', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_CHAT_CONVERSATIONS),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
class ChatEndpoints {}
