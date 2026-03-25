<?php

namespace App\Http\Requests\Meal;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SyncMealTutorialsRequest extends FormRequest
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
            'tutorials' => ['required', 'array'],
            'tutorials.*.title' => ['required', 'string', 'max:255'],
            'tutorials.*.description' => ['nullable', 'string'],
            'tutorials.*.video_url' => ['nullable', 'url', 'max:2048'],
        ];
    }
}
