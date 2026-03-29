<?php

declare(strict_types=1);

namespace App\Http\Requests\DailyMenu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDailyMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'menu_date' => ['required', 'date', Rule::unique('asl_daily_menus', 'menu_date')],
            'notes' => ['nullable', 'string', 'max:10000'],
            'items' => ['nullable', 'array'],
            'items.*.meal_id' => ['required_with:items', 'uuid', 'exists:asl_meals,id'],
            'items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'items.*.servings_available' => ['required_with:items.*.meal_id', 'integer', 'min:1'],
            'items.*.max_per_order' => ['nullable', 'integer', 'min:1'],
            'items.*.price' => ['nullable', 'numeric', 'min:0'],
            'items.*.discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
