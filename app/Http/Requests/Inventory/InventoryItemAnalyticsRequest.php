<?php

declare(strict_types=1);

namespace App\Http\Requests\Inventory;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class InventoryItemAnalyticsRequest extends FormRequest
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
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $v): void {
            $fromStr = $this->input('from');
            $toStr = $this->input('to');
            if (! is_string($fromStr) || ! is_string($toStr)) {
                return;
            }
            try {
                $from = Carbon::parse($fromStr)->startOfDay();
                $to = Carbon::parse($toStr)->startOfDay();
            } catch (\Throwable) {
                return;
            }
            $inclusiveDays = $from->diffInDays($to) + 1;
            if ($inclusiveDays > 366) {
                $v->errors()->add('to', 'Date range must not exceed 366 calendar days.');
            }
        });
    }
}
