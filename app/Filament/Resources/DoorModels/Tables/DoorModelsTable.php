<?php

namespace App\Filament\Resources\DoorModels\Tables;

use App\Models\DoorModel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DoorModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel.catalog.door_models.table.name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('height_range')
                    ->label(__('panel.catalog.door_models.table.height_range'))
                    ->state(fn (DoorModel $record): string => "{$record->min_height}–{$record->max_height}"),
                TextColumn::make('width_range')
                    ->label(__('panel.catalog.door_models.table.width_range'))
                    ->state(fn (DoorModel $record): string => "{$record->min_width}–{$record->max_width}"),
                TextColumn::make('not_recommended_height_range')
                    ->label(__('panel.catalog.door_models.table.not_recommended_height_range'))
                    ->state(fn (DoorModel $record): ?string => $record->not_recommended_height_min !== null
                        ? "{$record->not_recommended_height_min}–{$record->not_recommended_height_max}"
                        : null)
                    ->toggleable(),
                TextColumn::make('variants_count')
                    ->label(__('panel.catalog.door_models.table.variants_count'))
                    ->numeric()
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('panel.catalog.door_models.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->emptyStateHeading(__('panel.catalog.door_models.table.empty_heading'))
            ->emptyStateDescription(__('panel.catalog.door_models.table.empty_description'));
    }
}
