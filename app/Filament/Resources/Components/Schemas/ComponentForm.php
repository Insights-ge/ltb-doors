<?php

namespace App\Filament\Resources\Components\Schemas;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class ComponentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('type')
                    ->label(__('components.form.type'))
                    ->options(ComponentType::class)
                    ->searchable()
                    ->disabledOn(Operation::Edit)
                    ->required(),
                TextInput::make('code')
                    ->label(__('components.form.code'))
                    ->maxLength(255)
                    ->required(),
                Textarea::make('description')
                    ->label(__('components.form.description'))
                    ->maxLength(255)
                    ->rows(2)
                    ->columnSpanFull()
                    ->required(),
                Select::make('color')
                    ->label(__('components.form.color'))
                    ->options(DoorColor::class)
                    ->searchable(),
                TextInput::make('unique_code')
                    ->label(__('components.form.unique_code'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
            ]);
    }
}
