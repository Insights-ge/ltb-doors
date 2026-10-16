<?php

namespace App\Services;

use App\Data\DoorVariantImportPreviewData;
use App\Data\DoorVariantImportResultData;
use App\Data\DoorVariantImportRowData;
use App\Data\ImportErrorListData;
use App\Enums\DoorColor;
use App\Enums\DoorVariantImportColumn as Column;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;

/**
 * Previews and imports validated door variant spreadsheet rows.
 *
 * Variants are matched by unique code and the file wins: matching variants are overwritten, the rest are created.
 * Variants that aren't in the file are never changed. Door models and components are only looked up, never created or changed.
 */
class DoorVariantImportService
{
    private const int CHUNK_SIZE = 1000;

    /**
     * The variant attribute that holds the component of each component column.
     *
     * @var array<string, string>
     */
    private const array COMPONENT_ATTRIBUTES = [
        Column::SideProfileUniqueCode->value => 'side_profile_component_id',
        Column::TopRailUniqueCode->value => 'top_rail_component_id',
        Column::BottomRailUniqueCode->value => 'bottom_rail_component_id',
        Column::PartitionUniqueCode->value => 'partition_component_id',
        Column::SoftCloseMechanismUniqueCode->value => 'soft_close_mechanism_component_id',
    ];

    public function __construct(private readonly DoorVariantImportValidator $validator) {}

    /**
     * Counts what an import would do, without saving anything.
     *
     * @param  list<DoorVariantImportRowData>  $rows
     */
    public function preview(array $rows): DoorVariantImportPreviewData
    {
        $errors = $this->validator->validate($rows);

        if ($errors->hasErrors()) {
            return new DoorVariantImportPreviewData(0, 0, 0, $errors);
        }

        [$toCreateCount, $toUpdateCount, $unchangedCount] = [0, 0, 0];

        foreach ($this->filledVariants($rows) as $variant) {
            match (true) {
                ! $variant->exists => $toCreateCount++,
                $variant->isDirty() => $toUpdateCount++,
                default => $unchangedCount++,
            };
        }

        return new DoorVariantImportPreviewData($toCreateCount, $toUpdateCount, $unchangedCount, $errors);
    }

    /**
     * Validates the rows again and saves them in one transaction. Nothing is saved when there is any error.
     *
     * @param  list<DoorVariantImportRowData>  $rows
     */
    public function import(array $rows): DoorVariantImportResultData
    {
        $errors = $this->validator->validate($rows);

        if ($errors->hasErrors()) {
            return new DoorVariantImportResultData(0, 0, 0, $errors);
        }

        return DoorVariant::query()->getConnection()->transaction(fn (): DoorVariantImportResultData => $this->save($rows, $errors));
    }

    /**
     * @param  list<DoorVariantImportRowData>  $rows
     */
    private function save(array $rows, ImportErrorListData $errors): DoorVariantImportResultData
    {
        [$createdCount, $updatedCount, $unchangedCount] = [0, 0, 0];

        foreach ($this->filledVariants($rows) as $variant) {
            match (true) {
                ! $variant->exists => $createdCount++,
                $variant->isDirty() => $updatedCount++,
                default => $unchangedCount++,
            };

            $variant->save();
        }

        return new DoorVariantImportResultData($createdCount, $updatedCount, $unchangedCount, $errors);
    }

    /**
     * The variants of the rows, filled with the row values but not saved: new ones don't exist yet,
     * changed ones are dirty.
     *
     * @param  list<DoorVariantImportRowData>  $rows
     * @return list<DoorVariant>
     */
    private function filledVariants(array $rows): array
    {
        $savedVariants = $this->savedVariants($rows);
        $doorModelIds = $this->doorModelIds($rows);
        $componentIds = $this->componentIds($rows);

        return array_map(function (DoorVariantImportRowData $row) use ($savedVariants, $doorModelIds, $componentIds): DoorVariant {
            $variant = $savedVariants[$row->uniqueCode] ?? new DoorVariant(['unique_code' => $row->uniqueCode]);

            $variant->fill([
                'door_model_id' => $doorModelIds[$row->modelName],
                'product_code' => $row->productCode,
                'product_full_name' => $row->productFullName,
                'color' => DoorColor::tryFromGeorgian($row->color),
                'frame_design_code' => $row->frameDesignCode,
                'height_range_min' => (int) $row->heightRangeMin,
                'height_range_max' => (int) $row->heightRangeMax,
            ]);

            foreach ($row->componentUniqueCodes() as $header => $uniqueCode) {
                $type = Column::from($header)->componentType();
                $variant->setAttribute(self::COMPONENT_ATTRIBUTES[$header], $uniqueCode === '' ? null : $componentIds["{$type?->value}|{$uniqueCode}"]);
            }

            return $variant;
        }, $rows);
    }

    /**
     * The saved variants with the unique codes of the rows, keyed by unique code.
     *
     * @param  list<DoorVariantImportRowData>  $rows
     * @return array<string, DoorVariant>
     */
    private function savedVariants(array $rows): array
    {
        $savedVariants = [];

        foreach (array_chunk(array_unique(array_column($rows, 'uniqueCode')), self::CHUNK_SIZE) as $uniqueCodes) {
            foreach (DoorVariant::query()->whereIn('unique_code', $uniqueCodes)->get() as $variant) {
                $savedVariants[$variant->unique_code] = $variant;
            }
        }

        return $savedVariants;
    }

    /**
     * @param  list<DoorVariantImportRowData>  $rows
     * @return array<string, int> Keyed by door model name.
     */
    private function doorModelIds(array $rows): array
    {
        $doorModelIds = [];

        foreach (DoorModel::query()->whereIn('name', array_unique(array_column($rows, 'modelName')))->get(['id', 'name']) as $doorModel) {
            $doorModelIds[$doorModel->name] = $doorModel->id;
        }

        return $doorModelIds;
    }

    /**
     * @param  list<DoorVariantImportRowData>  $rows
     * @return array<string, int> Keyed by type + unique code.
     */
    private function componentIds(array $rows): array
    {
        $componentIds = [];
        $uniqueCodes = array_unique(array_merge(...array_map(fn (DoorVariantImportRowData $row): array => array_values($row->componentUniqueCodes()), $rows)));

        foreach (Component::query()->whereIn('unique_code', $uniqueCodes)->get(['id', 'type', 'unique_code']) as $component) {
            $componentIds["{$component->type->value}|{$component->unique_code}"] = $component->id;
        }

        return $componentIds;
    }
}
