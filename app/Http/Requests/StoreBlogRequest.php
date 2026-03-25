<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBlogRequest extends FormRequest
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
        return [
            'blog_category_id' => ['required', 'uuid', 'exists:asl_blog_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:asl_blogs,slug'],
            'excerpt' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'array', 'max:50'],
            'seo_keywords.*' => ['string', 'max:100'],
            'body' => ['required', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
        ];
    }
}
