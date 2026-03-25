<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'AI Agent', description: 'User AI chat (permission: use ai chat or staff/partner roles) and admin AI management (permission: manage ai agent)')]
#[OA\Post(
    path: '/api/ai/chat',
    operationId: 'aiChat',
    tags: ['AI Agent'],
    summary: 'Send a message to the AI assistant',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'conversation_id', type: 'string', format: 'uuid', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'Validation or AI error'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
        new OA\Response(response: 429, description: 'Daily limit or rate limit'),
    ]
)]
#[OA\Get(
    path: '/api/ai/conversations',
    operationId: 'aiConversationsList',
    tags: ['AI Agent'],
    summary: 'List conversations for the current user',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', default: 'active')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/ai/conversations/{id}',
    operationId: 'aiConversationShow',
    tags: ['AI Agent'],
    summary: 'Get one conversation with messages',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Delete(
    path: '/api/ai/conversations/{id}',
    operationId: 'aiConversationDelete',
    tags: ['AI Agent'],
    summary: 'Archive a conversation',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Archived'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Post(
    path: '/api/ai/conversations/{id}/regenerate',
    operationId: 'aiConversationRegenerate',
    tags: ['AI Agent'],
    summary: 'Regenerate the last assistant reply',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'Cannot regenerate'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Post(
    path: '/api/ai/assistant/chat',
    operationId: 'aiAssistantChat',
    tags: ['AI Agent'],
    summary: 'Assistant chat with KB + app context (daily limit applies)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['message'],
            properties: [
                new OA\Property(property: 'message', type: 'string'),
                new OA\Property(property: 'conversation_id', type: 'string', format: 'uuid', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'Validation or AI error'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
        new OA\Response(response: 429, description: 'Daily limit'),
    ]
)]
#[OA\Get(
    path: '/api/ai/assistant/conversations',
    operationId: 'aiAssistantConversationsList',
    tags: ['AI Agent'],
    summary: 'List conversations for assistant (customer-type) chat',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/ai/assistant/conversations/{id}',
    operationId: 'aiAssistantConversationShow',
    tags: ['AI Agent'],
    summary: 'Get one assistant conversation',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Delete(
    path: '/api/ai/assistant/conversations/{id}',
    operationId: 'aiAssistantConversationDelete',
    tags: ['AI Agent'],
    summary: 'Archive an assistant conversation',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Archived'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_USE_AI_CHAT),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/config',
    operationId: 'adminAiAgentConfigShow',
    tags: ['AI Agent'],
    summary: 'Get AI agent settings (API key masked)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Put(
    path: '/api/admin/ai-agent/config',
    operationId: 'adminAiAgentConfigUpdate',
    tags: ['AI Agent'],
    summary: 'Update AI agent settings',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'default_model', type: 'string', nullable: true),
                new OA\Property(property: 'enabled', type: 'boolean', nullable: true),
                new OA\Property(property: 'daily_limit_customer', type: 'integer', nullable: true),
                new OA\Property(property: 'daily_limit_admin', type: 'integer', nullable: true),
                new OA\Property(property: 'max_tokens', type: 'integer', nullable: true),
                new OA\Property(property: 'temperature', type: 'number', format: 'float', nullable: true),
                new OA\Property(property: 'system_prompts', type: 'object', nullable: true),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/openai/models',
    operationId: 'adminAiAgentOpenaiModels',
    tags: ['AI Agent'],
    summary: 'List OpenAI models',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'API key not configured'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
        new OA\Response(response: 500, description: 'OpenAI error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/openai/assistants',
    operationId: 'adminAiAgentOpenaiAssistants',
    tags: ['AI Agent'],
    summary: 'List OpenAI assistants',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'API key not configured'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
        new OA\Response(response: 500, description: 'OpenAI error'),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/conversations/stats',
    operationId: 'adminAiAgentConversationsStats',
    tags: ['AI Agent'],
    summary: 'AI conversation usage stats',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'days', in: 'query', required: false, schema: new OA\Schema(type: 'integer', default: 30)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/conversations',
    operationId: 'adminAiAgentConversationsIndex',
    tags: ['AI Agent'],
    summary: 'List all conversations (admin)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/conversations/{id}',
    operationId: 'adminAiAgentConversationsShow',
    tags: ['AI Agent'],
    summary: 'Get one conversation with messages (admin)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/kb/sources',
    operationId: 'adminAiAgentKbSourcesIndex',
    tags: ['AI Agent'],
    summary: 'List KB sources',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Post(
    path: '/api/admin/ai-agent/kb/sources',
    operationId: 'adminAiAgentKbSourcesStore',
    tags: ['AI Agent'],
    summary: 'Create KB source',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
        new OA\Response(response: 422, description: 'Validation error'),
    ]
)]
#[OA\Post(
    path: '/api/admin/ai-agent/kb/ingest-all',
    operationId: 'adminAiAgentKbIngestAll',
    tags: ['AI Agent'],
    summary: 'Ingest all KB sources',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Get(
    path: '/api/admin/ai-agent/kb/sources/{id}',
    operationId: 'adminAiAgentKbSourcesShow',
    tags: ['AI Agent'],
    summary: 'Get one KB source',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Put(
    path: '/api/admin/ai-agent/kb/sources/{id}',
    operationId: 'adminAiAgentKbSourcesUpdate',
    tags: ['AI Agent'],
    summary: 'Update KB source',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Delete(
    path: '/api/admin/ai-agent/kb/sources/{id}',
    operationId: 'adminAiAgentKbSourcesDestroy',
    tags: ['AI Agent'],
    summary: 'Delete KB source',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
#[OA\Post(
    path: '/api/admin/ai-agent/kb/sources/{id}/ingest',
    operationId: 'adminAiAgentKbSourcesIngest',
    tags: ['AI Agent'],
    summary: 'Ingest one KB source',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Success'),
        new OA\Response(response: 400, description: 'Ingest failed'),
        new OA\Response(response: 404, description: 'Not found'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_AI_AGENT),
    ]
)]
class AiAgentEndpoints {}
