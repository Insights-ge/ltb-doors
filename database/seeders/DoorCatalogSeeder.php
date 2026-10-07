<?php

namespace Database\Seeders;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Enums\DoorModelStatus;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use Illuminate\Database\Seeder;
use RuntimeException;

class DoorCatalogSeeder extends Seeder
{
    /**
     * @var array<string, DoorColor>
     */
    private const COLOR_MAP = [
        'შავი' => DoorColor::Black,
        'ინოქსი' => DoorColor::Inox,
        'გრაფიტი' => DoorColor::Graphite,
        'სილვერი' => DoorColor::Silver,
    ];

    public function run(): void
    {
        $handle = fopen(database_path('seeders/data/sliding-doors-catalog.csv'), 'r');

        if ($handle === false) {
            throw new RuntimeException('Unable to open sliding doors catalog CSV.');
        }

        $header = fgetcsv($handle);

        if ($header === false) {
            throw new RuntimeException('Sliding doors catalog CSV has no header row.');
        }

        $header = array_map(static fn (?string $value): string => $value ?? '', $header);

        while (($row = fgetcsv($handle)) !== false) {
            /** @var array<string, string> $data */
            $data = array_combine($header, array_map(static fn (?string $value): string => trim($value ?? ''), $row));

            $this->seedRow($data);
        }

        fclose($handle);
    }

    /**
     * @param  array<string, string>  $data
     */
    private function seedRow(array $data): void
    {
        $doorModel = $this->firstOrCreateDoorModel($data);

        $sideProfile = $this->firstOrCreateComponent(ComponentType::SideProfile, $data, 'side_profile');
        $topRail = $this->firstOrCreateComponent(ComponentType::TopRail, $data, 'top_rail');
        $bottomRail = $this->firstOrCreateComponent(ComponentType::BottomRail, $data, 'bottom_rail');
        $partition = $data['partition_unique_code'] !== ''
            ? $this->firstOrCreateComponent(ComponentType::Partition, $data, 'partition')
            : null;
        $softCloseMechanism = $this->firstOrCreateComponent(ComponentType::SoftCloseMechanism, $data, 'soft_close_mechanism');

        DoorVariant::query()->firstOrCreate(
            ['unique_code' => $data['unique_code']],
            [
                'door_model_id' => $doorModel->id,
                'product_code' => $data['product_code'],
                'product_full_name' => $data['product_full_name'],
                'color' => $this->mapColor($data['color']),
                'frame_design_code' => $data['frame_design_code'],
                'height_range_min' => (int) $data['height_range_min'],
                'height_range_max' => (int) $data['height_range_max'],
                'side_profile_component_id' => $sideProfile->id,
                'top_rail_component_id' => $topRail->id,
                'bottom_rail_component_id' => $bottomRail->id,
                'partition_component_id' => $partition?->id,
                'soft_close_mechanism_component_id' => $softCloseMechanism->id,
            ]
        );
    }

    /**
     * @param  array<string, string>  $data
     */
    private function firstOrCreateDoorModel(array $data): DoorModel
    {
        return DoorModel::query()->firstOrCreate(
            ['name' => $data['model_name']],
            [
                'status' => DoorModelStatus::Active,
                'min_height' => (int) $data['min_height'],
                'max_height' => (int) $data['max_height'],
                'min_width' => (int) $data['min_width'],
                'max_width' => (int) $data['max_width'],
                'not_recommended_height_min' => (int) $data['not_recommended_height_min'],
                'not_recommended_height_max' => (int) $data['not_recommended_height_max'],
            ]
        );
    }

    /**
     * @param  array<string, string>  $data
     */
    private function firstOrCreateComponent(ComponentType $type, array $data, string $prefix): Component
    {
        $color = $data["{$prefix}_color"] ?? '';

        return Component::query()->firstOrCreate(
            ['unique_code' => $data["{$prefix}_unique_code"]],
            [
                'type' => $type,
                'code' => $data["{$prefix}_code"],
                'description' => $data["{$prefix}_description"],
                'color' => $color !== '' ? $this->mapColor($color) : null,
            ]
        );
    }

    private function mapColor(string $value): DoorColor
    {
        return self::COLOR_MAP[$value] ?? throw new RuntimeException("Unmapped door color: {$value}");
    }
}
