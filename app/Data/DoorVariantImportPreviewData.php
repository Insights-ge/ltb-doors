<?php

namespace App\Data;

readonly class DoorVariantImportPreviewData
{
    /**
     * @param  ImportErrorListData  $errors  When there are errors, the counts are zero.
     */
    public function __construct(
        public int $toCreateCount,
        public int $toUpdateCount,
        public int $unchangedCount,
        public ImportErrorListData $errors,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors->hasErrors();
    }
}
