<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAdminMealIngredientRequest extends FormRequest
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
            'meal_id' => ['sometimes', 'required', 'uuid', 'exists:asl_meals,id'],
            'meal_type' => ['nullable', 'string', 'max:100'],
            'metadata' => ['sometimes', 'required', 'array', 'min:1'],
            'metadata.*.name' => ['required', 'string', 'max:255'],
            'metadata.*.value' => ['required', 'string', 'max:255'],
        ];
    }
}
