<?php

namespace App\Filament\Resources\Components\Actions;

use App\Actions\CreateComponentImportSampleAction;
use App\Actions\DispatchComponentImportAction;
use App\Actions\PreviewComponentImportAction;
use App\Data\ComponentImportPreviewData;
use App\Jobs\ImportComponentsJob;
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
 * The "Import from Excel" header action of the components list page, with "Download sample" inside its modal.
 */
class ComponentImportHeaderActions
{
    /**
     * Previews of the files uploaded in this request, so the preview and the validation read each file once.
     *
     * @var array<string, ComponentImportPreviewData>
     */
    private static array $previews = [];

    private const string XLSX_MIME_TYPE = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

    private const int MAX_FILE_SIZE_KILOBYTES = 5 * 1024;

    public static function import(): Action
    {
        return Action::make('importComponents')
            ->label(__('components.import.actions.import'))
            ->icon(Heroicon::OutlinedArrowUpTray)
            ->authorize('import')
            ->modalHeading(__('components.import.actions.modal_heading'))
            ->modalDescription(__('components.import.actions.modal_description'))
            ->modalSubmitActionLabel(__('components.import.actions.modal_submit'))
            ->schema([
                Actions::make([self::downloadSample()])->key('sampleActions'),
                FileUpload::make('file')
                    ->label(__('components.import.actions.file'))
                    ->acceptedFileTypes([self::XLSX_MIME_TYPE])
                    ->maxSize(self::MAX_FILE_SIZE_KILOBYTES)
                    ->disk(ImportComponentsJob::disk())
                    ->directory('component-imports')
                    ->visibility('private')
                    ->required()
                    ->live()
                    ->rule(fn (): Closure => self::rejectFilesWithErrors(...)),
                TextEntry::make('preview')
                    ->label(__('components.import.actions.preview'))
                    ->state(fn (Get $get): ?HtmlString => self::previewHtml($get('file')))
                    ->visible(fn (Get $get): bool => self::uploadedFile($get('file')) !== null),
            ])
            ->action(fn (array $data) => self::dispatchImport($data));
    }

    private static function downloadSample(): Action
    {
        return Action::make('downloadComponentImportSample')
            ->label(__('components.import.actions.download_sample'))
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize('import')
            ->action(fn (): BinaryFileResponse => self::sampleDownload());
    }

    private static function sampleDownload(): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'components-import-sample-');
        app(CreateComponentImportSampleAction::class)->execute(self::user(), $path);

        return response()
            ->download($path, __('components.import.actions.sample_file_name'), ['Content-Type' => self::XLSX_MIME_TYPE])
            ->deleteFileAfterSend();
    }

    /**
     * @param  array<mixed>  $data  The submitted form; "file" is the stored upload's path.
     */
    private static function dispatchImport(array $data): void
    {
        $path = $data['file'] ?? null;

        if (! is_string($path)) {
            throw new UnexpectedValueException('The component import form was submitted without a stored file.');
        }

        app(DispatchComponentImportAction::class)->execute(self::user(), $path, app()->getLocale());

        Notification::make()
            ->success()
            ->title(__('components.import.actions.queued_title'))
            ->body(__('components.import.actions.queued_body'))
            ->send();
    }

    /**
     * Validation rule of the upload field: the import can't be confirmed while the preview shows errors.
     */
    private static function rejectFilesWithErrors(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value instanceof TemporaryUploadedFile && self::preview($value)->hasErrors()) {
            $fail(__('components.import.actions.file_has_errors'));
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
                __('components.import.actions.preview_errors', ['count' => $preview->errors->totalCount]),
                $preview->errors,
            );
        }

        return new HtmlString(e(__('components.import.actions.preview_counts', [
            'create' => $preview->toCreateCount,
            'update' => $preview->toUpdateCount,
            'unchanged' => $preview->unchangedCount,
        ])));
    }

    private static function preview(TemporaryUploadedFile $file): ComponentImportPreviewData
    {
        return self::$previews[$file->getRealPath()] ??= app(PreviewComponentImportAction::class)->execute(self::user(), $file->getRealPath());
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
            throw new UnexpectedValueException('The component import needs a signed-in user.');
        }

        return $user;
    }
}
