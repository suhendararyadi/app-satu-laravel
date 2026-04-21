<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Subject;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subject>
 */
class SubjectFactory extends Factory
{
    protected $model = Subject::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->word(),
            'code' => fake()->optional()->lexify('???'),
        ];
    }
}
