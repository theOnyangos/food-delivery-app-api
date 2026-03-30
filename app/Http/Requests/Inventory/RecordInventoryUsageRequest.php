<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RecordInventoryUsageRequest extends FormRequest
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
            'occurred_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.inventory_item_id' => ['nullable', 'uuid'],
            'lines.*.sku' => ['nullable', 'string', 'max:64'],
            'lines.*.quantity_used' => ['required', 'numeric', 'min:0.0001'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $lines = $this->input('lines', []);
            if (! is_array($lines)) {
                return;
            }
            foreach ($lines as $index => $line) {
                if (! is_array($line)) {
                    continue;
                }
                $hasId = ! empty($line['inventory_item_id']);
                $hasSku = isset($line['sku']) && trim((string) $line['sku']) !== '';
                if (! $hasId && ! $hasSku) {
                    $validator->errors()->add(
                        "lines.$index",
                        'Each line must include inventory_item_id or sku.'
                    );
                }
            }
        });
    }
}
