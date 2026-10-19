<?php

namespace App\Services;

use App\Data\DoorVariantImportRowData;
use App\Data\ImportErrorListData;
use App\Enums\DoorColor;
use App\Enums\DoorVariantImportColumn as Column;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Support\ImportErrorCollector;

/**
 * Checks door variant spreadsheet rows against the import rules and the saved door models, components and variants.
 *
 * Not checked: the format of codes, height ranges against the door model or each other,
 * the frame design code against the side profile, and the colour against the components.
 */
class DoorVariantImportValidator
{
    public const int MAX_TEXT_LENGTH = 255;

    /**
     * The size of a TEXT column, in bytes. A Georgian letter takes 3 bytes.
     */
    public const int MAX_FULL_NAME_BYTES = 65535;

    /**
     * The largest value of the unsigned SMALLINT height columns.
     */
    public const int MAX_HEIGHT = 65535;

    /**
     * @var array<string, true>
     */
    private array $doorModelNames = [];

    /**
     * @var array<string, array<string, true>> Saved component unique codes, keyed by type.
     */
    private array $componentUniqueCodes = [];

    /**
     * @var array<string, string> The unique code of the saved variant that has each product code.
     */
    private array $productCodeOwners = [];

    /**
     * @param  list<DoorVariantImportRowData>  $rows
     */
    public function validate(array $rows): ImportErrorListData
    {
        $this->loadSavedRecords($rows);
        $errors = new ImportErrorCollector;

        /** @var array<string, int> $uniqueCodeRows First row of each variant unique code in the file. */
        $uniqueCodeRows = [];

        /** @var array<string, int> $productCodeRows First row of each product code in the file. */
        $productCodeRows = [];

        foreach ($rows as $row) {
            $this->validateDoorModel($row, $errors);
            $this->validateFullName($row, $errors);
            $hasProductCode = $this->validateText($row->rowNumber, Column::ProductCode, $row->productCode, $errors);
            $this->validateColor($row, $errors);
            $this->validateText($row->rowNumber, Column::FrameDesignCode, $row->frameDesignCode, $errors);
            $hasUniqueCode = $this->validateText($row->rowNumber, Column::UniqueCode, $row->uniqueCode, $errors);
            $this->validateHeights($row, $errors);
            $this->validateComponents($row, $errors);

            if ($hasUniqueCode) {
                $this->validateUniqueCodeInFile($row, $uniqueCodeRows, $errors);
            }

            if ($hasProductCode && $hasUniqueCode) {
                $this->validateProductCode($row, $productCodeRows, $errors);
            }
        }

        return $errors->toData();
    }

    /**
     * Loads only the saved records the file refers to.
     *
     * @param  list<DoorVariantImportRowData>  $rows
     */
    private function loadSavedRecords(array $rows): void
    {
        $this->doorModelNames = [];

        foreach (DoorModel::query()->whereIn('name', array_unique(array_column($rows, 'modelName')))->get(['name']) as $doorModel) {
            $this->doorModelNames[$doorModel->name] = true;
        }

        $this->componentUniqueCodes = [];
        $fileUniqueCodes = array_unique(array_merge(...array_map(fn (DoorVariantImportRowData $row): array => array_values($row->componentUniqueCodes()), $rows)));

        foreach (Component::query()->whereIn('unique_code', $fileUniqueCodes)->get(['type', 'unique_code']) as $component) {
            $this->componentUniqueCodes[$component->type->value][$component->unique_code] = true;
        }

        $this->productCodeOwners = [];

        foreach (DoorVariant::query()->whereIn('product_code', array_unique(array_column($rows, 'productCode')))->get(['product_code', 'unique_code']) as $variant) {
            $this->productCodeOwners[$variant->product_code] = $variant->unique_code;
        }
    }

    private function validateDoorModel(DoorVariantImportRowData $row, ImportErrorCollector $errors): void
    {
        if (! $this->validateText($row->rowNumber, Column::ModelName, $row->modelName, $errors)) {
            return;
        }

        if (! isset($this->doorModelNames[$row->modelName])) {
            $errors->add($row->rowNumber, Column::ModelName->value, __('door_variants.import.errors.unknown_door_model', ['name' => $row->modelName]));
        }
    }

    /**
     * The full name is stored in a TEXT column, so it is limited in bytes, not characters.
     */
    private function validateFullName(DoorVariantImportRowData $row, ImportErrorCollector $errors): void
    {
        $column = Column::ProductFullName->value;

        if ($row->productFullName === '') {
            $errors->add($row->rowNumber, $column, __('door_variants.import.errors.required', ['column' => $column]));
        } elseif (strlen($row->productFullName) > self::MAX_FULL_NAME_BYTES) {
            $errors->add($row->rowNumber, $column, __('door_variants.import.errors.full_name_too_long', ['column' => $column]));
        }
    }

    private function validateColor(DoorVariantImportRowData $row, ImportErrorCollector $errors): void
    {
        if ($row->color === '') {
            $errors->add($row->rowNumber, Column::Color->value, __('door_variants.import.errors.required', ['column' => Column::Color->value]));

            return;
        }

        if (DoorColor::tryFromGeorgian($row->color) === null) {
            $allowed = implode(', ', array_map(fn (DoorColor $color): string => $color->georgianName(), DoorColor::cases()));
            $errors->add($row->rowNumber, Column::Color->value, __('door_variants.import.errors.invalid_color', ['value' => $row->color, 'allowed' => $allowed]));
        }
    }

    private function validateHeights(DoorVariantImportRowData $row, ImportErrorCollector $errors): void
    {
        $min = $this->validateHeight($row->rowNumber, Column::HeightRangeMin, $row->heightRangeMin, $errors);
        $max = $this->validateHeight($row->rowNumber, Column::HeightRangeMax, $row->heightRangeMax, $errors);

        if ($min !== null && $max !== null && $min > $max) {
            $errors->add($row->rowNumber, Column::HeightRangeMin->value, __('door_variants.import.errors.height_range_reversed', ['min' => $min, 'max' => $max]));
        }
    }

    /**
     * @return ?int The height, or null when it isn't a whole number from 0 to MAX_HEIGHT.
     */
    private function validateHeight(int $rowNumber, Column $column, string $value, ImportErrorCollector $errors): ?int
    {
        if ($value === '') {
            $errors->add($rowNumber, $column->value, __('door_variants.import.errors.required', ['column' => $column->value]));

            return null;
        }

        if (preg_match('/^\d{1,5}$/', $value) !== 1 || (int) $value > self::MAX_HEIGHT) {
            $errors->add($rowNumber, $column->value, __('door_variants.import.errors.invalid_height', ['column' => $column->value, 'value' => $value, 'max' => self::MAX_HEIGHT]));

            return null;
        }

        return (int) $value;
    }

    /**
     * Every component must already exist with the column's type. Only the partition may be empty.
     */
    private function validateComponents(DoorVariantImportRowData $row, ImportErrorCollector $errors): void
    {
        foreach ($row->componentUniqueCodes() as $header => $uniqueCode) {
            $column = Column::from($header);
            $type = $column->componentType();

            if ($type === null || ($column === Column::PartitionUniqueCode && $uniqueCode === '')) {
                continue;
            }

            if (! $this->validateText($row->rowNumber, $column, $uniqueCode, $errors)) {
                continue;
            }

            if (! isset($this->componentUniqueCodes[$type->value][$uniqueCode])) {
                $errors->add($row->rowNumber, $column->value, __('door_variants.import.errors.unknown_component', [
                    'unique_code' => $uniqueCode,
                    'type' => $type->georgianName(),
                ]));
            }
        }
    }

    /**
     * @param  array<string, int>  $uniqueCodeRows
     */
    private function validateUniqueCodeInFile(DoorVariantImportRowData $row, array &$uniqueCodeRows, ImportErrorCollector $errors): void
    {
        if (isset($uniqueCodeRows[$row->uniqueCode])) {
            $errors->add($row->rowNumber, Column::UniqueCode->value, __('door_variants.import.errors.duplicate_unique_code', [
                'unique_code' => $row->uniqueCode,
                'row' => $uniqueCodeRows[$row->uniqueCode],
            ]));

            return;
        }

        $uniqueCodeRows[$row->uniqueCode] = $row->rowNumber;
    }

    /**
     * A product code may appear once in the file and may not belong to another saved variant,
     * even one that gets a different product code in the same file.
     *
     * @param  array<string, int>  $productCodeRows
     */
    private function validateProductCode(DoorVariantImportRowData $row, array &$productCodeRows, ImportErrorCollector $errors): void
    {
        if (isset($productCodeRows[$row->productCode])) {
            $errors->add($row->rowNumber, Column::ProductCode->value, __('door_variants.import.errors.duplicate_product_code', [
                'product_code' => $row->productCode,
                'row' => $productCodeRows[$row->productCode],
            ]));

            return;
        }

        $productCodeRows[$row->productCode] = $row->rowNumber;
        $owner = $this->productCodeOwners[$row->productCode] ?? null;

        if ($owner !== null && $owner !== $row->uniqueCode) {
            $errors->add($row->rowNumber, Column::ProductCode->value, __('door_variants.import.errors.product_code_taken', [
                'product_code' => $row->productCode,
                'unique_code' => $owner,
            ]));
        }
    }

    /**
     * @return bool Whether the value is filled in and short enough.
     */
    private function validateText(int $rowNumber, Column $column, string $value, ImportErrorCollector $errors): bool
    {
        if ($value === '') {
            $errors->add($rowNumber, $column->value, __('door_variants.import.errors.required', ['column' => $column->value]));

            return false;
        }

        if (mb_strlen($value) > self::MAX_TEXT_LENGTH) {
            $errors->add($rowNumber, $column->value, __('door_variants.import.errors.too_long', ['column' => $column->value, 'max' => self::MAX_TEXT_LENGTH]));

            return false;
        }

        return true;
    }
}
