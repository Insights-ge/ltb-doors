<?php

namespace App\Filament\Resources\DoorModels\RelationManagers;

use App\Filament\Resources\DoorVariants\DoorVariantResource;
use App\Models\DoorVariant;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel.catalog.door_models.relations.variants');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('product_code')
            ->columns([
                TextColumn::make('product_code')
                    ->label(__('panel.catalog.door_variants.table.product_code'))
                    ->searchable(),
                TextColumn::make('color')
                    ->label(__('panel.catalog.door_variants.table.color'))
                    ->badge(),
                TextColumn::make('height_range')
                    ->label(__('panel.catalog.door_variants.table.height_range'))
                    ->state(fn (DoorVariant $record): string => "{$record->height_range_min}–{$record->height_range_max}"),
                TextColumn::make('unique_code')
                    ->label(__('panel.catalog.door_variants.table.unique_code'))
                    ->copyable(),
            ])
            ->recordActions([
                Action::make('view')
                    ->label(__('panel.catalog.door_variants.layout.edit_door_variant'))
                    ->url(fn (DoorVariant $record): string => DoorVariantResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-o-pencil-square')
                    ->iconButton()
                    ->tooltip(__('panel.catalog.door_variants.layout.edit_door_variant')),
            ])
            ->headerActions([
                Action::make('create')
                    ->label(__('panel.catalog.door_variants.layout.create_door_variant'))
                    ->url(fn (): string => DoorVariantResource::getUrl('create'))
                    ->icon('heroicon-o-plus'),
            ]);
    }
}
