<?php

namespace App\Services;

use App\Data\BuilderCatalogData;
use App\Data\BuilderComponentCompatibilityData;
use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;
use App\Data\BuilderInfillData;
use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Enums\DoorModelStatus;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Models\Infill;

class BuilderCatalogReadService
{
    public function __construct(
        private readonly BuilderCatalogRecordValidator $validator,
    ) {}

    public function read(): BuilderCatalogData
    {
        return new BuilderCatalogData(
            models: $this->readModels(),
            infills: $this->readInfills(),
        );
    }

    /**
     * @return list<BuilderDoorModelData>
     */
    private function readModels(): array
    {
        return array_values(DoorModel::query()
            ->where('status', DoorModelStatus::Active)
            ->select(['id', 'name', 'min_height', 'max_height', 'min_width', 'max_width', 'partition_count', 'not_recommended_height_min', 'not_recommended_height_max'])
            ->with($this->variantRelationships())
            ->orderBy('name')->orderBy('id')
            ->get()
            ->filter(fn (DoorModel $model): bool => $this->validator->isModelUsable($model))
            ->map(fn (DoorModel $model): BuilderDoorModelData => $this->mapModel($model))
            ->filter(fn (BuilderDoorModelData $model): bool => $model->variants !== [])
            ->all());
    }

    /**
     * @return list<string>
     */
    private function variantRelationships(): array
    {
        $relationships = [
            'variants:id,door_model_id,product_full_name,color,frame_design_code,height_range_min,height_range_max,side_profile_component_id,top_rail_component_id,bottom_rail_component_id,partition_component_id,soft_close_mechanism_component_id',
        ];

        foreach (['sideProfile', 'topRail', 'bottomRail', 'partition', 'softCloseMechanism'] as $relation) {
            $relationships[] = "variants.{$relation}:id,type,code,description,color";
            $relationships[] = "variants.{$relation}.incompatibleInfills:infills.id";
        }

        return $relationships;
    }

    private function mapModel(DoorModel $model): BuilderDoorModelData
    {
        return new BuilderDoorModelData(
            id: $model->id,
            name: $model->name,
            minHeight: $model->min_height,
            maxHeight: $model->max_height,
            minWidth: $model->min_width,
            maxWidth: $model->max_width,
            partitionCount: $model->partition_count,
            notRecommendedHeightMin: $this->validator->hasUsableAdvisoryRange($model) ? $model->not_recommended_height_min : null,
            notRecommendedHeightMax: $this->validator->hasUsableAdvisoryRange($model) ? $model->not_recommended_height_max : null,
            variants: array_values($model->variants
                ->filter(fn (DoorVariant $variant): bool => $this->validator->isVariantUsable($variant))->sortBy([
                    ['product_full_name', 'asc'], ['id', 'asc'],
                ])->map(fn (DoorVariant $variant): BuilderDoorVariantData => $this->mapVariant($variant))->all()),
        );
    }

    private function mapVariant(DoorVariant $variant): BuilderDoorVariantData
    {
        $components = [];

        foreach ([
            ComponentType::SideProfile->value => $variant->sideProfile,
            ComponentType::TopRail->value => $variant->topRail,
            ComponentType::BottomRail->value => $variant->bottomRail,
            ComponentType::Partition->value => $variant->partition,
            ComponentType::SoftCloseMechanism->value => $variant->softCloseMechanism,
        ] as $type => $component) {
            if ($component !== null && $this->validator->componentMatches($component, ComponentType::from($type))) {
                $components[] = $this->mapComponent($component);
            }
        }

        return new BuilderDoorVariantData(
            id: $variant->id,
            name: $variant->product_full_name,
            color: $variant->color,
            frameDesignCode: $variant->frame_design_code,
            heightRangeMin: $variant->height_range_min,
            heightRangeMax: $variant->height_range_max,
            components: $components,
        );
    }

    private function mapComponent(Component $component): BuilderComponentCompatibilityData
    {
        return new BuilderComponentCompatibilityData(
            id: $component->id,
            type: $component->type,
            code: $component->code,
            description: $component->description,
            color: is_string($color = $component->getRawOriginal('color')) ? DoorColor::tryFrom($color) : null,
            incompatibleInfillIds: array_values($component->incompatibleInfills
                ->map(fn (Infill $infill): int => $infill->id)->sort()->all()),
        );
    }

    /**
     * @return list<BuilderInfillData>
     */
    private function readInfills(): array
    {
        return array_values(Infill::query()
            ->select(['id', 'code', 'description'])
            ->orderBy('code')->orderBy('id')
            ->get()
            ->filter(fn (Infill $infill): bool => $this->validator->isInfillUsable($infill))
            ->map(fn (Infill $infill): BuilderInfillData => new BuilderInfillData(
                id: $infill->id,
                code: $infill->code,
                description: $infill->description,
            ))
            ->all());
    }
}
