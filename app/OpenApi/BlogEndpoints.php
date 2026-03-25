<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Tag(name: 'Blog', description: 'Public blog listing and Super Admin/Admin content management')]
#[OA\Get(
    path: '/api/blog/categories',
    operationId: 'publicBlogCategories',
    tags: ['Blog'],
    summary: 'List blog categories (public, cached)',
    responses: [
        new OA\Response(response: 200, description: 'Categories list'),
    ]
)]
#[OA\Get(
    path: '/api/blogs/recent',
    operationId: 'publicBlogsRecent',
    tags: ['Blog'],
    summary: 'Recent published posts (public, cached)',
    parameters: [
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 24)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Recent blogs'),
    ]
)]
#[OA\Get(
    path: '/api/blogs',
    operationId: 'publicBlogsIndex',
    tags: ['Blog'],
    summary: 'Paginated published blogs (public, cached)',
    parameters: [
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'search', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated blogs'),
    ]
)]
#[OA\Get(
    path: '/api/blogs/{slugOrId}',
    operationId: 'publicBlogShow',
    tags: ['Blog'],
    summary: 'Single published blog by slug or UUID (public, cached)',
    parameters: [
        new OA\Parameter(name: 'slugOrId', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Blog detail'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/admin/blog/categories',
    operationId: 'adminBlogCategoriesDataTables',
    tags: ['Blog'],
    summary: 'Blog categories (DataTables)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CONTENT),
    ]
)]
#[OA\Post(
    path: '/api/admin/blog/categories',
    operationId: 'adminBlogCategoryStore',
    tags: ['Blog'],
    summary: 'Create blog category',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CONTENT),
    ]
)]
#[OA\Get(
    path: '/api/admin/blogs',
    operationId: 'adminBlogsDataTables',
    tags: ['Blog'],
    summary: 'Blogs (DataTables)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CONTENT),
    ]
)]
#[OA\Post(
    path: '/api/admin/blogs',
    operationId: 'adminBlogStore',
    tags: ['Blog'],
    summary: 'Create blog post',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 201, description: 'Created'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_CONTENT),
    ]
)]
final class BlogEndpoints {}
