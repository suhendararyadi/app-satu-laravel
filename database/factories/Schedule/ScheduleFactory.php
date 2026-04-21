<?php

namespace Database\Factories\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $team = Team::factory()->create();

        return [
            'team_id' => $team->id,
            'semester_id' => Semester::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_user_id' => User::factory(),
            'day_of_week' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'time_slot_id' => TimeSlot::factory()->for($team),
            'room' => fake()->optional()->word(),
        ];
    }
}
