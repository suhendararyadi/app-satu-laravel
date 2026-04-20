<?php

namespace Database\Factories;

use App\Enums\SchoolType;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'is_personal' => false,
        ];
    }

    /**
     * Indicate that the team is a personal team.
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_personal' => true,
        ]);
    }

    /**
     * Indicate that the team has been deleted.
     */
    public function trashed(): static
    {
        return $this->state(fn (array $attributes) => [
            'deleted_at' => now(),
        ]);
    }

    /**
     * Indicate that the team has school profile fields populated.
     */
    public function school(): static
    {
        return $this->state(fn (array $attributes) => [
            'npsn' => fake()->numerify('########'),
            'school_type' => fake()->randomElement(SchoolType::cases())->value,
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'province' => fake()->state(),
            'postal_code' => fake()->postcode(),
            'phone' => fake()->numerify('0##-########'),
            'email' => fake()->companyEmail(),
            'logo_path' => 'logos/'.fake()->uuid().'.png',
            'accreditation' => fake()->randomElement(['A', 'B', 'C']),
            'principal_name' => fake()->name(),
            'founded_year' => fake()->numberBetween(1945, 2020),
            'vision' => fake()->sentence(),
            'mission' => fake()->paragraph(),
            'description' => fake()->paragraph(),
            'website_theme' => fake()->randomElement(['default', 'modern', 'classic']),
            'custom_domain' => null,
        ]);
    }
}
