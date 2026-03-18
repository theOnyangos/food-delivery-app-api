<?php

namespace App\Http\Requests\DeliveryZone;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDeliveryZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'zip_code' => ['sometimes', 'string', 'max:20'],
            'delivery_fee' => ['sometimes', 'numeric', 'min:0'],
            'status' => ['sometimes', 'string', Rule::in(['active', 'inactive'])],
            'minimum_order_amount' => ['nullable', 'integer', 'min:0'],
            'estimated_delivery_minutes' => ['nullable', 'integer', 'min:1', 'max:1440'],
            'is_serviceable' => ['sometimes', 'boolean'],
        ];
    }
}
