<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Classroom;
use App\Models\Academic\ReportCard;
use App\Models\Academic\Semester;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ReportCard>
 */
class ReportCardFactory extends Factory
{
    protected $model = ReportCard::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'semester_id' => Semester::factory(),
            'classroom_id' => Classroom::factory(),
            'student_user_id' => User::factory(),
            'generated_by' => User::factory(),
            'homeroom_notes' => null,
            'generated_at' => now(),
        ];
    }
}
