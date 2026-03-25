<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meal\StoreMealRequest;
use App\Http\Requests\Meal\SyncMealAllergensRequest;
use App\Http\Requests\Meal\SyncMealIngredientsRequest;
use App\Http\Requests\Meal\SyncMealRecipesRequest;
use App\Http\Requests\Meal\SyncMealTutorialsRequest;
use App\Http\Requests\Meal\UpdateMealRequest;
use App\Http\Requests\Meal\UpsertMealNutritionRequest;
use App\Models\Meal;
use App\Services\MealCacheService;
use App\Services\MealService;
use App\Services\RedisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MealController extends Controller
{
    public function __construct(
        private readonly MealService $mealService,
        private readonly MealCacheService $mealCache,
        private readonly RedisService $redisService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only(['category_id', 'tag']);
        $meals = $this->mealCache->publishedList((string) $request->user()->id, $filters, function () use ($request, $filters) {
            return $this->mealService->listPublishedForUser($request->user(), $filters);
        });

        return $this->apiSuccess($meals, 'Meals fetched successfully.');
    }

    public function myMeals(Request $request): JsonResponse
    {
        $filters = $request->only(['status']);
        $meals = $this->mealCache->manageableList((string) $request->user()->id, $filters, function () use ($request, $filters) {
            return $this->mealService->listManageableForUser($request->user(), $filters);
        });

        return $this->apiSuccess($meals, 'Manageable meals fetched successfully.');
    }

    public function store(StoreMealRequest $request): JsonResponse
    {
        $meal = $this->mealService->createForOwner($request->user(), $request->validated());

        return $this->apiSuccess($meal, 'Meal created successfully.', 201);
    }

    public function show(Request $request, Meal $meal): JsonResponse
    {
        try {
            $result = $this->mealCache->publishedMeal(
                (string) $meal->id,
                (string) $request->user()->id,
                $request->user()->hasRole('Pro User'),
                function () use ($request, $meal) {
                    return $this->mealService->showPublishedForUser($request->user(), $meal);
                }
            );

            return $this->apiSuccess($result, 'Meal fetched successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 404);
        }
    }

    public function showMine(Request $request, Meal $meal): JsonResponse
    {
        try {
            $result = $this->mealCache->manageableMeal((string) $meal->id, (string) $request->user()->id, function () use ($request, $meal) {
                return $this->mealService->showForOwner($request->user(), $meal);
            });

            return $this->apiSuccess($result, 'Meal fetched successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function update(UpdateMealRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->updateForOwner($request->user(), $meal, $request->validated());

            return $this->apiSuccess($updated, 'Meal updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function destroy(Request $request, Meal $meal): JsonResponse
    {
        try {
            $this->mealService->deleteForOwner($request->user(), $meal);

            return $this->apiSuccess(null, 'Meal deleted successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function upsertNutrition(UpsertMealNutritionRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->upsertNutritionForOwner($request->user(), $meal, $request->validated());

            return $this->apiSuccess($updated, 'Meal nutrition updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function syncAllergens(SyncMealAllergensRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->syncAllergensForOwner($request->user(), $meal, $request->validated('allergens'));

            return $this->apiSuccess($updated, 'Meal allergens updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function syncIngredients(SyncMealIngredientsRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->syncIngredientsForOwner($request->user(), $meal, $request->validated('ingredients'));

            return $this->apiSuccess($updated, 'Meal ingredients updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function syncRecipes(SyncMealRecipesRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->syncRecipesForOwner($request->user(), $meal, $request->validated('recipes'));

            return $this->apiSuccess($updated, 'Meal recipes updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function syncTutorials(SyncMealTutorialsRequest $request, Meal $meal): JsonResponse
    {
        try {
            $updated = $this->mealService->syncTutorialsForOwner($request->user(), $meal, $request->validated('tutorials'));

            return $this->apiSuccess($updated, 'Meal tutorials updated successfully.');
        } catch (\RuntimeException $exception) {
            return $this->apiError($exception->getMessage(), 403);
        }
    }

    public function clearRedisCache(Request $request): JsonResponse
    {
        if (! $request->user()->hasRole('Super Admin')) {
            return $this->apiError('This action is unauthorized.', 403);
        }

        $this->redisService->flushAll();

        return $this->apiSuccess(null, 'Redis cache cleared successfully.');
    }
}
