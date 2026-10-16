<?php

use App\Jobs\ImportDoorVariantsJob;
use App\Models\DoorVariant;
use App\Models\User;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(ImportDoorVariantsJob::disk());
    Notification::fake();
    createDoorVariantImportReferences();

    $this->user = User::factory()->create();
});

/**
 * Stores a spreadsheet with the given rows as an upload and returns its path.
 *
 * @param  list<list<mixed>>  $rows
 */
function storeDoorVariantUpload(array $rows): string
{
    $path = 'door-variant-imports/upload.xlsx';
    Storage::disk(ImportDoorVariantsJob::disk())->put($path, file_get_contents(writeSpreadsheet($rows)));

    return $path;
}

/**
 * Stores a spreadsheet with the header row and the given data rows as an upload and returns its path.
 *
 * @param  list<array<string, mixed>>  $rows
 */
function storeDoorVariantRowsUpload(array $rows): string
{
    return storeDoorVariantUpload([doorVariantImportHeaders(), ...array_map(fn (array $row): array => array_values($row), $rows)]);
}

/**
 * @return array{title: string, body: string, status: string}
 */
function sentDoorVariantImportNotification(User $user): array
{
    $sentNotifications = Notification::sent($user, DatabaseNotification::class);

    expect($sentNotifications)->toHaveCount(1);

    $data = $sentNotifications->first()->data;

    return ['title' => $data['title'], 'body' => (string) $data['body'], 'status' => $data['status']];
}

test('a valid upload is imported, deleted and reported to the user', function () {
    $path = storeDoorVariantRowsUpload([doorVariantImportRow()]);

    ImportDoorVariantsJob::dispatchSync($path, $this->user, app()->getLocale());

    expect(DoorVariant::query()->where('unique_code', '000041482')->exists())->toBeTrue()
        ->and(Storage::disk(ImportDoorVariantsJob::disk())->exists($path))->toBeFalse()
        ->and(sentDoorVariantImportNotification($this->user))->toBe([
            'title' => __('door_variants.import.notifications.succeeded_title'),
            'body' => __('door_variants.import.notifications.succeeded_body', ['created' => 1, 'updated' => 0, 'unchanged' => 0]),
            'status' => 'success',
        ]);
});

test('an upload that fails validation saves nothing, is deleted and the errors are reported', function () {
    $path = storeDoorVariantRowsUpload([
        doorVariantImportRow(),
        doorVariantImportRow(['unique_code' => '000041483', 'product_code' => 'პნლა275003', 'model_name' => 'უცნობი']),
    ]);

    ImportDoorVariantsJob::dispatchSync($path, $this->user, app()->getLocale());

    $notification = sentDoorVariantImportNotification($this->user);

    expect(DoorVariant::query()->count())->toBe(0)
        ->and(Storage::disk(ImportDoorVariantsJob::disk())->exists($path))->toBeFalse()
        ->and($notification['title'])->toBe(__('door_variants.import.notifications.failed_title'))
        ->and($notification['status'])->toBe('danger')
        ->and($notification['body'])->toContain(e(__('door_variants.import.notifications.failed_body', ['count' => 1])))
        ->and($notification['body'])->toContain(e(__('imports.row_error', [
            'row' => 3,
            'message' => __('door_variants.import.errors.unknown_door_model', ['name' => 'უცნობი']),
        ])));
});

test('an upload with wrong headers is deleted and the header errors are reported', function () {
    $path = storeDoorVariantUpload([['model_name', 'unique_code'], ['მირზაია', '000041482']]);

    ImportDoorVariantsJob::dispatchSync($path, $this->user, app()->getLocale());

    expect(Storage::disk(ImportDoorVariantsJob::disk())->exists($path))->toBeFalse()
        ->and(sentDoorVariantImportNotification($this->user)['body'])->toContain(e(__('imports.errors.missing_column', ['column' => 'product_code'])));
});

test('the failure report lists at most 200 errors and says how many more there are', function () {
    $rows = array_map(fn (int $index): array => doorVariantImportRow([
        'unique_code' => "u{$index}",
        'product_code' => "p{$index}",
        'color' => 'x',
    ]), range(1, 250));

    ImportDoorVariantsJob::dispatchSync(storeDoorVariantRowsUpload($rows), $this->user, app()->getLocale());

    $body = sentDoorVariantImportNotification($this->user)['body'];

    expect(substr_count($body, '<br>'))->toBe(201)
        ->and($body)->toContain(e(__('imports.more_errors', ['count' => 50])));
});

test('the report is written in the language the user had when starting the import', function () {
    app()->setLocale('en');

    ImportDoorVariantsJob::dispatchSync(storeDoorVariantRowsUpload([doorVariantImportRow()]), $this->user, 'ka');

    expect(sentDoorVariantImportNotification($this->user)['title'])->toBe('კარის ვარიანტების იმპორტი დასრულდა')
        ->and(app()->getLocale())->toBe('en');
});

test('when the job crashes the upload is deleted and the user is told nothing was saved', function () {
    $path = storeDoorVariantRowsUpload([doorVariantImportRow()]);

    (new ImportDoorVariantsJob($path, $this->user, app()->getLocale()))->failed(new RuntimeException('Database went away'));

    expect(Storage::disk(ImportDoorVariantsJob::disk())->exists($path))->toBeFalse()
        ->and(sentDoorVariantImportNotification($this->user))->toBe([
            'title' => __('door_variants.import.notifications.failed_title'),
            'body' => __('door_variants.import.notifications.crashed_body'),
            'status' => 'danger',
        ]);
});

test('the job finishes before the queue would start it again', function () {
    $job = new ImportDoorVariantsJob('door-variant-imports/upload.xlsx', $this->user, 'ka');

    expect($job->timeout)->toBeLessThan(config('queue.connections.database.retry_after'))
        ->and($job->tries)->toBe(1);
});
