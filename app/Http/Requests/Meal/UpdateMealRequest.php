<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMealRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'category_id' => ['sometimes', 'nullable', 'uuid', 'exists:asl_meal_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'excerpt' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'string'],
            'thumbnail_image' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'images' => ['sometimes', 'nullable', 'array'],
            'images.*' => ['string', 'max:2048'],
            'cooking_time' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1440'],
            'servings' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:1000'],
            'calories' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:20000'],
            'status' => ['sometimes', 'string', Rule::in(['draft', 'published', 'archived'])],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'published_at' => ['sometimes', 'nullable', 'date'],

            'nutrition' => ['sometimes', 'nullable', 'array'],
            'nutrition.fats' => ['nullable', 'numeric', 'min:0'],
            'nutrition.protein' => ['nullable', 'numeric', 'min:0'],
            'nutrition.carbs' => ['nullable', 'numeric', 'min:0'],
            'nutrition.metadata' => ['nullable', 'array'],

            'allergens' => ['sometimes', 'array'],
            'allergens.*.title' => ['required', 'string', 'max:255'],
            'allergens.*.description' => ['nullable', 'string'],

            'ingredients' => ['sometimes', 'array'],
            'ingredients.*.meal_type' => ['nullable', 'string', 'max:100'],
            'ingredients.*.metadata' => ['required', 'array', 'min:1'],
            'ingredients.*.metadata.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.metadata.*.value' => ['required', 'string', 'max:255'],

            'recipes' => ['sometimes', 'array'],
            'recipes.*.description' => ['nullable', 'string'],
            'recipes.*.status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'recipes.*.is_pro_only' => ['nullable', 'boolean'],
            'recipes.*.steps' => ['nullable', 'array'],
            'recipes.*.steps.*.title' => ['required', 'string', 'max:255'],
            'recipes.*.steps.*.description' => ['nullable', 'string'],
            'recipes.*.steps.*.images' => ['nullable', 'array'],
            'recipes.*.steps.*.images.*' => ['string', 'max:2048'],
            'recipes.*.steps.*.position' => ['nullable', 'integer', 'min:1', 'max:999'],

            'tutorials' => ['sometimes', 'array'],
            'tutorials.*.title' => ['required', 'string', 'max:255'],
            'tutorials.*.description' => ['nullable', 'string'],
            'tutorials.*.video_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
