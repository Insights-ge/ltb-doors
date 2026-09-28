<?php

namespace App\Filament\Resources\DoorVariants\Schemas\Sections;

use App\Enums\DoorColor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;

class GeneralInformationSection
{
    public static function schema(): Section
    {
        return Section::make(__('panel.catalog.door_variants.form.general_section'))
            ->schema([
                Grid::make(2)
                    ->schema([
                        Select::make('door_model_id')
                            ->label(__('panel.catalog.door_variants.form.door_model'))
                            ->relationship('doorModel', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('color')
                            ->label(__('panel.catalog.door_variants.form.color'))
                            ->options(DoorColor::class)
                            ->searchable()
                            ->required(),
                        TextInput::make('product_code')
                            ->label(__('panel.catalog.door_variants.form.product_code'))
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('frame_design_code')
                            ->label(__('panel.catalog.door_variants.form.frame_design_code'))
                            ->maxLength(255)
                            ->required(),
                        TextInput::make('unique_code')
                            ->label(__('panel.catalog.door_variants.form.unique_code'))
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->required(),
                    ]),
                Textarea::make('product_full_name')
                    ->label(__('panel.catalog.door_variants.form.product_full_name'))
                    ->rows(2)
                    ->maxLength(500)
                    ->required(),
            ])
            ->columnSpanFull();
    }
}
