<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasColor, HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case User = 'user';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => __('panel.roles.super_admin'),
            self::Admin => __('panel.roles.admin'),
            self::User => __('panel.roles.user'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::SuperAdmin => 'danger',
            self::Admin => 'info',
            self::User => 'gray',
        };
    }
}
