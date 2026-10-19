<?php

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\Components\Pages\ListComponents;
use App\Jobs\ImportComponentsJob;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([UserSeeder::class, RoleSeeder::class]);
    Storage::fake(ImportComponentsJob::disk());
    Queue::fake();

    $this->actingAs(User::where('email', 'admin@ltb.ge')->first());
});

/**
 * An uploaded .xlsx with the header row and the given data rows.
 *
 * @param  list<array<string, mixed>>  $rows
 */
function componentImportUpload(array $rows): UploadedFile
{
    $path = writeSpreadsheet([componentImportHeaders(), ...array_map(fn (array $row): array => array_values($row), $rows)]);

    return UploadedFile::fake()->createWithContent('components.xlsx', file_get_contents($path));
}

test('admins see the import action', function () {
    Livewire::test(ListComponents::class)
        ->assertActionVisible('importComponents');
});

test('the sample download is inside the import modal, not on the page', function () {
    Livewire::test(ListComponents::class)
        ->assertActionDoesNotExist('downloadComponentImportSample')
        ->mountAction('importComponents')
        ->assertActionVisible(TestAction::make('downloadComponentImportSample')->schemaComponent('sampleActions'));
});

test('users without the import permission do not see the import action', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User->value);
    $user->givePermissionTo('ViewAny:Component');
    $this->actingAs($user);

    Livewire::test(ListComponents::class)
        ->assertActionHidden('importComponents');
});

test('the sample can be downloaded from the import modal', function () {
    Livewire::test(ListComponents::class)
        ->mountAction('importComponents')
        ->callAction(TestAction::make('downloadComponentImportSample')->schemaComponent('sampleActions'))
        ->assertFileDownloaded(__('components.import.actions.sample_file_name'));
});

test('the preview shows what the file would change', function () {
    Livewire::test(ListComponents::class)
        ->mountAction('importComponents')
        ->fillForm(['file' => componentImportUpload([componentImportRow(), componentImportRow(['unique_code' => '000015192'])])])
        ->assertMountedActionModalSee(__('components.import.actions.preview_counts', ['create' => 2, 'update' => 0, 'unchanged' => 0]));
});

test('the preview lists the errors and the import cannot be confirmed', function () {
    Livewire::test(ListComponents::class)
        ->mountAction('importComponents')
        ->fillForm(['file' => componentImportUpload([componentImportRow(['type' => 'უცნობი'])])])
        ->assertMountedActionModalSee(__('components.import.actions.preview_errors', ['count' => 1]))
        ->assertMountedActionModalSee(__('imports.row_error', [
            'row' => 2,
            'message' => __('components.import.errors.invalid_type', [
                'value' => 'უცნობი',
                'allowed' => 'პროფილი გვერდითა, შემკვრელი ზედა, შემკვრელი ქვედა, ტიხარი, ნელი დაკეტვის მექანიზმი',
            ]),
        ]))
        ->callMountedAction()
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
    expect(Storage::disk(ImportComponentsJob::disk())->allFiles())->toBeEmpty();
});

test('confirming a valid file stores it and queues the import', function () {
    Livewire::test(ListComponents::class)
        ->callAction('importComponents', data: ['file' => componentImportUpload([componentImportRow()])])
        ->assertHasNoFormErrors()
        ->assertNotified(__('components.import.actions.queued_title'));

    $storedFiles = Storage::disk(ImportComponentsJob::disk())->allFiles('component-imports');

    expect($storedFiles)->toHaveCount(1);

    Queue::assertPushed(ImportComponentsJob::class, fn (ImportComponentsJob $job): bool => $job->path === $storedFiles[0]
        && $job->user->email === 'admin@ltb.ge'
        && $job->locale === app()->getLocale());
});

test('a file that is not an xlsx file is rejected', function () {
    Livewire::test(ListComponents::class)
        ->callAction('importComponents', data: ['file' => UploadedFile::fake()->createWithContent('components.csv', "type,unique_code\n")])
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
});

test('a file larger than 5 MB is rejected', function () {
    Livewire::test(ListComponents::class)
        ->callAction('importComponents', data: ['file' => UploadedFile::fake()->create('components.xlsx', 5 * 1024 + 1, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
});
