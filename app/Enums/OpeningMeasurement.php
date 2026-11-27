<?php

namespace App\Enums;

enum OpeningMeasurement: string
{
    case Width = 'openingWidth';
    case Height = 'openingHeight';

    public function labelKey(): string
    {
        return match ($this) {
            self::Width => 'width',
            self::Height => 'height',
        };
    }
}
