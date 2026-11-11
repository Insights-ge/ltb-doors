<?php

namespace App\Services;

use App\Data\BuilderFrameColorData;
use App\Data\BuilderInfillVisualData;
use App\Data\BuilderMetadataData;
use App\Data\BuilderVisualData;
use App\Enums\DoorColor;

class BuilderMetadataReadService
{
    public function read(): BuilderMetadataData
    {
        $neutral = $this->readVisual(config('builder.neutral_fill'), new BuilderVisualData('#B8B8B8'));

        return new BuilderMetadataData(
            frameColors: array_map(
                fn (DoorColor $color): BuilderFrameColorData => new BuilderFrameColorData(
                    color: $color,
                    label: $color->getLabel(),
                    visual: $this->readVisual(config('builder.frame_colors.' . $color->value), $neutral),
                ),
                DoorColor::cases(),
            ),
            infillVisuals: $this->readInfillVisuals($neutral),
            neutralVisual: $neutral,
        );
    }

    private function readVisual(mixed $fill, BuilderVisualData $fallback): BuilderVisualData
    {
        if (! is_string($fill) || preg_match('/\A#[0-9a-fA-F]{6}\z/', $fill) !== 1) {
            return $fallback;
        }

        return new BuilderVisualData(strtoupper($fill));
    }

    /**
     * @return list<BuilderInfillVisualData>
     */
    private function readInfillVisuals(BuilderVisualData $neutral): array
    {
        $mappings = config('builder.infill_visuals', []);

        if (! is_array($mappings)) {
            return [];
        }

        $visuals = [];

        foreach ($mappings as $id => $fill) {
            if (is_int($id) && $id > 0) {
                $visuals[] = new BuilderInfillVisualData($id, $this->readVisual($fill, $neutral));
            }
        }

        usort($visuals, fn (BuilderInfillVisualData $first, BuilderInfillVisualData $second): int => $first->infillId <=> $second->infillId);

        return $visuals;
    }
}
