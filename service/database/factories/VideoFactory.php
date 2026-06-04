<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Video>
 */
class VideoFactory extends Factory {
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array {
        return [
            'name' => $this->faker->sentence(3),
            'url' => 'videos/' . $this->faker->uuid() . '.mp4',
            'thumbnail' => 'images/' . $this->faker->uuid() . '.jpg',
            'tags' => 'acao, aventura',
            'isPrivate' => false,
            'isFavourite' => false,
        ];
    }
}
