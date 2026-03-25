<?php

namespace App\Http\Requests;

use App\Models\ReviewTopic;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewTopicRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var ReviewTopic $topic */
        $topic = $this->route('review_topic');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('asl_review_topics', 'slug')->ignore($topic->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
