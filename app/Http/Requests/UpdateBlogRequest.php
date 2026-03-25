<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogRequest extends FormRequest
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
        $blog = $this->route('blog');

        return [
            'blog_category_id' => ['sometimes', 'uuid', 'exists:asl_blog_categories,id'],
            'title' => ['sometimes', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('asl_blogs', 'slug')->ignore($blog->id)],
            'excerpt' => ['nullable', 'string'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_meta_description' => ['nullable', 'string', 'max:500'],
            'seo_keywords' => ['nullable', 'array', 'max:50'],
            'seo_keywords.*' => ['string', 'max:100'],
            'body' => ['sometimes', 'string'],
            'image' => ['nullable', 'string', 'max:500'],
            'status' => ['nullable', 'string', Rule::in(['draft', 'published'])],
        ];
    }
}
