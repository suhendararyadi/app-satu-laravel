<?php

namespace Database\Factories;

use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4, false);

        return [
            'team_id' => Team::factory(),
            'author_id' => User::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->optional()->paragraph(),
            'content' => fake()->paragraphs(3, true),
            'featured_image_path' => null,
            'is_published' => false,
            'published_at' => null,
            'meta_description' => fake()->optional()->sentence(10),
        ];
    }

    public function published(): static
    {
        return $this->state([
            'is_published' => true,
            'published_at' => now(),
        ]);
    }
}
