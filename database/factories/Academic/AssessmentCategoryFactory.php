<?php

namespace Database\Factories\Academic;

use App\Models\Academic\AssessmentCategory;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssessmentCategory>
 */
class AssessmentCategoryFactory extends Factory
{
    protected $model = AssessmentCategory::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'name' => fake()->randomElement(['Tugas', 'Ulangan Harian', 'UTS', 'UAS']),
            'weight' => 25.00,
        ];
    }
}
