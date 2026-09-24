<?php

namespace Database\Factories;

use App\Models\DoorModel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoorModel>
 */
class DoorModelFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->word(),
            'min_height' => 800,
            'max_height' => 2600,
            'min_width' => 500,
            'max_width' => 1100,
            'not_recommended_height_min' => 2601,
            'not_recommended_height_max' => 2700,
        ];
    }
}
