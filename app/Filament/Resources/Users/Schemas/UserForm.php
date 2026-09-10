<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Role;

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
                    ->autocomplete('off')
                    ->required(),
                Toggle::make('email_verified')
                    ->label(__('panel.users.form.email_verified'))
                    ->helperText(__('panel.users.form.email_verified_help'))
                    ->formatStateUsing(fn (?User $record): bool => $record?->email_verified_at !== null),
                TextInput::make('password')
                    ->label(__('panel.users.form.password'))
                    ->password()
                    ->autocomplete('new-password')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state): bool => filled($state)),
                Select::make('roles')
                    ->label(__('panel.users.form.role'))
                    ->relationship(
                        name: 'roles',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query): Builder => Auth::user()?->hasRole(RoleEnum::SuperAdmin->value)
                            ? $query
                            : $query->where('name', '!=', RoleEnum::SuperAdmin->value),
                    )
                    ->multiple()
                    ->preload()
                    ->searchable()
                    ->getOptionLabelFromRecordUsing(fn (Role $record): string => RoleEnum::tryFrom($record->name)?->getLabel() ?? $record->name)
                    ->noOptionsMessage(__('panel.users.form.roles_no_options'))
                    ->rule(fn () => function (string $attribute, mixed $value, Closure $fail): void {
                        if (Auth::user()?->hasRole(RoleEnum::SuperAdmin->value)) {
                            return;
                        }

                        $isAssigningSuperAdmin = Role::query()
                            ->whereKey(Arr::wrap($value))
                            ->where('name', RoleEnum::SuperAdmin->value)
                            ->exists();

                        if ($isAssigningSuperAdmin) {
                            $fail(__('panel.users.form.roles_super_admin_forbidden'));
                        }
                    }),
            ]);
    }
}
