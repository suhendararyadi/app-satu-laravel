<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeacherAssignment>
 */
class TeacherAssignmentFactory extends Factory
{
    protected $model = TeacherAssignment::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'academic_year_id' => AcademicYear::factory(),
            'subject_id' => Subject::factory(),
            'classroom_id' => Classroom::factory(),
            'user_id' => User::factory(),
        ];
    }
}
