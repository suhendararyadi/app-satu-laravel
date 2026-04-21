<?php

namespace Database\Factories\Academic;

use App\Models\Academic\Guardian;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Guardian>
 */
class GuardianFactory extends Factory
{
    protected $model = Guardian::class;

    public function definition(): array
    {
        return [
            'student_id' => User::factory(),
            'guardian_id' => User::factory(),
            'relationship' => fake()->randomElement(['ayah', 'ibu', 'wali']),
        ];
    }
}
