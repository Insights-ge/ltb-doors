<?php

namespace App\Filament\Resources\Components\Schemas;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use App\Models\Infill;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;
use Illuminate\Validation\Rules\Unique;

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
                    ->live()
                    ->disabledOn(Operation::Edit)
                    ->required(),
                TextInput::make('height_deduction')
                    ->label(__('components.form.height_deduction'))
                    ->integer()
                    ->minValue(0)
                    ->maxValue(65_535)
                    ->visible(fn (Get $get, ?Component $record): bool => ($record?->type ?? $get->enum('type', ComponentType::class)) === ComponentType::SideProfile)
                    ->disabledOn(Operation::Edit),
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
                Select::make('incompatibleInfills')
                    ->label(__('infills.incompatible_infills'))
                    ->relationship('incompatibleInfills', 'description')
                    ->getOptionLabelFromRecordUsing(fn (Infill $record): string => "{$record->code} | {$record->description} | {$record->unique_code}")
                    ->multiple()->searchable(['code', 'description', 'unique_code'])->preload()
                    ->columnSpanFull(),
                TextInput::make('unique_code')
                    ->label(__('components.form.unique_code'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true, modifyRuleUsing: fn (Unique $rule, Get $get): Unique => self::scopeToType($rule, $get->enum('type', ComponentType::class)))
                    ->required(),
            ]);
    }

    /**
     * The same unique code may exist once per component type (e.g. a profile used as top rail and as partition).
     */
    private static function scopeToType(Unique $rule, ?BackedEnum $type): Unique
    {
        return $rule->where('type', $type?->value);
    }
}
