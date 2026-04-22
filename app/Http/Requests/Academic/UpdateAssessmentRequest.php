<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'assessment_category_id' => ['required', 'integer', 'exists:assessment_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ];
    }
}
