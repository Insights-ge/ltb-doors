<?php

namespace App\Filament\Resources\Components;

use App\Filament\Resources\Components\Pages\CreateComponent;
use App\Filament\Resources\Components\Pages\EditComponent;
use App\Filament\Resources\Components\Pages\ListComponents;
use App\Filament\Resources\Components\Schemas\ComponentForm;
use App\Filament\Resources\Components\Tables\ComponentsTable;
use App\Models\Component;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ComponentResource extends Resource
{
    protected static ?string $model = Component::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPuzzlePiece;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::PuzzlePiece;

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return ComponentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ComponentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListComponents::route('/'),
            'create' => CreateComponent::route('/create'),
            'edit' => EditComponent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.catalog.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel.catalog.components.layout.components');
    }

    public static function getLabel(): ?string
    {
        return __('panel.catalog.components.layout.components');
    }

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }

    /**
     * Cancels a delete action when the component is still referenced by a door variant.
     */
    public static function preventDeletingComponentInUse(Component $record, Action $action): void
    {
        if ($record->isInUse()) {
            self::notifyComponentInUse();
            $action->cancel();
        }
    }

    public static function notifyComponentInUse(): void
    {
        Notification::make()
            ->title(__('panel.catalog.components.table.delete_in_use_title'))
            ->body(__('panel.catalog.components.table.delete_in_use_body'))
            ->danger()
            ->send();
    }
}
