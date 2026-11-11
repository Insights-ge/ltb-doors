<?php

namespace App\Data;

readonly class BuilderDoorModelData
{
    /**
     * Catalog limits are retained as supplied; their interpretation belongs to sizing rules.
     *
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
        public ?int $notRecommendedHeightMin,
        public ?int $notRecommendedHeightMax,
        public array $variants,
    ) {}
}
