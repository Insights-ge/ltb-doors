<?php

namespace App\Actions;

use App\Data\DoorVariantImportPreviewData;
use App\Data\ImportErrorListData;
use App\Models\DoorVariant;
use App\Models\User;
use App\Services\DoorVariantImportService;
use App\Services\DoorVariantSpreadsheetReader;
use Illuminate\Support\Facades\Gate;

class PreviewDoorVariantImportAction
{
    public function __construct(
        private readonly DoorVariantSpreadsheetReader $reader,
        private readonly DoorVariantImportService $importService,
    ) {}

    /**
     * Reads the spreadsheet and counts what importing it would do, without saving anything.
     *
     * @param  string  $path  The absolute path of the uploaded file.
     */
    public function execute(User $user, string $path): DoorVariantImportPreviewData
    {
        Gate::forUser($user)->authorize('import', DoorVariant::class);

        $spreadsheet = $this->reader->read($path);

        if ($spreadsheet->hasErrors()) {
            $errors = new ImportErrorListData(
                array_slice($spreadsheet->errors, 0, ImportErrorListData::MAX_KEPT_ERRORS),
                count($spreadsheet->errors),
            );

            return new DoorVariantImportPreviewData(0, 0, 0, $errors);
        }

        return $this->importService->preview($spreadsheet->rows);
    }
}
