<?php

namespace App\Services;

use App\Data\DoorVariantImportRowData;
use App\Data\DoorVariantSpreadsheetData;
use App\Data\ImportRowErrorData;
use App\Enums\DoorVariantImportColumn as Column;
use DateInterval;
use DateTimeInterface;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Exception\OpenSpoutException;
use OpenSpout\Reader\XLSX\Options;
use OpenSpout\Reader\XLSX\Reader;

/**
 * Reads the first sheet of a door variant import spreadsheet into row DTOs.
 * Checks the header row and the row limit; the values themselves are validated later.
 */
class DoorVariantSpreadsheetReader
{
    public const int MAX_ROWS = 10000;

    public function __construct(private readonly int $maxRows = self::MAX_ROWS) {}

    public function read(string $path): DoorVariantSpreadsheetData
    {
        $options = new Options;
        $options->SHOULD_PRESERVE_EMPTY_ROWS = true;
        $reader = new Reader($options);

        try {
            $reader->open($path);

            foreach ($reader->getSheetIterator() as $sheet) {
                return $this->readRows($sheet->getRowIterator());
            }
        } catch (OpenSpoutException) {
            return $this->fileError(__('imports.errors.file_unreadable'));
        } finally {
            $reader->close();
        }

        return $this->fileError(__('imports.errors.no_header'));
    }

    /**
     * Blank rows are skipped. The first non-blank row is the header row.
     *
     * @param  iterable<int, Row>  $sheetRows  Keyed by Excel row number.
     */
    private function readRows(iterable $sheetRows): DoorVariantSpreadsheetData
    {
        $columnIndexes = null;
        $rows = [];

        foreach ($sheetRows as $rowNumber => $sheetRow) {
            if ($sheetRow->isEmpty()) {
                continue;
            }

            $values = array_map($this->cellValue(...), $sheetRow->getCells());

            if ($columnIndexes === null) {
                $headerErrors = $this->headerErrors($values);

                if ($headerErrors !== []) {
                    return new DoorVariantSpreadsheetData([], $headerErrors);
                }

                $columnIndexes = array_flip(array_filter($values, fn (string $header): bool => $header !== ''));

                continue;
            }

            if (count($rows) === $this->maxRows) {
                return $this->fileError(__('imports.errors.too_many_rows', ['max' => $this->maxRows]));
            }

            $rows[] = $this->toRowData($rowNumber, $values, $columnIndexes);
        }

        if ($columnIndexes === null) {
            return $this->fileError(__('imports.errors.no_header'));
        }

        return new DoorVariantSpreadsheetData($rows, []);
    }

    /**
     * Blank header cells are ignored.
     *
     * @param  array<array-key, string>  $headers  Keyed by column index.
     * @return list<ImportRowErrorData>
     */
    private function headerErrors(array $headers): array
    {
        $errors = [];
        $expectedHeaders = array_column(Column::cases(), 'value');
        $filledHeaders = array_filter($headers, fn (string $header): bool => $header !== '');

        foreach (array_diff($expectedHeaders, $filledHeaders) as $missingHeader) {
            $errors[] = new ImportRowErrorData(null, $missingHeader, __('imports.errors.missing_column', ['column' => $missingHeader]));
        }

        foreach (array_diff($filledHeaders, $expectedHeaders) as $unknownHeader) {
            $errors[] = new ImportRowErrorData(null, $unknownHeader, __('imports.errors.unknown_column', ['column' => $unknownHeader]));
        }

        foreach (array_keys(array_filter(array_count_values($filledHeaders), fn (int $count): bool => $count > 1)) as $duplicateHeader) {
            $errors[] = new ImportRowErrorData(null, (string) $duplicateHeader, __('imports.errors.duplicate_column', ['column' => $duplicateHeader]));
        }

        return $errors;
    }

    /**
     * @param  array<array-key, string>  $values  Keyed by column index.
     * @param  array<string, array-key>  $columnIndexes
     */
    private function toRowData(int $rowNumber, array $values, array $columnIndexes): DoorVariantImportRowData
    {
        $value = fn (Column $column): string => $values[$columnIndexes[$column->value]] ?? '';

        return new DoorVariantImportRowData(
            rowNumber: $rowNumber,
            modelName: $value(Column::ModelName),
            productFullName: $value(Column::ProductFullName),
            productCode: $value(Column::ProductCode),
            color: $value(Column::Color),
            frameDesignCode: $value(Column::FrameDesignCode),
            uniqueCode: $value(Column::UniqueCode),
            heightRangeMin: $value(Column::HeightRangeMin),
            heightRangeMax: $value(Column::HeightRangeMax),
            sideProfileUniqueCode: $value(Column::SideProfileUniqueCode),
            topRailUniqueCode: $value(Column::TopRailUniqueCode),
            bottomRailUniqueCode: $value(Column::BottomRailUniqueCode),
            partitionUniqueCode: $value(Column::PartitionUniqueCode),
            softCloseMechanismUniqueCode: $value(Column::SoftCloseMechanismUniqueCode),
        );
    }

    /**
     * Every cell becomes trimmed text. Whole numbers lose their ".0" (e.g. a height or a code typed as a number).
     */
    private function cellValue(Cell $cell): string
    {
        $value = $cell instanceof FormulaCell ? $cell->getComputedValue() : $cell->getValue();

        return Str::trim(match (true) {
            $value === null => '',
            is_bool($value) => $value ? 'TRUE' : 'FALSE',
            is_float($value) && floor($value) === $value && abs($value) < PHP_INT_MAX => (string) (int) $value,
            $value instanceof DateTimeInterface => $value->format('Y-m-d H:i:s'),
            $value instanceof DateInterval => $value->format('%H:%I:%S'),
            default => (string) $value,
        });
    }

    private function fileError(string $message): DoorVariantSpreadsheetData
    {
        return new DoorVariantSpreadsheetData([], [new ImportRowErrorData(null, null, $message)]);
    }
}
