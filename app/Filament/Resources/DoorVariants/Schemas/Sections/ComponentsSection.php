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

        return Section::make(__('door_variants.form.components_section'))
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
        return Select::make($foreignKey)
            ->label(__("door_variants.form.{$labelKey}"))
            ->options(fn (int|string|null $state): array => self::componentOptions($components, $type, filled($state) ? (int) $state : null))
            ->searchable()
            ->required($required);
    }

    /**
     * Components of the given type, plus the currently selected component even when its type differs
     * (e.g. a top rail profile that is also used as a partition).
     *
     * @param  Collection<int, Component>  $components
     * @return array<int, string>
     */
    private static function componentOptions(Collection $components, ComponentType $type, ?int $selectedId): array
    {
        return $components
            ->filter(fn (Component $component): bool => $component->type === $type || $component->id === $selectedId)
            ->mapWithKeys(fn (Component $component): array => [$component->id => "{$component->code} — {$component->description}"])
            ->all();
    }
}
