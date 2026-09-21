<?php

namespace App\Filament\Resources\Roles;

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Roles\Pages\CreateRole;
use App\Filament\Resources\Roles\Pages\EditRole;
use App\Filament\Resources\Roles\Pages\ListRoles;
use App\Filament\Resources\Roles\Pages\ViewRole;
use BezhanSalleh\FilamentShield\Resources\Roles\RoleResource as ShieldRoleResource;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Override;

class RoleResource extends ShieldRoleResource
{
    #[Override]
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (Auth::user()?->hasRole(RoleEnum::SuperAdmin->value)) {
            return $query;
        }

        return $query->where('name', '!=', RoleEnum::SuperAdmin->value);
    }

    #[Override]
    public static function getPages(): array
    {
        return [
            'index' => ListRoles::route('/'),
            'create' => CreateRole::route('/create'),
            'view' => ViewRole::route('/{record}'),
            'edit' => EditRole::route('/{record}/edit'),
        ];
    }

    #[Override]
    public static function table(Table $table): Table
    {
        return parent::table($table)->columns([
            TextColumn::make('name')
                ->weight(FontWeight::Medium)
                ->label(__('filament-shield::filament-shield.column.name'))
                ->formatStateUsing(fn (string $state): string => RoleEnum::tryFrom($state)?->getLabel() ?? str($state)->headline()->toString())
                ->searchable(),
            TextColumn::make('guard_name')
                ->badge()
                ->color('warning')
                ->label(__('filament-shield::filament-shield.column.guard_name')),
            TextColumn::make('team.name')
                ->default('Global')
                ->badge()
                ->color(fn (mixed $state): string => str($state)->contains('Global') ? 'gray' : 'primary')
                ->label(__('filament-shield::filament-shield.column.team'))
                ->searchable()
                ->visible(fn (): bool => static::shield()->isCentralApp() && Utils::isTenancyEnabled()),
            TextColumn::make('permissions_count')
                ->badge()
                ->label(__('filament-shield::filament-shield.column.permissions'))
                ->counts('permissions')
                ->color('primary'),
            TextColumn::make('updated_at')
                ->label(__('filament-shield::filament-shield.column.updated_at'))
                ->dateTime(),
        ]);
    }
}
