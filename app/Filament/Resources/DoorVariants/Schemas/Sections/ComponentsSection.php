<?php

namespace App\Filament\Resources\DoorVariants\Schemas\Sections;

use App\Enums\ComponentType;
use App\Models\Component;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Collection;

class ComponentsSection
{
    public static function schema(): Section
    {
        $components = Component::query()->get();

        return Section::make(__('panel.catalog.door_variants.form.components_section'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        self::componentSelect('side_profile_component_id', 'side_profile', $components, ComponentType::SideProfile, required: true),
                        self::componentSelect('top_rail_component_id', 'top_rail', $components, ComponentType::TopRail, required: true),
                        self::componentSelect('bottom_rail_component_id', 'bottom_rail', $components, ComponentType::BottomRail, required: true),
                        self::componentSelect('partition_component_id', 'partition', $components, ComponentType::Partition, required: false),
                        self::componentSelect('soft_close_mechanism_component_id', 'soft_close_mechanism', $components, ComponentType::SoftCloseMechanism, required: true),
                    ]),
            ])
            ->columnSpanFull();
    }

    /**
     * @param  Collection<int, Component>  $components
     */
    private static function componentSelect(string $foreignKey, string $labelKey, Collection $components, ComponentType $type, bool $required): Select
    {
        $options = $components->where('type', $type)
            ->mapWithKeys(fn (Component $component): array => [$component->id => "{$component->code} — {$component->description}"])
            ->all();

        return Select::make($foreignKey)
            ->label(__("panel.catalog.door_variants.form.{$labelKey}"))
            ->options($options)
            ->searchable()
            ->required($required);
    }
}
