<?php

namespace App\Filament\Resources\DoorVariants\Schemas\Sections;

use App\Enums\ComponentType;
use App\Models\Component;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;

class ComponentsSection
{
    public static function schema(): Section
    {
        return Section::make(__('panel.catalog.door_variants.form.components_section'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        self::componentSelect('side_profile_component_id', 'sideProfile', 'side_profile', ComponentType::SideProfile, required: true),
                        self::componentSelect('top_rail_component_id', 'topRail', 'top_rail', ComponentType::TopRail, required: true),
                        self::componentSelect('bottom_rail_component_id', 'bottomRail', 'bottom_rail', ComponentType::BottomRail, required: true),
                        self::componentSelect('partition_component_id', 'partition', 'partition', ComponentType::Partition, required: false),
                        self::componentSelect('soft_close_mechanism_component_id', 'softCloseMechanism', 'soft_close_mechanism', ComponentType::SoftCloseMechanism, required: true),
                    ]),
            ])
            ->columnSpanFull();
    }

    private static function componentSelect(string $foreignKey, string $relationship, string $labelKey, ComponentType $type, bool $required): Select
    {
        return Select::make($foreignKey)
            ->label(__("panel.catalog.door_variants.form.{$labelKey}"))
            ->relationship(
                name: $relationship,
                titleAttribute: 'code',
                modifyQueryUsing: fn (Builder $query): Builder => $query->where('type', $type),
            )
            ->getOptionLabelFromRecordUsing(fn (Component $record): string => "{$record->code} — {$record->description}")
            ->searchable(['code', 'description', 'unique_code'])
            ->preload()
            ->required($required);
    }
}
