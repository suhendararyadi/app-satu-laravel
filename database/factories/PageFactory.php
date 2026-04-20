<?php

namespace Database\Factories;

use App\Models\Page;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(3, false);

        return [
            'team_id' => Team::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'content' => fake()->paragraphs(3, true),
            'is_published' => fake()->boolean(),
            'sort_order' => fake()->numberBetween(0, 100),
            'meta_description' => fake()->optional()->sentence(10),
        ];
    }
}
