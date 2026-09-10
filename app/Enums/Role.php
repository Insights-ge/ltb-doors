<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Role: string implements HasLabel
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';

    public function getLabel(): string
    {
        return match ($this) {
            self::SuperAdmin => __('panel.roles.super_admin'),
            self::Admin => __('panel.roles.admin'),
        };
    }
}
