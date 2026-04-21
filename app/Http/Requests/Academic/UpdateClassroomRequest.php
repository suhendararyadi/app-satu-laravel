<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassroomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'academic_year_id' => ['sometimes', 'required', 'integer', 'exists:academic_years,id'],
            'grade_id' => ['sometimes', 'required', 'integer', 'exists:grades,id'],
        ];
    }
}
