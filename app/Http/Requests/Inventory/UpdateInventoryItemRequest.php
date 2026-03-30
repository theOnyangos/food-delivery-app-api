<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use App\Models\InventoryItem;
use App\Support\InventoryConstants;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryItemRequest extends FormRequest
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
        /** @var InventoryItem|null $item */
        $item = $this->route('inventoryItem');

        return [
            'sku' => [
                'sometimes',
                'nullable',
                'string',
                'max:64',
                Rule::unique('asl_inventory_items', 'sku')->ignore($item?->id),
            ],
            'name' => ['sometimes', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'quantity' => ['sometimes', 'numeric', 'min:0'],
            'unit' => ['sometimes', 'string', Rule::in(InventoryConstants::UNITS)],
            'storage_location' => ['nullable', 'string', 'max:255'],
            'storage_temperature_celsius' => ['nullable', 'numeric'],
            'expiration_date' => ['nullable', 'date'],
            'low_stock_threshold' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
