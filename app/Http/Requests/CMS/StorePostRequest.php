<?php

namespace App\Http\Requests\CMS;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'content' => ['required', 'string'],
            'featured_image' => ['nullable', 'image', 'max:2048', 'mimes:jpg,jpeg,png,webp'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
