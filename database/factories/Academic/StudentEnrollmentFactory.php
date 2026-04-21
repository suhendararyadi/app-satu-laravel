<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Classroom;
use App\Models\Academic\StudentEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentEnrollment>
 */
class StudentEnrollmentFactory extends Factory
{
    protected $model = StudentEnrollment::class;

    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'user_id' => User::factory(),
            'student_number' => fake()->optional()->numerify('###########'),
        ];
    }
}
