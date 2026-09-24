<?php

namespace App\Filament\Resources\DoorVariants\Tables;

use App\Enums\DoorColor;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use Closure;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class DoorVariantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product_code')
                    ->label(__('panel.catalog.door_variants.table.product_code'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('doorModel.name')
                    ->label(__('panel.catalog.door_variants.table.door_model'))
                    ->sortable(),
                TextColumn::make('color')
                    ->label(__('panel.catalog.door_variants.table.color'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('height_range')
                    ->label(__('panel.catalog.door_variants.table.height_range'))
                    ->state(fn (DoorVariant $record): string => "{$record->height_range_min}–{$record->height_range_max}"),
                TextColumn::make('frame_design_code')
                    ->label(__('panel.catalog.door_variants.table.frame_design_code'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('unique_code')
                    ->label(__('panel.catalog.door_variants.table.unique_code'))
                    ->searchable()
                    ->copyable(),
                TextColumn::make('sideProfile.code')
                    ->label(__('panel.catalog.door_variants.table.side_profile'))
                    ->tooltip(self::componentDescriptionTooltip(fn (DoorVariant $record): ?Component => $record->sideProfile))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('topRail.code')
                    ->label(__('panel.catalog.door_variants.table.top_rail'))
                    ->tooltip(self::componentDescriptionTooltip(fn (DoorVariant $record): ?Component => $record->topRail))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('bottomRail.code')
                    ->label(__('panel.catalog.door_variants.table.bottom_rail'))
                    ->tooltip(self::componentDescriptionTooltip(fn (DoorVariant $record): ?Component => $record->bottomRail))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('partition.code')
                    ->label(__('panel.catalog.door_variants.table.partition'))
                    ->tooltip(self::componentDescriptionTooltip(fn (DoorVariant $record): ?Component => $record->partition))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->placeholder('—'),
                TextColumn::make('softCloseMechanism.code')
                    ->label(__('panel.catalog.door_variants.table.soft_close_mechanism'))
                    ->tooltip(self::componentDescriptionTooltip(fn (DoorVariant $record): ?Component => $record->softCloseMechanism))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('door_model_id')
                    ->label(__('panel.catalog.door_variants.table.door_model'))
                    ->options(fn (): array => DoorModel::query()->pluck('name', 'id')->all())
                    ->searchable(),
                SelectFilter::make('color')
                    ->label(__('panel.catalog.door_variants.table.color'))
                    ->options(DoorColor::class),
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
            ->emptyStateHeading(__('panel.catalog.door_variants.table.empty_heading'))
            ->emptyStateDescription(__('panel.catalog.door_variants.table.empty_description'));
    }

    /**
     * @param  Closure(DoorVariant): ?Component  $relation
     */
    private static function componentDescriptionTooltip(Closure $relation): Closure
    {
        return fn (DoorVariant $record): ?string => $relation($record)?->description;
    }
}
