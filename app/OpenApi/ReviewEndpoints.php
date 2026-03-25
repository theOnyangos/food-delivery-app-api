<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/meals/reviews',
    operationId: 'listAllMealReviews',
    tags: ['Meal reviews'],
    summary: 'Latest approved meal reviews (aggregate)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'limit', in: 'query', required: false, schema: new OA\Schema(type: 'integer', maximum: 50)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Aggregate meal reviews'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/meals/{meal}/reviews',
    operationId: 'listMealReviews',
    tags: ['Meal reviews'],
    summary: 'Paginated approved reviews for a meal',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'per_page', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'topic_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'rating', in: 'query', required: false, schema: new OA\Schema(type: 'integer', minimum: 1, maximum: 5)),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated reviews'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Meal not published or not visible'),
    ]
)]
#[OA\Post(
    path: '/api/meals/{meal}/reviews',
    operationId: 'createMealReview',
    tags: ['Meal reviews'],
    summary: 'Submit a review for a published meal',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['rating', 'message'],
            properties: [
                new OA\Property(property: 'rating', type: 'integer', minimum: 1, maximum: 5),
                new OA\Property(property: 'message', type: 'string', maxLength: 2000),
                new OA\Property(property: 'category_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
                new OA\Property(property: 'topic_ids', type: 'array', items: new OA\Items(type: 'string', format: 'uuid')),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Review created'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 422, description: 'Validation failed or meal not published'),
    ]
)]
#[OA\Get(
    path: '/api/user/reviews',
    operationId: 'listUserMealReviews',
    tags: ['Meal reviews'],
    summary: 'List current user approved meal reviews',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Paginated user reviews'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Delete(
    path: '/api/user/reviews/{review}',
    operationId: 'deleteUserMealReview',
    tags: ['Meal reviews'],
    summary: 'Delete own meal review',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'review', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Deleted'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/my-meals/{meal}/reviews',
    operationId: 'listPartnerMealReviews',
    tags: ['Meal reviews'],
    summary: 'Partner: list reviews for own meal',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Paginated reviews'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
    ]
)]
#[OA\Get(
    path: '/api/admin/review-categories',
    operationId: 'adminReviewCategoriesDataTables',
    tags: ['Admin — meal reviews'],
    summary: 'Review categories (DataTables)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_REVIEW_CATEGORIES),
    ]
)]
#[OA\Get(
    path: '/api/admin/review-categories/list',
    operationId: 'adminReviewCategoriesList',
    tags: ['Admin — meal reviews'],
    summary: 'Ordered review categories for dropdowns',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Categories list'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_REVIEW_CATEGORIES),
    ]
)]
#[OA\Get(
    path: '/api/admin/reviews',
    operationId: 'adminReviewsDataTables',
    tags: ['Admin — meal reviews'],
    summary: 'All reviews (DataTables)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_REVIEWS),
    ]
)]
#[OA\Patch(
    path: '/api/admin/reviews/{review}/status',
    operationId: 'adminReviewUpdateStatus',
    tags: ['Admin — meal reviews'],
    summary: 'Update review moderation status',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'review', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'status', type: 'string', enum: ['approved', 'pending', 'rejected']),
            ]
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Updated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_REVIEWS),
        new OA\Response(response: 422, description: 'Invalid status'),
    ]
)]
final class ReviewEndpoints {}
