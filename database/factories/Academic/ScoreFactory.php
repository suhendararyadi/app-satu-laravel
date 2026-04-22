<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Assessment;
use App\Models\Academic\Score;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Score>
 */
class ScoreFactory extends Factory
{
    protected $model = Score::class;

    public function definition(): array
    {
        return [
            'assessment_id' => Assessment::factory(),
            'student_user_id' => User::factory(),
            'score' => fake()->randomFloat(2, 0, 100),
            'notes' => null,
        ];
    }
}
