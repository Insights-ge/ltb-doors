<?php

namespace App\Filament\Resources\DoorVariants\Schemas\Sections;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class SizingSection
{
    public static function schema(): Section
    {
        return Section::make(__('panel.catalog.door_variants.form.sizing_section'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        TextInput::make('height_range_min')
                            ->label(__('panel.catalog.door_variants.form.height_range_min'))
                            ->numeric()
                            ->required(),
                        TextInput::make('height_range_max')
                            ->label(__('panel.catalog.door_variants.form.height_range_max'))
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->columnSpanFull();
    }
}
