<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum DoorColor: string implements HasColor, HasLabel
{
    case Black = 'black';
    case Inox = 'inox';
    case Graphite = 'graphite';
    case Silver = 'silver';

    public function getLabel(): string
    {
        return match ($this) {
            self::Black => __('catalog.colors.black'),
            self::Inox => __('catalog.colors.inox'),
            self::Graphite => __('catalog.colors.graphite'),
            self::Silver => __('catalog.colors.silver'),
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Black => 'gray',
            self::Inox => 'blue',
            self::Graphite => 'purple',
            self::Silver => 'yellow',
        };
    }
}
