<?php

namespace Database\Factories\Schedule;

use App\Enums\AttendanceStatus;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'student_user_id' => User::factory(),
            'status' => fake()->randomElement(AttendanceStatus::cases())->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
