<?php

namespace App\Filament\Resources\DoorModels\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DoorModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel.catalog.door_models.form.name'))
                    ->maxLength(255)
                    ->required(),
                Section::make(__('panel.catalog.door_models.form.sizing_section'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('min_height')
                                    ->label(__('panel.catalog.door_models.form.min_height'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_height')
                                    ->label(__('panel.catalog.door_models.form.max_height'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('min_width')
                                    ->label(__('panel.catalog.door_models.form.min_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_width')
                                    ->label(__('panel.catalog.door_models.form.max_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('not_recommended_height_min')
                                    ->label(__('panel.catalog.door_models.form.not_recommended_height_min'))
                                    ->numeric(),
                                TextInput::make('not_recommended_height_max')
                                    ->label(__('panel.catalog.door_models.form.not_recommended_height_max'))
                                    ->numeric(),
                            ]),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
