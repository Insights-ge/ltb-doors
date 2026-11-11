<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum InfillTexture: string implements HasLabel
{
    case Solid = 'solid';
    case WoodStraight = 'wood_straight';
    case WoodCathedral = 'wood_cathedral';
    case Glass = 'glass';
    case Mirror = 'mirror';

    public function getLabel(): string
    {
        return __('infills.textures.' . $this->value);
    }
}
