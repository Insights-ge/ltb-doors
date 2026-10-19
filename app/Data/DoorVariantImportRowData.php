<?php

namespace App\Data;

use App\Enums\DoorVariantImportColumn as Column;

/**
 * One spreadsheet row, with trimmed and not yet validated values.
 */
readonly class DoorVariantImportRowData
{
    /**
     * @param  int  $rowNumber  The row number as shown in Excel (the header is row 1).
     * @param  string  $color  The Georgian colour name, e.g. "შავი".
     * @param  string  $heightRangeMin  As written in the file; the validator checks it is a whole number.
     * @param  string  $heightRangeMax  As written in the file; the validator checks it is a whole number.
     * @param  string  $partitionUniqueCode  Empty when the variant has no partition.
     */
    public function __construct(
        public int $rowNumber,
        public string $modelName,
        public string $productFullName,
        public string $productCode,
        public string $color,
        public string $frameDesignCode,
        public string $uniqueCode,
        public string $heightRangeMin,
        public string $heightRangeMax,
        public string $sideProfileUniqueCode,
        public string $topRailUniqueCode,
        public string $bottomRailUniqueCode,
        public string $partitionUniqueCode,
        public string $softCloseMechanismUniqueCode,
    ) {}

    /**
     * The unique code in each component column, keyed by the column's header.
     *
     * @return array<string, string>
     */
    public function componentUniqueCodes(): array
    {
        return [
            Column::SideProfileUniqueCode->value => $this->sideProfileUniqueCode,
            Column::TopRailUniqueCode->value => $this->topRailUniqueCode,
            Column::BottomRailUniqueCode->value => $this->bottomRailUniqueCode,
            Column::PartitionUniqueCode->value => $this->partitionUniqueCode,
            Column::SoftCloseMechanismUniqueCode->value => $this->softCloseMechanismUniqueCode,
        ];
    }
}
