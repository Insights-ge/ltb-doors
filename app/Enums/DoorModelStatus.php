<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DoorModelStatus: string implements HasColor, HasLabel
{
    case Active = 'active';
    case Passive = 'passive';

    public function getLabel(): string
    {
        return match ($this) {
            self::Active => __('catalog.door_model_statuses.active'),
            self::Passive => __('catalog.door_model_statuses.passive'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Passive => 'gray',
        };
    }
}
