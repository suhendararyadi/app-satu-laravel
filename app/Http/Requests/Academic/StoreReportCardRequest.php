<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'student_user_id' => ['required', 'integer', 'exists:users,id'],
            'homeroom_notes' => ['nullable', 'string'],
        ];
    }
}
