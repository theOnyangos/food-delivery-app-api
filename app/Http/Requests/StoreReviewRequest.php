<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'message' => ['required', 'string', 'max:2000'],
            'category_ids' => ['nullable', 'array'],
            'category_ids.*' => ['uuid', 'exists:asl_review_categories,id'],
            'topic_ids' => ['nullable', 'array'],
            'topic_ids.*' => ['uuid', 'exists:asl_review_topics,id'],
        ];
    }
}
