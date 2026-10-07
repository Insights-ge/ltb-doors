<?php

namespace App\Filament\Resources\DoorModels;

use App\Filament\Resources\DoorModels\Pages\CreateDoorModel;
use App\Filament\Resources\DoorModels\Pages\EditDoorModel;
use App\Filament\Resources\DoorModels\Pages\ListDoorModels;
use App\Filament\Resources\DoorModels\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\DoorModels\Schemas\DoorModelForm;
use App\Filament\Resources\DoorModels\Tables\DoorModelsTable;
use App\Models\DoorModel;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoorModelResource extends Resource
{
    protected static ?string $model = DoorModel::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|BackedEnum|null $activeNavigationIcon = Heroicon::RectangleStack;

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount('variants');
    }

    public static function form(Schema $schema): Schema
    {
        return DoorModelForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoorModelsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDoorModels::route('/'),
            'create' => CreateDoorModel::route('/create'),
            'edit' => EditDoorModel::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('catalog.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('door_models.layout.door_models');
    }

    public static function getLabel(): ?string
    {
        return __('door_models.layout.door_models');
    }

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }
}
