<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    protected $model = Assessment::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'semester_id' => Semester::factory(),
            'assessment_category_id' => AssessmentCategory::factory(),
            'title' => fake()->sentence(3),
            'max_score' => 100.00,
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'teacher_user_id' => User::factory(),
        ];
    }
}
