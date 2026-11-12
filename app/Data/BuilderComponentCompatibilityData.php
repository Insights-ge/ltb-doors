<?php

namespace App\Data;

use App\Enums\ComponentType;
use App\Enums\DoorColor;

readonly class BuilderComponentCompatibilityData
{
    /**
     * These IDs are exclusions, not an allowlist. An empty list means no recorded exclusions.
     *
     * @param  list<int>  $incompatibleInfillIds
     */
    public function __construct(
        public int $id,
        public ComponentType $type,
        public array $incompatibleInfillIds,
        public string $code,
        public string $description,
        public ?DoorColor $color,
    ) {}
}
