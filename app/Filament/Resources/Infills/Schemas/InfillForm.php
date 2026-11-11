<?php

namespace App\Filament\Resources\Infills\Schemas;

use App\Enums\InfillTexture;
use App\Models\Component;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class InfillForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('code')->label(__('infills.code'))->required()->maxLength(255),
            TextInput::make('unique_code')->label(__('infills.unique_code'))->required()->maxLength(255)->unique(ignoreRecord: true),
            Textarea::make('description')->label(__('infills.description'))->required()->columnSpanFull(),
            ColorPicker::make('visual_color')->label(__('infills.visual_color'))->required()->hex()->regex('/\A#[0-9a-fA-F]{6}\z/')->default('#B8B8B8'),
            Select::make('visual_texture')->label(__('infills.visual_texture'))->options(InfillTexture::class)->required()->default(InfillTexture::Solid)->searchable(),
            Select::make('incompatibleComponents')
                ->label(__('infills.incompatible_components'))
                ->relationship('incompatibleComponents', 'description')
                ->getOptionLabelFromRecordUsing(fn (Component $record): string => implode(' | ', array_filter([
                    $record->type->getLabel(), $record->code, $record->description,
                    $record->color?->getLabel(), $record->unique_code,
                ])))
                ->multiple()->searchable(['code', 'description', 'unique_code'])->preload()
                ->helperText(__('infills.compatibility_help'))->columnSpanFull(),
        ]);
    }
}
