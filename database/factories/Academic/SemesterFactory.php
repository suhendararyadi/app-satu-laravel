<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Semester;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Semester>
 */
class SemesterFactory extends Factory
{
    protected $model = Semester::class;

    public function definition(): array
    {
        return [
            'academic_year_id' => AcademicYear::factory(),
            'name' => 'Semester '.fake()->numberBetween(1, 2),
            'order' => 1,
            'is_active' => false,
        ];
    }
}
