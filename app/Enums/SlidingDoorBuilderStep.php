<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum SlidingDoorBuilderStep: int implements HasLabel
{
    case Size = 1;
    case System = 2;
    case Infills = 3;
    case Review = 4;

    public function getLabel(): string
    {
        return __($this->translationKey() . '.label');
    }

    public function getDescription(): ?string
    {
        if ($this === self::Review) {
            return null;
        }

        $description = __($this->translationKey() . '.description');

        return is_string($description) ? $description : null;
    }

    private function translationKey(): string
    {
        return 'sliding_door_builder.steps.' . match ($this) {
            self::Size => 'opening',
            self::System => 'system',
            self::Infills => 'infills',
            self::Review => 'review',
        };
    }
}
