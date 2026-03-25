<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncMealIngredientsRequest extends FormRequest
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
            'ingredients' => ['required', 'array'],
            'ingredients.*.meal_type' => ['nullable', 'string', 'max:100'],
            'ingredients.*.metadata' => ['required', 'array', 'min:1'],
            'ingredients.*.metadata.*.name' => ['required', 'string', 'max:255'],
            'ingredients.*.metadata.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
