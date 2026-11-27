<?php

namespace App\Services;

use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;
use App\Data\BuilderInfillData;
use App\Enums\InfillTexture;
use App\Enums\OpeningMeasurement;
use App\Filament\Pages\SlidingDoorBuilder;
use Filament\Schemas\Components\Utilities\Get;
use Illuminate\Support\MessageBag;

class SlidingDoorBuilderFormService
{
    private const OPENING_BOUNDS = [
        'width' => ['min' => 985, 'max' => 5460],
        'height' => ['min' => 238, 'max' => 2738],
    ];

    public static function infillOptionLabel(SlidingDoorBuilder $page, BuilderInfillData $infill): string
    {
        $visual = $page->builderMetadata->neutralVisual;
        foreach ($page->builderMetadata->infillVisuals as $mapping) {
            if ($mapping->infillId === $infill->id) {
                $visual = $mapping->visual;
                break;
            }
        }
        $background = match ($visual->texture) {
            InfillTexture::WoodStraight, InfillTexture::WoodCathedral => 'repeating-linear-gradient(90deg, transparent 0 3px, #0002 3px 4px), ' . $visual->fill,
            InfillTexture::Mirror => 'linear-gradient(135deg, #0003, #fff9, transparent, #fff6, #0003), ' . $visual->fill,
            InfillTexture::Glass => 'linear-gradient(135deg, transparent 35%, #fff9 49%, transparent 60%), ' . $visual->fill,
            default => $visual->fill,
        };

        return '<span style="display:flex;align-items:center;gap:0.5rem"><span aria-hidden="true" style="display:inline-block;width:1.5rem;height:1.5rem;flex-shrink:0;border:1px solid #888;border-radius:0.25rem;background:' . $background . '"></span><span>' . e($infill->code) . ' — ' . e($infill->description) . '</span></span>';
    }

    /** @return list<int> */
    public static function incompatibleInfills(SlidingDoorBuilder $page): array
    {
        foreach ($page->builderCatalog->models as $model) {
            if ($model->id !== self::id($page->builderFields['modelId'])) {
                continue;
            }
            foreach ($model->variants as $variant) {
                if ($variant->id === self::id($page->builderFields['variantId'])) {
                    $ids = [];
                    foreach ($variant->components as $component) {
                        $ids = [...$ids, ...$component->incompatibleInfillIds];
                    }

                    return array_values(array_unique($ids));
                }
            }
        }

        return array_map(fn (BuilderInfillData $infill): int => $infill->id, $page->builderCatalog->infills);
    }

    public static function hasError(SlidingDoorBuilder $page, string $field): bool
    {
        $errors = $page->getErrorBag();

        return $errors instanceof MessageBag && $errors->has('builderFields.' . $field);
    }

    public static function id(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    public static function model(SlidingDoorBuilder $page, Get $get): ?BuilderDoorModelData
    {
        foreach ($page->builderCatalog->models as $model) {
            if ($model->id === self::id($get('modelId'))) {
                return $model;
            }
        }

        return null;
    }

    /** @return list<BuilderDoorVariantData> */
    public static function variants(SlidingDoorBuilder $page, Get $get): array
    {
        return array_values(array_filter(self::matchingVariants($page, $get), fn (BuilderDoorVariantData $variant): bool => $variant->frameDesignCode === $get('frameDesignCode')));
    }

    /** @return list<BuilderDoorVariantData> */
    public static function matchingVariants(SlidingDoorBuilder $page, Get $get): array
    {
        $model = self::model($page, $get);

        return $model === null ? [] : BuilderOpeningSizing::matchingVariants(
            $model,
            BuilderOpeningSizing::measurement($get('openingWidth')),
            BuilderOpeningSizing::measurement($get('openingHeight')),
            self::id($get('doorCount')),
        );
    }

    public static function sizeBound(SlidingDoorBuilder $page, Get $get, OpeningMeasurement $measurement, string $bound): int
    {
        $model = self::model($page, $get);

        $modelBound = match ([$measurement, $bound]) {
            [OpeningMeasurement::Height, 'min'] => $model === null ? null : $model->minimumHeight() + BuilderOpeningSizing::heightBoundDeduction($model, $bound),
            [OpeningMeasurement::Height, 'max'] => $model === null ? null : BuilderOpeningSizing::maximumHeight($model) + BuilderOpeningSizing::heightBoundDeduction($model, $bound),
            default => null,
        };
        $openingBound = self::OPENING_BOUNDS[$measurement->labelKey()][$bound];

        return $bound === 'min' ? max($openingBound, $modelBound ?? $openingBound) : min($openingBound, $modelBound ?? $openingBound);
    }

    public static function supportsSize(BuilderDoorModelData $model, Get $get, ?int $count): bool
    {
        $width = BuilderOpeningSizing::measurement($get('openingWidth'));
        $openingHeight = BuilderOpeningSizing::measurement($get('openingHeight'));

        return $width !== null && $openingHeight !== null && in_array($count, [2, 3, 4, 5], true)
            && $width >= self::OPENING_BOUNDS['width']['min'] && $width <= self::OPENING_BOUNDS['width']['max']
            && $openingHeight >= self::OPENING_BOUNDS['height']['min'] && $openingHeight <= self::OPENING_BOUNDS['height']['max']
            && BuilderOpeningSizing::matchingVariants($model, $width, $openingHeight, $count) !== [];
    }

    /** @return list<int> */
    public static function doorCounts(SlidingDoorBuilder $page, Get $get): array
    {
        return array_values(array_filter([2, 3, 4, 5], fn (int $count): bool => array_any($page->builderCatalog->models, fn (BuilderDoorModelData $model): bool => self::supportsSize($model, $get, $count))));
    }

    public static function variant(SlidingDoorBuilder $page, Get $get): ?BuilderDoorVariantData
    {
        foreach (self::variants($page, $get) as $variant) {
            if ($variant->id === self::id($get('variantId'))) {
                return $variant;
            }
        }

        return null;
    }
}
