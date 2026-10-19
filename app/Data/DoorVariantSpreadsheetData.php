<?php

namespace App\Data;

/**
 * The rows read from an uploaded door variant spreadsheet, or the errors that prevented reading them.
 */
readonly class DoorVariantSpreadsheetData
{
    /**
     * @param  list<DoorVariantImportRowData>  $rows
     * @param  list<ImportRowErrorData>  $errors  Errors about the whole file (unreadable file, wrong headers, too many rows).
     */
    public function __construct(
        public array $rows,
        public array $errors,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }
}
