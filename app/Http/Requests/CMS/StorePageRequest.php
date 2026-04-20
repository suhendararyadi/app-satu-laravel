<?php

namespace App\Http\Requests\CMS;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StorePageRequest extends FormRequest
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
            'content' => ['required', 'string'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
