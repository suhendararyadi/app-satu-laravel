<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');

        return [
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.student_user_id' => ['required', 'integer', 'exists:users,id'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:'.$assessment->max_score],
            'scores.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
