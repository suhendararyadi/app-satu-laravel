<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Grade;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grade>
 */
class GradeFactory extends Factory
{
    protected $model = Grade::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => 'Kelas '.fake()->randomElement(['X', 'XI', 'XII']),
            'order' => 1,
        ];
    }
}
