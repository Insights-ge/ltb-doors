<?php

use App\Enums\Role as RoleEnum;
use App\Filament\Resources\DoorVariants\Pages\ListDoorVariants;
use App\Jobs\ImportDoorVariantsJob;
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
    createDoorVariantImportReferences();
    Storage::fake(ImportDoorVariantsJob::disk());
    Queue::fake();

    $this->actingAs(User::where('email', 'admin@ltb.ge')->first());
});

/**
 * An uploaded .xlsx with the header row and the given data rows.
 *
 * @param  list<array<string, mixed>>  $rows
 */
function doorVariantImportUpload(array $rows): UploadedFile
{
    $path = writeSpreadsheet([doorVariantImportHeaders(), ...array_map(fn (array $row): array => array_values($row), $rows)]);

    return UploadedFile::fake()->createWithContent('door-variants.xlsx', file_get_contents($path));
}

test('admins see the import action', function () {
    Livewire::test(ListDoorVariants::class)
        ->assertActionVisible('importDoorVariants');
});

test('the sample download is inside the import modal, not on the page', function () {
    Livewire::test(ListDoorVariants::class)
        ->assertActionDoesNotExist('downloadDoorVariantImportSample')
        ->mountAction('importDoorVariants')
        ->assertActionVisible(TestAction::make('downloadDoorVariantImportSample')->schemaComponent('sampleActions'));
});

test('users without the import permission do not see the import action', function () {
    $user = User::factory()->create();
    $user->assignRole(RoleEnum::User->value);
    $user->givePermissionTo('ViewAny:DoorVariant');
    $this->actingAs($user);

    Livewire::test(ListDoorVariants::class)
        ->assertActionHidden('importDoorVariants');
});

test('the sample can be downloaded from the import modal', function () {
    Livewire::test(ListDoorVariants::class)
        ->mountAction('importDoorVariants')
        ->callAction(TestAction::make('downloadDoorVariantImportSample')->schemaComponent('sampleActions'))
        ->assertFileDownloaded(__('door_variants.import.actions.sample_file_name'));
});

test('the preview shows what the file would change', function () {
    Livewire::test(ListDoorVariants::class)
        ->mountAction('importDoorVariants')
        ->fillForm(['file' => doorVariantImportUpload([
            doorVariantImportRow(),
            doorVariantImportRow(['unique_code' => '000041483', 'product_code' => 'პნლა275003']),
        ])])
        ->assertMountedActionModalSee(__('door_variants.import.actions.preview_counts', ['create' => 2, 'update' => 0, 'unchanged' => 0]));
});

test('the preview lists the errors and the import cannot be confirmed', function () {
    Livewire::test(ListDoorVariants::class)
        ->mountAction('importDoorVariants')
        ->fillForm(['file' => doorVariantImportUpload([doorVariantImportRow(['model_name' => 'უცნობი'])])])
        ->assertMountedActionModalSee(__('door_variants.import.actions.preview_errors', ['count' => 1]))
        ->assertMountedActionModalSee(__('imports.row_error', [
            'row' => 2,
            'message' => __('door_variants.import.errors.unknown_door_model', ['name' => 'უცნობი']),
        ]))
        ->callMountedAction()
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
    expect(Storage::disk(ImportDoorVariantsJob::disk())->allFiles())->toBeEmpty();
});

test('confirming a valid file stores it and queues the import', function () {
    Livewire::test(ListDoorVariants::class)
        ->callAction('importDoorVariants', data: ['file' => doorVariantImportUpload([doorVariantImportRow()])])
        ->assertHasNoFormErrors()
        ->assertNotified(__('door_variants.import.actions.queued_title'));

    $storedFiles = Storage::disk(ImportDoorVariantsJob::disk())->allFiles('door-variant-imports');

    expect($storedFiles)->toHaveCount(1);

    Queue::assertPushed(ImportDoorVariantsJob::class, fn (ImportDoorVariantsJob $job): bool => $job->path === $storedFiles[0]
        && $job->user->email === 'admin@ltb.ge'
        && $job->locale === app()->getLocale());
});

test('a file that is not an xlsx file is rejected', function () {
    Livewire::test(ListDoorVariants::class)
        ->callAction('importDoorVariants', data: ['file' => UploadedFile::fake()->createWithContent('door-variants.csv', "model_name,unique_code\n")])
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
});

test('a file larger than 10 MB is rejected', function () {
    Livewire::test(ListDoorVariants::class)
        ->callAction('importDoorVariants', data: ['file' => UploadedFile::fake()->create('door-variants.xlsx', 10 * 1024 + 1, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])
        ->assertHasFormErrors(['file']);

    Queue::assertNothingPushed();
});
