<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreAdminMealIngredientRequest extends FormRequest
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
            'meal_id' => ['required', 'uuid', 'exists:asl_meals,id'],
            'meal_type' => ['nullable', 'string', 'max:100'],
            'metadata' => ['required', 'array', 'min:1'],
            'metadata.*.name' => ['required', 'string', 'max:255'],
            'metadata.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
