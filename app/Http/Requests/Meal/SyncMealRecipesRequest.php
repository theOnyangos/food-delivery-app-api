<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SyncMealRecipesRequest extends FormRequest
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
            'recipes' => ['required', 'array'],
            'recipes.*.description' => ['nullable', 'string'],
            'recipes.*.status' => ['nullable', 'string', Rule::in(['active', 'inactive'])],
            'recipes.*.is_pro_only' => ['nullable', 'boolean'],
            'recipes.*.steps' => ['nullable', 'array'],
            'recipes.*.steps.*.title' => ['required', 'string', 'max:255'],
            'recipes.*.steps.*.description' => ['nullable', 'string'],
            'recipes.*.steps.*.images' => ['nullable', 'array'],
            'recipes.*.steps.*.images.*' => ['string', 'max:2048'],
            'recipes.*.steps.*.position' => ['nullable', 'integer', 'min:1', 'max:999'],
        ];
    }
}
