<?php

namespace App\Filament\Resources\Components\Tables;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class ComponentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('panel.catalog.components.table.type'))
                    ->badge(),
                TextColumn::make('code')
                    ->label(__('panel.catalog.components.table.code'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('panel.catalog.components.table.description'))
                    ->searchable()
                    ->limit(60)
                    ->tooltip(fn (?string $state): ?string => $state),
                TextColumn::make('color')
                    ->label(__('panel.catalog.components.table.color'))
                    ->badge(),
                TextColumn::make('unique_code')
                    ->label(__('panel.catalog.components.table.unique_code'))
                    ->searchable()
                    ->copyable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(__('panel.catalog.components.table.type'))
                    ->options(ComponentType::class)
                    ->searchable(),
                SelectFilter::make('color')
                    ->label(__('panel.catalog.components.table.color'))
                    ->options(DoorColor::class)
                    ->searchable(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Component $record, DeleteAction $action): void {
                        if ($record->isInUse()) {
                            self::notifyInUse();
                            $action->cancel();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->before(function (Collection $records, DeleteBulkAction $action): void {
                            $hasComponentInUse = $records->contains(
                                fn (Model $record): bool => $record instanceof Component && $record->isInUse()
                            );

                            if ($hasComponentInUse) {
                                self::notifyInUse();
                                $action->cancel();
                            }
                        }),
                ]),
            ])
            ->striped()
            ->emptyStateHeading(__('panel.catalog.components.table.empty_heading'))
            ->emptyStateDescription(__('panel.catalog.components.table.empty_description'));
    }

    private static function notifyInUse(): void
    {
        Notification::make()
            ->title(__('panel.catalog.components.table.delete_in_use_title'))
            ->body(__('panel.catalog.components.table.delete_in_use_body'))
            ->danger()
            ->send();
    }
}
