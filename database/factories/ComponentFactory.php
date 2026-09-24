<?php

namespace Database\Factories;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Component>
 */
class ComponentFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => fake()->randomElement(ComponentType::cases()),
            'code' => 'გალუ' . fake()->unique()->numberBetween(1000, 9999),
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(DoorColor::cases()),
            'unique_code' => fake()->unique()->numerify('000######'),
        ];
    }

    public function ofType(ComponentType $type): static
    {
        return $this->state(fn (array $attributes): array => ['type' => $type]);
    }
}
