<?php

namespace App\Data;

use App\Enums\InfillTexture;

readonly class BuilderVisualData
{
    public function __construct(
        public string $fill,
        public ?InfillTexture $texture = null,
    ) {}
}
