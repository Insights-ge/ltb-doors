<?php

namespace App\Services;

use App\Enums\DoorVariantImportColumn;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Writes the door variant import sample: an .xlsx file with only the header row.
 */
class DoorVariantImportSampleWriter
{
    /**
     * @param  string  $path  A file path, or "php://output" to stream it.
     */
    public function write(string $path): void
    {
        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues(array_column(DoorVariantImportColumn::cases(), 'value')));
        $writer->close();
    }
}
