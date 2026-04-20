<?php

namespace Database\Factories;

use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gallery_id' => Gallery::factory(),
            'image_path' => 'images/'.fake()->uuid().'.jpg',
            'caption' => fake()->optional()->sentence(5),
            'sort_order' => fake()->numberBetween(0, 100),
        ];
    }
}
