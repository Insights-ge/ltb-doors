<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('panel.users.table.name'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('panel.users.table.email'))
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label(__('panel.users.table.email_verified_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('panel.users.table.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('panel.users.table.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            // Nice To have stuff
            ->striped()
            ->emptyStateHeading(__('panel.users.table.empty_heading'))
            ->emptyStateDescription(__('panel.users.table.empty_description'));
    }
}
