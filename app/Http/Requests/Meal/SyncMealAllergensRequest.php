<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncMealAllergensRequest extends FormRequest
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
            'allergens' => ['required', 'array'],
            'allergens.*.title' => ['required', 'string', 'max:255'],
            'allergens.*.description' => ['nullable', 'string'],
        ];
    }
}
