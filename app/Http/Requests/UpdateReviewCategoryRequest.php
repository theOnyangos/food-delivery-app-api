<?php

namespace App\Http\Requests;

use App\Models\ReviewCategory;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateReviewCategoryRequest extends FormRequest
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
        /** @var ReviewCategory $category */
        $category = $this->route('review_category');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('asl_review_categories', 'slug')->ignore($category->id)],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}
