<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Classroom>
 */
class ClassroomFactory extends Factory
{
    protected $model = Classroom::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'grade_id' => Grade::factory(),
            'name' => fake()->bothify('?-##'),
        ];
    }
}
