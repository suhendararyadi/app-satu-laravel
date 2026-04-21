<?php

namespace App\Http\Requests\Schedule;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
