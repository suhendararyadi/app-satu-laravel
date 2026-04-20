<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gallery>
 */
class GalleryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'title' => fake()->sentence(3, false),
            'description' => fake()->optional()->paragraph(),
            'is_published' => fake()->boolean(),
        ];
    }
}
