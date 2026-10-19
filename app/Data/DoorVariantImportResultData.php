<?php

namespace App\Data;

readonly class DoorVariantImportResultData
{
    /**
     * @param  ImportErrorListData  $errors  When there are errors, nothing was saved and the counts are zero.
     */
    public function __construct(
        public int $createdCount,
        public int $updatedCount,
        public int $unchangedCount,
        public ImportErrorListData $errors,
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors->hasErrors();
    }
}
