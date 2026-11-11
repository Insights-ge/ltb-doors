<?php

namespace App\Services;

use App\Data\BuilderFrameColorData;
use App\Data\BuilderInfillVisualData;
use App\Data\BuilderMetadataData;
use App\Data\BuilderVisualData;
use App\Enums\DoorColor;
use App\Enums\InfillTexture;
use App\Models\Infill;

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
        return array_values(Infill::query()->select(['id', 'visual_color', 'visual_texture'])->orderBy('id')->get()
            ->map(function (Infill $infill) use ($neutral): BuilderInfillVisualData {
                $texture = $infill->getRawOriginal('visual_texture');

                return new BuilderInfillVisualData($infill->id, new BuilderVisualData(
                    fill: $this->readVisual($infill->visual_color, $neutral)->fill,
                    texture: is_string($texture) ? (InfillTexture::tryFrom($texture) ?? InfillTexture::Solid) : InfillTexture::Solid,
                ));
            })->all());
    }
}
