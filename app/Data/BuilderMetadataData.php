<?php

namespace App\Data;

readonly class BuilderMetadataData
{
    /**
     * @param  list<BuilderFrameColorData>  $frameColors
     * @param  list<BuilderInfillVisualData>  $infillVisuals
     */
    public function __construct(
        public array $frameColors,
        public array $infillVisuals,
        public BuilderVisualData $neutralVisual,
    ) {}
}
