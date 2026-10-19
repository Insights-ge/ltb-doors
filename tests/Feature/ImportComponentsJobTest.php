<?php

use App\Enums\ComponentType;
use App\Jobs\ImportComponentsJob;
use App\Models\Component;
use App\Models\User;
use Filament\Notifications\DatabaseNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake(ImportComponentsJob::disk());
    Notification::fake();

    $this->user = User::factory()->create();
});

/**
 * Stores a spreadsheet with the given rows as an upload and returns its path.
 *
 * @param  list<list<mixed>>  $rows
 */
function storeComponentUpload(array $rows): string
{
    $path = 'component-imports/upload.xlsx';
    Storage::disk(ImportComponentsJob::disk())->put($path, file_get_contents(writeSpreadsheet($rows)));

    return $path;
}

/**
 * Stores a spreadsheet with the header row and the given data rows as an upload and returns its path.
 *
 * @param  list<array<string, mixed>>  $rows
 */
function storeComponentRowsUpload(array $rows): string
{
    return storeComponentUpload([componentImportHeaders(), ...array_map(fn (array $row): array => array_values($row), $rows)]);
}

/**
 * @return array{title: string, body: string, status: string}
 */
function sentComponentImportNotification(User $user): array
{
    $sentNotifications = Notification::sent($user, DatabaseNotification::class);

    expect($sentNotifications)->toHaveCount(1);

    $data = $sentNotifications->first()->data;

    return ['title' => $data['title'], 'body' => (string) $data['body'], 'status' => $data['status']];
}

test('a valid upload is imported, deleted and reported to the user', function () {
    $path = storeComponentRowsUpload([componentImportRow()]);

    ImportComponentsJob::dispatchSync($path, $this->user, app()->getLocale());

    expect(Component::query()->where('unique_code', '000015193')->where('type', ComponentType::SideProfile)->exists())->toBeTrue()
        ->and(Storage::disk(ImportComponentsJob::disk())->exists($path))->toBeFalse()
        ->and(sentComponentImportNotification($this->user))->toBe([
            'title' => __('components.import.notifications.succeeded_title'),
            'body' => __('components.import.notifications.succeeded_body', ['created' => 1, 'updated' => 0, 'unchanged' => 0]),
            'status' => 'success',
        ]);
});

test('an upload that fails validation saves nothing, is deleted and the errors are reported', function () {
    $path = storeComponentRowsUpload([componentImportRow(), componentImportRow(['unique_code' => '000015192', 'color' => 'black'])]);

    ImportComponentsJob::dispatchSync($path, $this->user, app()->getLocale());

    $notification = sentComponentImportNotification($this->user);

    expect(Component::query()->count())->toBe(0)
        ->and(Storage::disk(ImportComponentsJob::disk())->exists($path))->toBeFalse()
        ->and($notification['title'])->toBe(__('components.import.notifications.failed_title'))
        ->and($notification['status'])->toBe('danger')
        ->and($notification['body'])->toContain(e(__('imports.row_error', [
            'row' => 3,
            'message' => __('components.import.errors.invalid_color', ['value' => 'black', 'allowed' => 'შავი, ინოქსი, გრაფიტი, სილვერი']),
        ])));
});

test('an upload with wrong headers is deleted and the header errors are reported', function () {
    $path = storeComponentUpload([['type', 'unique_code'], ['ტიხარი', '000016298']]);

    ImportComponentsJob::dispatchSync($path, $this->user, app()->getLocale());

    expect(Storage::disk(ImportComponentsJob::disk())->exists($path))->toBeFalse()
        ->and(sentComponentImportNotification($this->user)['body'])->toContain(e(__('imports.errors.missing_column', ['column' => 'description'])));
});

test('the failure report lists at most 200 errors and says how many more there are', function () {
    $rows = array_map(fn (int $index): array => componentImportRow(['unique_code' => "u{$index}", 'type' => 'უცნობი']), range(1, 250));

    ImportComponentsJob::dispatchSync(storeComponentRowsUpload($rows), $this->user, app()->getLocale());

    $body = sentComponentImportNotification($this->user)['body'];

    expect(substr_count($body, e(__('imports.row_error', ['row' => 2, 'message' => '']))))->toBe(1)
        ->and(substr_count($body, '<br>'))->toBe(201)
        ->and($body)->toContain(e(__('imports.more_errors', ['count' => 50])));
});

test('the report is written in the language the user had when starting the import', function () {
    app()->setLocale('en');

    ImportComponentsJob::dispatchSync(storeComponentRowsUpload([componentImportRow()]), $this->user, 'ka');

    expect(sentComponentImportNotification($this->user)['title'])->toBe('კომპონენტების იმპორტი დასრულდა')
        ->and(app()->getLocale())->toBe('en');
});

test('when the job crashes the upload is deleted and the user is told nothing was saved', function () {
    $path = storeComponentRowsUpload([componentImportRow()]);

    (new ImportComponentsJob($path, $this->user, app()->getLocale()))->failed(new RuntimeException('Database went away'));

    expect(Storage::disk(ImportComponentsJob::disk())->exists($path))->toBeFalse()
        ->and(sentComponentImportNotification($this->user))->toBe([
            'title' => __('components.import.notifications.failed_title'),
            'body' => __('components.import.notifications.crashed_body'),
            'status' => 'danger',
        ]);
});

test('the job finishes before the queue would start it again', function () {
    $job = new ImportComponentsJob('component-imports/upload.xlsx', $this->user, 'ka');

    expect($job->timeout)->toBeLessThan(config('queue.connections.database.retry_after'))
        ->and($job->tries)->toBe(1);
});
