<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Meal\StoreMealCategoryRequest;
use App\Http\Requests\Meal\UpdateMealCategoryRequest;
use App\Models\MealCategory;
use Illuminate\Http\JsonResponse;

class MealCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $categories = MealCategory::query()->orderBy('title')->get();

        return $this->apiSuccess($categories, 'Meal categories fetched successfully.');
    }

    public function store(StoreMealCategoryRequest $request): JsonResponse
    {
        $category = MealCategory::query()->create($request->validated());

        return $this->apiSuccess($category, 'Meal category created successfully.', 201);
    }

    public function show(MealCategory $mealCategory): JsonResponse
    {
        return $this->apiSuccess($mealCategory, 'Meal category fetched successfully.');
    }

    public function update(UpdateMealCategoryRequest $request, MealCategory $mealCategory): JsonResponse
    {
        $mealCategory->fill($request->validated())->save();

        return $this->apiSuccess($mealCategory->fresh(), 'Meal category updated successfully.');
    }

    public function destroy(MealCategory $mealCategory): JsonResponse
    {
        $mealCategory->delete();

        return $this->apiSuccess(null, 'Meal category deleted successfully.');
    }
}
