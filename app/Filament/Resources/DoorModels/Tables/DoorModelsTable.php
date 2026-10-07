<?php

namespace App\Filament\Resources\DoorModels\Tables;

use App\Enums\DoorModelStatus;
use App\Models\DoorModel;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DoorModelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('door_models.table.name'))
                    ->searchable(),
                TextColumn::make('status')
                    ->label(__('door_models.table.status'))
                    ->badge(),
                TextColumn::make('height_range')
                    ->label(__('door_models.table.height_range'))
                    ->state(fn (DoorModel $record): string => "{$record->min_height}–{$record->max_height}"),
                TextColumn::make('width_range')
                    ->label(__('door_models.table.width_range'))
                    ->state(fn (DoorModel $record): string => "{$record->min_width}–{$record->max_width}"),
                TextColumn::make('not_recommended_height_range')
                    ->label(__('door_models.table.not_recommended_height_range'))
                    ->state(fn (DoorModel $record): ?string => $record->not_recommended_height_min !== null
                        ? "{$record->not_recommended_height_min}–{$record->not_recommended_height_max}"
                        : null)
                    ->toggleable(),
                TextColumn::make('variants_count')
                    ->label(__('door_models.table.variants_count'))
                    ->numeric()
                    ->sortable()
                    ->badge(),
                TextColumn::make('created_at')
                    ->label(__('door_models.table.created_at'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('door_models.table.status'))
                    ->options(DoorModelStatus::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->striped()
            ->emptyStateHeading(__('door_models.table.empty_heading'))
            ->emptyStateDescription(__('door_models.table.empty_description'));
    }
}
