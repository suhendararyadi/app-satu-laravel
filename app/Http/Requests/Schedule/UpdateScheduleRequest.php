<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
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
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'room' => ['nullable', 'string', 'max:100'],
        ];
    }
}
