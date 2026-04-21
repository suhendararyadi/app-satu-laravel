<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        static $order = 1;

        return [
            'team_id' => Team::factory(),
            'name' => 'Jam '.$order++,
            'start_time' => fake()->time('H:i'),
            'end_time' => fake()->time('H:i'),
            'sort_order' => $order,
        ];
    }
}
