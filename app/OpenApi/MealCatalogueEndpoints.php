<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Get(
    path: '/api/meals',
    operationId: 'listMeals',
    tags: ['Meals'],
    summary: 'List published meals for authenticated users',
    description: 'Returns only published meals. Recipe visibility is filtered by user tier: pro users can see pro-only recipes, signed users cannot.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'tag', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meals fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/meals/{meal}',
    operationId: 'showMeal',
    tags: ['Meals'],
    summary: 'Get one published meal for authenticated users',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meal fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Get(
    path: '/api/my-meals',
    operationId: 'listMyMeals',
    tags: ['Meals'],
    summary: 'List meals manageable by current user (requires manage meals or Super Admin/Admin/Partner)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Manageable meals fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
    ]
)]
#[OA\Post(
    path: '/api/my-meals',
    operationId: 'createMyMeal',
    tags: ['Meals'],
    summary: 'Create a meal (requires manage meals or Super Admin/Admin/Partner)',
    description: 'Creates a meal with optional nested nutrition, allergens, ingredients, recipes, steps, and tutorials.',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title', 'description', 'status'],
            properties: [
                new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'title', type: 'string', example: 'Grilled Chicken Bowl'),
                new OA\Property(property: 'excerpt', type: 'string', nullable: true, example: 'High-protein healthy bowl'),
                new OA\Property(property: 'description', type: 'string', example: 'Fresh grilled chicken with vegetables.'),
                new OA\Property(property: 'thumbnail_image', type: 'string', nullable: true, example: 'https://example.com/images/meal-thumb.jpg'),
                new OA\Property(property: 'images', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'cooking_time', type: 'integer', nullable: true, example: 35),
                new OA\Property(property: 'servings', type: 'integer', nullable: true, example: 2),
                new OA\Property(property: 'calories', type: 'integer', nullable: true, example: 540),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived'], example: 'draft'),
                new OA\Property(property: 'tags', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(
                    property: 'nutrition',
                    description: 'Optional per-serving macros; persisted to asl_meal_nutritions (one row per meal).',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'fats', type: 'number', format: 'float', nullable: true, example: 12.5),
                        new OA\Property(property: 'protein', type: 'number', format: 'float', nullable: true, example: 30),
                        new OA\Property(property: 'carbs', type: 'number', format: 'float', nullable: true, example: 45),
                        new OA\Property(property: 'metadata', description: 'Arbitrary JSON (optional)', type: 'object', nullable: true),
                    ],
                    type: 'object'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Meal created successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Get(
    path: '/api/my-meals/{meal}',
    operationId: 'showMyMeal',
    tags: ['Meals'],
    summary: 'Get one manageable meal by id',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meal fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}',
    operationId: 'updateMyMeal',
    tags: ['Meals'],
    summary: 'Update one manageable meal (PUT)',
    description: 'Partial update. Send `nutrition: null` to remove the nutrition row; omit `nutrition` to leave it unchanged. Nested arrays replace existing lists when provided.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'title', type: 'string', example: 'Updated Meal Title'),
                new OA\Property(property: 'excerpt', type: 'string', nullable: true),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived'], example: 'published'),
                new OA\Property(property: 'thumbnail_image', type: 'string', nullable: true, example: 'https://example.com/images/new-thumb.jpg'),
                new OA\Property(property: 'images', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'cooking_time', type: 'integer', nullable: true),
                new OA\Property(property: 'servings', type: 'integer', nullable: true),
                new OA\Property(property: 'calories', type: 'integer', nullable: true),
                new OA\Property(property: 'tags', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(
                    property: 'nutrition',
                    description: 'Omit to keep existing. Send null to delete. Send object to create/update asl_meal_nutritions (only provided keys are updated on an existing row).',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'fats', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'protein', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'carbs', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'metadata', type: 'object', nullable: true),
                    ],
                    type: 'object'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Patch(
    path: '/api/my-meals/{meal}',
    operationId: 'patchMyMeal',
    tags: ['Meals'],
    summary: 'Update one manageable meal (PATCH)',
    description: 'Same body semantics as PUT; use whichever HTTP method your client prefers.',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'category_id', type: 'string', format: 'uuid', nullable: true),
                new OA\Property(property: 'title', type: 'string'),
                new OA\Property(property: 'excerpt', type: 'string', nullable: true),
                new OA\Property(property: 'description', type: 'string'),
                new OA\Property(property: 'status', type: 'string', enum: ['draft', 'published', 'archived']),
                new OA\Property(property: 'thumbnail_image', type: 'string', nullable: true),
                new OA\Property(property: 'images', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'cooking_time', type: 'integer', nullable: true),
                new OA\Property(property: 'servings', type: 'integer', nullable: true),
                new OA\Property(property: 'calories', type: 'integer', nullable: true),
                new OA\Property(property: 'tags', type: 'array', nullable: true, items: new OA\Items(type: 'string')),
                new OA\Property(property: 'published_at', type: 'string', format: 'date-time', nullable: true),
                new OA\Property(
                    property: 'nutrition',
                    nullable: true,
                    properties: [
                        new OA\Property(property: 'fats', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'protein', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'carbs', type: 'number', format: 'float', nullable: true),
                        new OA\Property(property: 'metadata', type: 'object', nullable: true),
                    ],
                    type: 'object'
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/my-meals/{meal}',
    operationId: 'deleteMyMeal',
    tags: ['Meals'],
    summary: 'Delete one manageable meal',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meal deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}/nutrition',
    operationId: 'upsertMealNutrition',
    tags: ['Meals'],
    summary: 'Add or update meal nutrition',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'fats', type: 'number', format: 'float', nullable: true, example: 12.5),
                new OA\Property(property: 'protein', type: 'number', format: 'float', nullable: true, example: 30.0),
                new OA\Property(property: 'carbs', type: 'number', format: 'float', nullable: true, example: 45.0),
                new OA\Property(property: 'metadata', type: 'object', nullable: true),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal nutrition updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}/allergens',
    operationId: 'syncMealAllergens',
    tags: ['Meals'],
    summary: 'Replace meal allergens list',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['allergens'],
            properties: [
                new OA\Property(
                    property: 'allergens',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'title', type: 'string'),
                            new OA\Property(property: 'description', type: 'string', nullable: true),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal allergens updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}/ingredients',
    operationId: 'syncMealIngredients',
    tags: ['Meals'],
    summary: 'Replace meal ingredients list',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['ingredients'],
            properties: [
                new OA\Property(
                    property: 'ingredients',
                    type: 'array',
                    items: new OA\Items(
                        properties: [
                            new OA\Property(property: 'meal_type', type: 'string', nullable: true),
                            new OA\Property(property: 'metadata', type: 'array', items: new OA\Items(type: 'object')),
                        ],
                        type: 'object'
                    )
                ),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal ingredients updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}/recipes',
    operationId: 'syncMealRecipes',
    tags: ['Meals'],
    summary: 'Replace meal recipes list (with steps)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['recipes'],
            properties: [
                new OA\Property(property: 'recipes', type: 'array', items: new OA\Items(type: 'object')),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal recipes updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/my-meals/{meal}/tutorials',
    operationId: 'syncMealTutorials',
    tags: ['Meals'],
    summary: 'Replace meal tutorials list',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['tutorials'],
            properties: [
                new OA\Property(property: 'tutorials', type: 'array', items: new OA\Items(type: 'object')),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal tutorials updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEALS),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Post(
    path: '/api/admin/cache/redis/clear',
    operationId: 'clearRedisCache',
    tags: ['Meals'],
    summary: 'Clear all redis cache (Super Admin)',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Redis cache cleared successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_SUPER_ADMIN_ONLY),
    ]
)]
#[OA\Get(
    path: '/api/meal-categories',
    operationId: 'listMealCategories',
    tags: ['Meal Categories'],
    summary: 'List meal categories',
    security: [['sanctum' => []]],
    responses: [
        new OA\Response(response: 200, description: 'Meal categories fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
    ]
)]
#[OA\Get(
    path: '/api/meal-categories/{mealCategory}',
    operationId: 'showMealCategory',
    tags: ['Meal Categories'],
    summary: 'Get one meal category',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'mealCategory', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meal category fetched successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 404, description: 'Not found'),
    ]
)]
#[OA\Post(
    path: '/api/meal-categories',
    operationId: 'createMealCategory',
    tags: ['Meal Categories'],
    summary: 'Create meal category (requires manage meal categories)',
    security: [['sanctum' => []]],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            required: ['title'],
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Breakfast'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'image', type: 'string', nullable: true),
                new OA\Property(property: 'icon', type: 'string', nullable: true),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 201, description: 'Meal category created successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEAL_CATEGORIES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Put(
    path: '/api/meal-categories/{mealCategory}',
    operationId: 'updateMealCategory',
    tags: ['Meal Categories'],
    summary: 'Update meal category (requires manage meal categories)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'mealCategory', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    requestBody: new OA\RequestBody(
        required: true,
        content: new OA\JsonContent(
            properties: [
                new OA\Property(property: 'title', type: 'string', example: 'Lunch'),
                new OA\Property(property: 'description', type: 'string', nullable: true),
                new OA\Property(property: 'image', type: 'string', nullable: true),
                new OA\Property(property: 'icon', type: 'string', nullable: true),
            ],
            type: 'object'
        )
    ),
    responses: [
        new OA\Response(response: 200, description: 'Meal category updated successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEAL_CATEGORIES),
        new OA\Response(response: 422, description: 'Validation failed'),
    ]
)]
#[OA\Delete(
    path: '/api/meal-categories/{mealCategory}',
    operationId: 'deleteMealCategory',
    tags: ['Meal Categories'],
    summary: 'Delete meal category (requires manage meal categories)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'mealCategory', in: 'path', required: true, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'Meal category deleted successfully'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_MANAGE_MEAL_CATEGORIES),
    ]
)]
#[OA\Get(
    path: '/api/admin/meals',
    operationId: 'adminMealsDataTables',
    tags: ['Meals'],
    summary: 'All meals (Yajra DataTables; Super Admin or Admin only)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'status', in: 'query', required: false, schema: new OA\Schema(type: 'string', enum: ['draft', 'published', 'archived'])),
        new OA\Parameter(name: 'category_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_ADMIN_MEAL_INGREDIENTS),
    ]
)]
#[OA\Get(
    path: '/api/admin/meal-ingredients',
    operationId: 'adminMealIngredientsDataTables',
    tags: ['Meals'],
    summary: 'All meal ingredients (Yajra DataTables; Super Admin or Admin only)',
    security: [['sanctum' => []]],
    parameters: [
        new OA\Parameter(name: 'meal_id', in: 'query', required: false, schema: new OA\Schema(type: 'string', format: 'uuid')),
        new OA\Parameter(name: 'meal_type', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
    ],
    responses: [
        new OA\Response(response: 200, description: 'DataTables JSON'),
        new OA\Response(response: 401, description: 'Unauthenticated'),
        new OA\Response(response: 403, description: AuthorizationNotes::FORBIDDEN_ADMIN_MEAL_INGREDIENTS),
    ]
)]
class MealCatalogueEndpoints {}
