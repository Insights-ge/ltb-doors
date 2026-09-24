<?php

namespace Database\Factories;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DoorVariant>
 */
class DoorVariantFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $color = fake()->randomElement(DoorColor::cases());

        return [
            'door_model_id' => DoorModel::factory(),
            'product_code' => fake()->unique()->bothify('პნლა######'),
            'product_full_name' => fake()->sentence(),
            'color' => $color,
            'frame_design_code' => 'გალუ' . fake()->numberBetween(1000, 9999),
            'unique_code' => fake()->unique()->numerify('000######'),
            'height_range_min' => 1600,
            'height_range_max' => 2000,
            'side_profile_component_id' => Component::factory()->ofType(ComponentType::SideProfile),
            'top_rail_component_id' => Component::factory()->ofType(ComponentType::TopRail),
            'bottom_rail_component_id' => Component::factory()->ofType(ComponentType::BottomRail),
            'partition_component_id' => Component::factory()->ofType(ComponentType::Partition),
            'soft_close_mechanism_component_id' => Component::factory()->ofType(ComponentType::SoftCloseMechanism),
        ];
    }

    public function withoutPartition(): static
    {
        return $this->state(fn (array $attributes): array => ['partition_component_id' => null]);
    }
}
