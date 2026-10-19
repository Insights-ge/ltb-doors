<?php

namespace App\Filament\Resources\DoorVariants\Actions;

use App\Actions\CreateDoorVariantImportSampleAction;
use App\Actions\DispatchDoorVariantImportAction;
use App\Actions\PreviewDoorVariantImportAction;
use App\Data\DoorVariantImportPreviewData;
use App\Jobs\ImportDoorVariantsJob;
use App\Models\User;
use App\Support\ImportErrorFormatter;
use Closure;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\FileUpload;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\HtmlString;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use UnexpectedValueException;

/**
 * The "Import from Excel" header action of the door variants list page, with "Download sample" inside its modal.
 */
class DoorVariantImportHeaderActions
{
    /**
     * Previews of the files uploaded in this request, so the preview and the validation read each file once.
     *
     * @var array<string, DoorVariantImportPreviewData>
     */
    private static array $previews = [];

    private const string XLSX_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const int MAX_FILE_SIZE_KILOBYTES = 10 * 1024;

    public static function import(): Action
    {
        return Action::make('importDoorVariants')
            ->label(__('door_variants.import.actions.import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->authorize('import')
            ->modalHeading(__('door_variants.import.actions.modal_heading'))
            ->modalDescription(__('door_variants.import.actions.modal_description'))
            ->modalSubmitActionLabel(__('door_variants.import.actions.modal_submit'))
            ->schema([
                Actions::make([self::downloadSample()])->key('sampleActions'),
                FileUpload::make('file')
                    ->label(__('door_variants.import.actions.file'))
                    ->acceptedFileTypes([self::XLSX_MIME_TYPE])
                    ->maxSize(self::MAX_FILE_SIZE_KILOBYTES)
                    ->disk(ImportDoorVariantsJob::disk())
                    ->directory('door-variant-imports')
                    ->visibility('private')
                    ->required()
                    ->live()
                    ->rule(fn (): Closure => self::rejectFilesWithErrors(...)),
                TextEntry::make('preview')
                    ->label(__('door_variants.import.actions.preview'))
                    ->state(fn (Get $get): ?HtmlString => self::previewHtml($get('file')))
                    ->visible(fn (Get $get): bool => self::uploadedFile($get('file')) !== null),
            ])
            ->action(fn (array $data) => self::dispatchImport($data));
    }

    private static function downloadSample(): Action
    {
        return Action::make('downloadDoorVariantImportSample')
            ->label(__('door_variants.import.actions.download_sample'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize('import')
            ->action(fn (): BinaryFileResponse => self::sampleDownload());
    }

    private static function sampleDownload(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'door-variants-import-sample-');
        app(CreateDoorVariantImportSampleAction::class)->execute(self::user(), $path);

        return response()
            ->download($path, __('door_variants.import.actions.sample_file_name'), ['Content-Type' => self::XLSX_MIME_TYPE])
            ->deleteFileAfterSend();
    }

    /**
     * @param  array<mixed>  $data  The submitted form; "file" is the stored upload's path.
     */
    private static function dispatchImport(array $data): void
    {
        $path = $data['file'] ?? null;

        if (! is_string($path)) {
            throw new UnexpectedValueException('The door variant import form was submitted without a stored file.');
        }

        app(DispatchDoorVariantImportAction::class)->execute(self::user(), $path, app()->getLocale());

        Notification::make()
            ->success()
            ->title(__('door_variants.import.actions.queued_title'))
            ->body(__('door_variants.import.actions.queued_body'))
            ->send();
    }

    /**
     * Validation rule of the upload field: the import can't be confirmed while the preview shows errors.
     */
    private static function rejectFilesWithErrors(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof TemporaryUploadedFile && self::preview($value)->hasErrors()) {
            $fail(__('door_variants.import.actions.file_has_errors'));
        }
    }

    private static function previewHtml(mixed $files): ?HtmlString
    {
        $file = self::uploadedFile($files);

        if ($file === null) {
            return null;
        }

        $preview = self::preview($file);

        if ($preview->hasErrors()) {
            return app(ImportErrorFormatter::class)->toHtml(
                __('door_variants.import.actions.preview_errors', ['count' => $preview->errors->totalCount]),
                $preview->errors,
            );
        }

        return new HtmlString(e(__('door_variants.import.actions.preview_counts', [
            'create' => $preview->toCreateCount,
            'update' => $preview->toUpdateCount,
            'unchanged' => $preview->unchangedCount,
        ])));
    }

    private static function preview(TemporaryUploadedFile $file): DoorVariantImportPreviewData
    {
        return self::$previews[$file->getRealPath()] ??= app(PreviewDoorVariantImportAction::class)->execute(self::user(), $file->getRealPath());
    }

    /**
     * The file uploaded in the field, before the form is submitted.
     */
    private static function uploadedFile(mixed $files): ?TemporaryUploadedFile
    {
        foreach (is_array($files) ? $files : [$files] as $file) {
            if ($file instanceof TemporaryUploadedFile) {
                return $file;
            }
        }

        return null;
    }

    private static function user(): User
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            throw new UnexpectedValueException('The door variant import needs a signed-in user.');
        }

        return $user;
    }
}
