<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('panel.users.form.name'))
                    ->maxLength(255)
                    ->required(),
                TextInput::make('email')
                    ->label(__('panel.users.form.email'))
                    ->email()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn (Set $set) => $set('email_verified', false))
                    ->required(),
                Toggle::make('email_verified')
                    ->label(__('panel.users.form.email_verified'))
                    ->helperText(__('panel.users.form.email_verified_help'))
                    ->formatStateUsing(fn (?User $record): bool => $record?->email_verified_at !== null),
                TextInput::make('password')
                    ->label(__('panel.users.form.password'))
                    ->password()
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('roles')
                    ->label(__('panel.users.form.role'))
                    ->relationship('roles', 'name')
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->noOptionsMessage(__('panel.users.form.roles_no_options'))
                    ->hidden(fn (string $operation): bool => $operation === 'edit'),
            ]);
    }
}
