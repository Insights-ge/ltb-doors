<?php

use App\Actions\CreateDoorVariantImportSampleAction;
use App\Actions\DispatchDoorVariantImportAction;
use App\Actions\PreviewDoorVariantImportAction;
use App\Enums\Role as RoleEnum;
use App\Jobs\ImportDoorVariantsJob;
use App\Models\DoorVariant;
use App\Models\User;
use App\Services\DoorVariantSpreadsheetReader;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([UserSeeder::class, RoleSeeder::class]);
    createDoorVariantImportReferences();

    $this->admin = User::where('email', 'admin@ltb.ge')->first();
    $this->user = User::factory()->create();
    $this->user->assignRole(RoleEnum::User->value);
});

test('the preview counts what the file would change', function () {
    $path = writeSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow())]);

    $preview = app(PreviewDoorVariantImportAction::class)->execute($this->admin, $path);

    expect($preview->hasErrors())->toBeFalse()
        ->and($preview->toCreateCount)->toBe(1)
        ->and(DoorVariant::query()->count())->toBe(0);
});

test('the preview reports invalid rows', function () {
    $path = writeSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow(['model_name' => 'უცნობი']))]);

    $preview = app(PreviewDoorVariantImportAction::class)->execute($this->admin, $path);

    expect($preview->hasErrors())->toBeTrue()
        ->and($preview->errors->errors[0]->rowNumber)->toBe(2)
        ->and($preview->errors->errors[0]->column)->toBe('model_name');
});

test('the preview reports errors about the whole file', function () {
    $path = writeSpreadsheet([['model_name'], ['მირზაია']]);

    $preview = app(PreviewDoorVariantImportAction::class)->execute($this->admin, $path);

    expect($preview->hasErrors())->toBeTrue()
        ->and($preview->errors->totalCount)->toBe(12)
        ->and($preview->errors->errors[0]->rowNumber)->toBeNull();
});

test('the import is queued for the user in their language', function () {
    Storage::fake(ImportDoorVariantsJob::disk());
    Queue::fake();
    Storage::disk(ImportDoorVariantsJob::disk())->put('door-variant-imports/upload.xlsx', 'content');

    app(DispatchDoorVariantImportAction::class)->execute($this->admin, 'door-variant-imports/upload.xlsx', 'ka');

    Queue::assertPushed(ImportDoorVariantsJob::class, fn (ImportDoorVariantsJob $job): bool => $job->path === 'door-variant-imports/upload.xlsx'
        && $job->user->is($this->admin)
        && $job->locale === 'ka');
});

test('an import is not queued when the upload does not exist', function () {
    Storage::fake(ImportDoorVariantsJob::disk());
    Queue::fake();

    expect(fn () => app(DispatchDoorVariantImportAction::class)->execute($this->admin, 'door-variant-imports/missing.xlsx', 'ka'))
        ->toThrow(InvalidArgumentException::class);

    Queue::assertNothingPushed();
});

test('the sample has only the header row', function () {
    $path = tempnam(sys_get_temp_dir(), 'import-test-') . '.xlsx';

    app(CreateDoorVariantImportSampleAction::class)->execute($this->admin, $path);

    $sample = app(DoorVariantSpreadsheetReader::class)->read($path);

    expect($sample->hasErrors())->toBeFalse()
        ->and($sample->rows)->toBeEmpty();
});

test('users without the import permission cannot preview, import or download the sample', function () {
    Storage::fake(ImportDoorVariantsJob::disk());
    Queue::fake();
    Storage::disk(ImportDoorVariantsJob::disk())->put('door-variant-imports/upload.xlsx', 'content');
    $path = writeSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow())]);

    expect(fn () => app(PreviewDoorVariantImportAction::class)->execute($this->user, $path))->toThrow(AuthorizationException::class)
        ->and(fn () => app(DispatchDoorVariantImportAction::class)->execute($this->user, 'door-variant-imports/upload.xlsx', 'ka'))->toThrow(AuthorizationException::class)
        ->and(fn () => app(CreateDoorVariantImportSampleAction::class)->execute($this->user, tempnam(sys_get_temp_dir(), 'import-test-')))->toThrow(AuthorizationException::class);

    Queue::assertNothingPushed();
});

test('the component import permission does not allow importing door variants', function () {
    $this->user->givePermissionTo('Import:Component');
    $path = writeSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow())]);

    expect(fn () => app(PreviewDoorVariantImportAction::class)->execute($this->user, $path))->toThrow(AuthorizationException::class);
});

test('super admins can use the import actions', function () {
    $superAdmin = User::where('email', 'demo@demo.com')->first();
    $path = writeSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow())]);

    expect(app(PreviewDoorVariantImportAction::class)->execute($superAdmin, $path)->toCreateCount)->toBe(1);
});
