<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'start_year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100'],
            'end_year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100', 'gt:start_year'],
        ];
    }
}
