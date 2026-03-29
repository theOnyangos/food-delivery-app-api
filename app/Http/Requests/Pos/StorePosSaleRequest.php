<?php

declare(strict_types=1);

namespace App\Http\Requests\Pos;

use Illuminate\Foundation\Http\FormRequest;

class StorePosSaleRequest extends FormRequest
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
            'order_type' => ['required', 'string', 'max:64'],
            'daily_menu_id' => ['nullable', 'uuid', 'exists:asl_daily_menus,id'],
            'customer_email' => ['nullable', 'string', 'email', 'max:255'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.meal_id' => ['required', 'uuid'],
            'lines.*.quantity' => ['required', 'integer', 'min:1'],
        ];
    }
}
