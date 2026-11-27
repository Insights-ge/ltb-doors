<?php

namespace App\Data;

use App\Enums\ComponentType;
use App\Enums\DoorColor;

readonly class BuilderDoorVariantData
{
    public bool $hasPartition;

    public bool $hasSoftCloseMechanism;

    /**
     * Components identify their role by type, including optional partitions and soft close.
     *
     * @param  list<BuilderComponentCompatibilityData>  $components
     */
    public function __construct(
        public int $id,
        public string $name,
        public DoorColor $color,
        public string $frameDesignCode,
        public int $heightRangeMin,
        public int $heightRangeMax,
        public array $components,
        public ?int $heightDeduction = null,
        public ?int $overlapWidth = null,
    ) {
        $this->hasPartition = $this->hasComponent(ComponentType::Partition);
        $this->hasSoftCloseMechanism = $this->hasComponent(ComponentType::SoftCloseMechanism);
    }

    private function hasComponent(ComponentType $type): bool
    {
        foreach ($this->components as $component) {
            if ($component->type === $type) {
                return true;
            }
        }

        return false;
    }
}
