<?php

declare(strict_types=1);

namespace App\Http\Requests\DailyMenu;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DuplicateDailyMenuRequest extends FormRequest
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
        ];
    }
}
