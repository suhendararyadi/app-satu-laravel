<?php

namespace App\Http\Requests\School;

use App\Enums\SchoolType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolProfileRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $currentTeam = $this->user()->currentTeam;

        return [
            'npsn' => ['nullable', 'string', 'size:8', Rule::unique('teams', 'npsn')->ignore($currentTeam->id)],
            'school_type' => ['nullable', Rule::enum(SchoolType::class)],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:100'],
            'province' => ['nullable', 'string', 'max:100'],
            'postal_code' => ['nullable', 'string', 'max:10'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'accreditation' => ['nullable', 'string', 'max:5'],
            'principal_name' => ['nullable', 'string', 'max:100'],
            'founded_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
            'vision' => ['nullable', 'string'],
            'mission' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'website_theme' => ['nullable', 'string', 'in:default,modern,classic'],
            'custom_domain' => ['nullable', 'string', 'max:255', Rule::unique('teams', 'custom_domain')->ignore($currentTeam->id)],
        ];
    }
}
