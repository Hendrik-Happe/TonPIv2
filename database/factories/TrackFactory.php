<?php

namespace Database\Factories;

use App\Models\Track;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Track>
 */
class TrackFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->words(3, true),
            'file_path' => '/tmp/test_track_'.fake()->numberBetween(1, 1000).'.mp3',
            'duration' => fake()->numberBetween(120, 300),
            'track_number' => fake()->numberBetween(1, 20),
        ];
    }
}
