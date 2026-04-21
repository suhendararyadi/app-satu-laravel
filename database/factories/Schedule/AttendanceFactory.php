<?php

namespace Database\Factories\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Schedule\Attendance;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'classroom_id' => Classroom::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'subject_id' => null,
            'semester_id' => Semester::factory(),
            'recorded_by' => User::factory(),
        ];
    }
}
