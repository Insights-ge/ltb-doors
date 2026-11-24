<?php

namespace App\Data;

readonly class BuilderDoorModelData
{
    /**
     * Catalog limits are retained as supplied; their interpretation belongs to sizing rules.
     *
     * @param  list<array{type: 'fixed', fixed_mm: int}|array{type: 'flexible', min_mm: int, weight: int}>|null  $panelLayout
     * @param  list<BuilderDoorVariantData>  $variants
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $minHeight,
        public int $maxHeight,
        public int $minWidth,
        public int $maxWidth,
        public int $partitionCount,
        public ?array $panelLayout,
        public ?int $notRecommendedHeightMin,
        public ?int $notRecommendedHeightMax,
        public array $variants,
    ) {}

    public function minimumHeight(): int
    {
        return max($this->minHeight, array_sum(array_column($this->panelLayout ?? [], 'fixed_mm'))
            + array_sum(array_column($this->panelLayout ?? [], 'min_mm')));
    }
}
