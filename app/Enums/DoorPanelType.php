<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum DoorPanelType: string implements HasLabel
{
    case Fixed = 'fixed';
    case Flexible = 'flexible';

    public function getLabel(): string
    {
        return match ($this) {
            self::Fixed => __('door_models.form.panel_fixed'),
            self::Flexible => __('door_models.form.panel_flexible'),
        };
    }
}
