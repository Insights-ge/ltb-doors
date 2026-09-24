<?php

namespace App\Filament\Resources\DoorVariants;

use App\Filament\Resources\DoorVariants\Pages\CreateDoorVariant;
use App\Filament\Resources\DoorVariants\Pages\EditDoorVariant;
use App\Filament\Resources\DoorVariants\Pages\ListDoorVariants;
use App\Filament\Resources\DoorVariants\Schemas\DoorVariantForm;
use App\Filament\Resources\DoorVariants\Tables\DoorVariantsTable;
use App\Models\DoorVariant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DoorVariantResource extends Resource
{
    protected static ?string $model = DoorVariant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedSquares2x2;

    protected static ?int $navigationSort = 30;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'doorModel',
            'sideProfile',
            'topRail',
            'bottomRail',
            'partition',
            'softCloseMechanism',
        ]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('doorModel');
    }

    public static function form(Schema $schema): Schema
    {
        return DoorVariantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DoorVariantsTable::configure($table);
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
            'index' => ListDoorVariants::route('/'),
            'create' => CreateDoorVariant::route('/create'),
            'edit' => EditDoorVariant::route('/{record}/edit'),
        ];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel.catalog.navigation_group');
    }

    public static function getNavigationLabel(): string
    {
        return __('panel.catalog.door_variants.layout.door_variants');
    }

    public static function getLabel(): ?string
    {
        return __('panel.catalog.door_variants.layout.door_variants');
    }

    public static function hasTitleCaseModelLabel(): bool
    {
        return false;
    }
}
