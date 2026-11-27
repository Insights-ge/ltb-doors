<?php

namespace App\Services;

use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;

class BuilderOpeningSizing
{
    private const OPENING_HEIGHT_DEDUCTION_MM = 38;

    /** @return list<BuilderDoorVariantData> */
    public static function matchingVariants(BuilderDoorModelData $model, ?float $width, ?float $height, ?int $count): array
    {
        if ($width === null || $height === null || $count === null || $count < 2 || $count > 5) {
            return [];
        }

        return array_values(array_filter(self::supportedVariants($model), fn (BuilderDoorVariantData $variant): bool => self::variantSupportsHeight($model, $variant, $height)
            && self::variantSupportsWidth($model, $variant, $width, $count)));
    }

    public static function hasVariantForWidth(BuilderDoorModelData $model, float $width, int $count): bool
    {
        return array_any(self::supportedVariants($model), fn (BuilderDoorVariantData $variant): bool => self::variantSupportsWidth($model, $variant, $width, $count));
    }

    public static function maximumHeight(BuilderDoorModelData $model): int
    {
        return max($model->maxHeight, $model->notRecommendedHeightMax ?? $model->maxHeight);
    }

    public static function heightBoundDeduction(BuilderDoorModelData $model, string $bound): int
    {
        $deductions = array_map(self::heightDeduction(...), self::supportedVariants($model));

        return $deductions === [] ? self::OPENING_HEIGHT_DEDUCTION_MM : ($bound === 'min' ? min($deductions) : max($deductions));
    }

    public static function measurement(mixed $value): ?float
    {
        $value = is_string($value) ? str_replace(',', '.', trim($value)) : $value;

        return is_numeric($value) && is_finite((float) $value) && (float) $value > 0 ? (float) $value : null;
    }

    /** @return list<BuilderDoorVariantData> */
    private static function supportedVariants(BuilderDoorModelData $model): array
    {
        return array_values(array_filter($model->variants, fn (BuilderDoorVariantData $variant): bool => $model->partitionCount === 0 || $variant->hasPartition));
    }

    private static function variantSupportsHeight(BuilderDoorModelData $model, BuilderDoorVariantData $variant, float $openingHeight): bool
    {
        $maximum = $variant->heightRangeMax >= $model->maxHeight
            ? max($variant->heightRangeMax, self::maximumHeight($model)) : $variant->heightRangeMax;

        $height = $openingHeight - self::heightDeduction($variant);

        return $height >= max($model->minimumHeight(), $variant->heightRangeMin)
            && $height <= min(self::maximumHeight($model), $maximum);
    }

    private static function heightDeduction(BuilderDoorVariantData $variant): int
    {
        return $variant->heightDeduction ?? self::OPENING_HEIGHT_DEDUCTION_MM;
    }

    private static function variantSupportsWidth(BuilderDoorModelData $model, BuilderDoorVariantData $variant, float $openingWidth, int $count): bool
    {
        $overlap = $variant->overlapWidth;
        $width = $overlap === null
            ? $openingWidth / $count
            : floor(($openingWidth + ($count === 2 ? $overlap : 2 * $overlap)) / $count);

        return $width >= $model->minWidth && $width <= $model->maxWidth;
    }
}
