<?php

namespace App\Filament\Resources\DoorModels\Schemas;

use App\Enums\DoorModelStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class DoorModelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('door_models.form.name'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('status')
                    ->label(__('door_models.form.status'))
                    ->options(DoorModelStatus::class)
                    ->default(DoorModelStatus::Active)
                    ->required(),
                TextInput::make('partition_count')
                    ->label(__('door_models.form.partition_count'))
                    ->integer()
                    ->minValue(1)
                    ->maxValue(4)
                    ->default(1)
                    ->required(),
                Section::make(__('door_models.form.sizing_section'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('min_height')
                                    ->label(__('door_models.form.min_height'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_height')
                                    ->label(__('door_models.form.max_height'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('min_width')
                                    ->label(__('door_models.form.min_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_width')
                                    ->label(__('door_models.form.max_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('not_recommended_height_min')
                                    ->label(__('door_models.form.not_recommended_height_min'))
                                    ->numeric(),
                                TextInput::make('not_recommended_height_max')
                                    ->label(__('door_models.form.not_recommended_height_max'))
                                    ->numeric(),
                            ]),
                    ])
                    ->disabledOn(Operation::Edit)
                    ->columnSpanFull(),
            ]);
    }
}
