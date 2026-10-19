<?php

namespace App\Jobs;

use App\Data\ComponentImportResultData;
use App\Data\ImportErrorListData;
use App\Models\User;
use App\Services\ComponentImportService;
use App\Services\ComponentSpreadsheetReader;
use App\Support\ImportErrorFormatter;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Traits\Localizable;
use Throwable;

/**
 * Imports an uploaded component spreadsheet, deletes the upload, and notifies the user who started it.
 */
class ImportComponentsJob implements ShouldQueue
{
    use Localizable, Queueable;

    /**
     * A 5,000-row import takes a few seconds. Kept below the database queue's retry_after (90 s),
     * so the queue never starts this job again while it is still running.
     */
    public int $timeout = 60;

    /**
     * The upload is deleted after the first attempt, so there is nothing to retry.
     */
    public int $tries = 1;

    /**
     * @param  string  $path  The upload's path on the local disk.
     * @param  string  $locale  The language of the notification: the user's language when they started the import.
     */
    public function __construct(
        public string $path,
        public User $user,
        public string $locale,
    ) {}

    /**
     * The disk the upload is stored on until this job has read it.
     */
    public static function disk(): string
    {
        return config()->string('filesystems.imports_disk');
    }

    public function handle(ComponentSpreadsheetReader $reader, ComponentImportService $importService): void
    {
        try {
            $result = $this->import($reader, $importService);
        } finally {
            Storage::disk(self::disk())->delete($this->path);
        }

        $this->withLocale($this->locale, fn () => $this->resultNotification($result)->sendToDatabase($this->user));
    }

    /**
     * Called when the job throws or times out. Nothing was saved, because the import runs in one transaction.
     */
    public function failed(?Throwable $exception): void
    {
        Storage::disk(self::disk())->delete($this->path);

        $this->withLocale($this->locale, fn () => Notification::make()
            ->danger()
            ->title(__('components.import.notifications.failed_title'))
            ->body(__('components.import.notifications.crashed_body'))
            ->sendToDatabase($this->user));
    }

    private function import(ComponentSpreadsheetReader $reader, ComponentImportService $importService): ComponentImportResultData
    {
        $spreadsheet = $reader->read(Storage::disk(self::disk())->path($this->path));

        if ($spreadsheet->hasErrors()) {
            $errors = new ImportErrorListData(
                array_slice($spreadsheet->errors, 0, ImportErrorListData::MAX_KEPT_ERRORS),
                count($spreadsheet->errors),
            );

            return new ComponentImportResultData(0, 0, 0, $errors);
        }

        return $importService->import($spreadsheet->rows);
    }

    private function resultNotification(ComponentImportResultData $result): Notification
    {
        if ($result->hasErrors()) {
            return Notification::make()
                ->danger()
                ->title(__('components.import.notifications.failed_title'))
                ->body(app(ImportErrorFormatter::class)->toHtml(
                    __('components.import.notifications.failed_body', ['count' => $result->errors->totalCount]),
                    $result->errors,
                ));
        }

        return Notification::make()
            ->success()
            ->title(__('components.import.notifications.succeeded_title'))
            ->body(__('components.import.notifications.succeeded_body', [
                'created' => $result->createdCount,
                'updated' => $result->updatedCount,
                'unchanged' => $result->unchangedCount,
            ]));
    }
}
