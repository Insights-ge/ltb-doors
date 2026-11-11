<?php

namespace App\Services;

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Models\Infill;

class BuilderCatalogRecordValidator
{
    public function isModelUsable(DoorModel $model): bool
    {
        return $model->partition_count >= 1 && $model->partition_count <= 4
            && trim($model->name) !== ''
            && $model->min_height > 0 && $model->max_height >= $model->min_height
            && $model->min_width > 0 && $model->max_width >= $model->min_width;
    }

    public function isVariantUsable(DoorVariant $variant): bool
    {
        $color = $variant->getRawOriginal('color');

        return trim($variant->product_full_name) !== ''
            && trim($variant->frame_design_code) !== ''
            && is_string($color) && DoorColor::tryFrom($color) !== null
            && $variant->height_range_min > 0 && $variant->height_range_max >= $variant->height_range_min
            && $this->componentMatches($variant->sideProfile, ComponentType::SideProfile)
            && $this->componentMatches($variant->topRail, ComponentType::TopRail)
            && $this->componentMatches($variant->bottomRail, ComponentType::BottomRail);
    }

    public function componentMatches(?Component $component, ComponentType $type): bool
    {
        return $component?->getRawOriginal('type') === $type->value;
    }

    public function hasUsableAdvisoryRange(DoorModel $model): bool
    {
        return $model->not_recommended_height_min !== null
            && $model->not_recommended_height_max !== null
            && $model->not_recommended_height_min > 0
            && $model->not_recommended_height_max >= $model->not_recommended_height_min;
    }

    public function isInfillUsable(Infill $infill): bool
    {
        return trim($infill->code) !== '' && trim($infill->description) !== '';
    }
}
