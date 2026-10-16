<?php

use App\Data\DoorVariantImportPreviewData;
use App\Data\DoorVariantImportResultData;
use App\Data\DoorVariantImportRowData;
use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Enums\DoorModelStatus;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Services\DoorVariantImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->doorModel = createDoorVariantImportReferences();
});

/**
 * @param  list<DoorVariantImportRowData>  $rows
 */
function importDoorVariantRows(array $rows): DoorVariantImportResultData
{
    return app(DoorVariantImportService::class)->import($rows);
}

/**
 * @param  list<DoorVariantImportRowData>  $rows
 */
function previewDoorVariantRows(array $rows): DoorVariantImportPreviewData
{
    return app(DoorVariantImportService::class)->preview($rows);
}

/**
 * @return array{created: int, updated: int, unchanged: int}
 */
function doorVariantImportCounts(DoorVariantImportResultData|DoorVariantImportPreviewData $result): array
{
    return $result instanceof DoorVariantImportResultData
        ? ['created' => $result->createdCount, 'updated' => $result->updatedCount, 'unchanged' => $result->unchangedCount]
        : ['created' => $result->toCreateCount, 'updated' => $result->toUpdateCount, 'unchanged' => $result->unchangedCount];
}

/**
 * The saved component of the given type with the given unique code.
 */
function doorVariantImportComponent(ComponentType $type, string $uniqueCode): Component
{
    return Component::query()->where('type', $type)->where('unique_code', $uniqueCode)->sole();
}

/**
 * Saves the variant that doorVariantImportRowData() describes, so importing that row changes nothing.
 */
function savedImportedDoorVariant(): DoorVariant
{
    importDoorVariantRows([doorVariantImportRowData()]);

    return DoorVariant::query()->where('unique_code', '000041482')->sole();
}

test('a new door variant is created from the file', function () {
    $result = importDoorVariantRows([doorVariantImportRowData()]);

    expect($result->hasErrors())->toBeFalse()
        ->and(doorVariantImportCounts($result))->toBe(['created' => 1, 'updated' => 0, 'unchanged' => 0]);

    $variant = DoorVariant::query()->sole();

    expect($variant->door_model_id)->toBe($this->doorModel->id)
        ->and($variant->unique_code)->toBe('000041482')
        ->and($variant->product_code)->toBe('პნლა275002')
        ->and($variant->product_full_name)->toBe('პნლა275002 - გასაგორებელი კარი მირზაია, შავი, 1600-2000, გალუ4108')
        ->and($variant->color)->toBe(DoorColor::Black)
        ->and($variant->frame_design_code)->toBe('გალუ4108')
        ->and($variant->height_range_min)->toBe(1600)
        ->and($variant->height_range_max)->toBe(2000)
        ->and($variant->side_profile_component_id)->toBe(doorVariantImportComponent(ComponentType::SideProfile, '000015193')->id)
        ->and($variant->top_rail_component_id)->toBe(doorVariantImportComponent(ComponentType::TopRail, '000016298')->id)
        ->and($variant->bottom_rail_component_id)->toBe(doorVariantImportComponent(ComponentType::BottomRail, '000016296')->id)
        ->and($variant->partition_component_id)->toBe(doorVariantImportComponent(ComponentType::Partition, '000016298')->id)
        ->and($variant->soft_close_mechanism_component_id)->toBe(doorVariantImportComponent(ComponentType::SoftCloseMechanism, '000013473')->id);
});

test('a shared unique code is linked to the component of each slot type', function () {
    importDoorVariantRows([doorVariantImportRowData()]);

    $variant = DoorVariant::query()->sole();

    expect($variant->topRail->type)->toBe(ComponentType::TopRail)
        ->and($variant->partition->type)->toBe(ComponentType::Partition)
        ->and($variant->top_rail_component_id)->not->toBe($variant->partition_component_id);
});

test('a variant without a partition is created with no partition', function () {
    importDoorVariantRows([doorVariantImportRowData(['partitionUniqueCode' => ''])]);

    expect(DoorVariant::query()->sole()->partition_component_id)->toBeNull();
});

test('an existing door variant is updated from the file', function () {
    $variant = savedImportedDoorVariant();
    $otherDoorModel = DoorModel::factory()->create(['name' => 'დაისი']);
    $otherSideProfile = Component::factory()->ofType(ComponentType::SideProfile)->create(['unique_code' => '000004465']);

    $result = importDoorVariantRows([doorVariantImportRowData([
        'modelName' => 'დაისი',
        'productFullName' => 'ახალი სახელი',
        'productCode' => 'პნლა999999',
        'color' => 'ინოქსი',
        'frameDesignCode' => 'გალუ9999',
        'heightRangeMin' => '2001',
        'heightRangeMax' => '2600',
        'sideProfileUniqueCode' => '000004465',
    ])]);

    expect(doorVariantImportCounts($result))->toBe(['created' => 0, 'updated' => 1, 'unchanged' => 0])
        ->and(DoorVariant::query()->count())->toBe(1);

    $variant->refresh();

    expect($variant->door_model_id)->toBe($otherDoorModel->id)
        ->and($variant->product_full_name)->toBe('ახალი სახელი')
        ->and($variant->product_code)->toBe('პნლა999999')
        ->and($variant->color)->toBe(DoorColor::Inox)
        ->and($variant->frame_design_code)->toBe('გალუ9999')
        ->and($variant->height_range_min)->toBe(2001)
        ->and($variant->height_range_max)->toBe(2600)
        ->and($variant->side_profile_component_id)->toBe($otherSideProfile->id);
});

test('an empty partition in the file removes the saved partition', function () {
    $variant = savedImportedDoorVariant();

    $result = importDoorVariantRows([doorVariantImportRowData(['partitionUniqueCode' => ''])]);

    expect(doorVariantImportCounts($result))->toBe(['created' => 0, 'updated' => 1, 'unchanged' => 0])
        ->and($variant->refresh()->partition_component_id)->toBeNull();
});

test('an existing door variant with the same values is counted as unchanged and not saved', function () {
    $variant = savedImportedDoorVariant();
    $updatedAt = $variant->updated_at;
    $this->travel(1)->minute();

    $result = importDoorVariantRows([doorVariantImportRowData()]);

    expect(doorVariantImportCounts($result))->toBe(['created' => 0, 'updated' => 0, 'unchanged' => 1])
        ->and($variant->refresh()->updated_at->equalTo($updatedAt))->toBeTrue();
});

test('door variants that are not in the file are not changed', function () {
    $otherVariant = DoorVariant::factory()->create();
    $otherVariantAttributes = $otherVariant->fresh()->getAttributes();

    importDoorVariantRows([doorVariantImportRowData()]);

    expect(DoorVariant::query()->count())->toBe(2)
        ->and($otherVariant->fresh()->getAttributes())->toBe($otherVariantAttributes);
});

test('door models and components are never created or changed', function () {
    $this->doorModel->update(['status' => DoorModelStatus::Passive]);
    $doorModelAttributes = $this->doorModel->fresh()->getAttributes();
    $componentAttributes = Component::query()->orderBy('id')->get()->map->getAttributes()->all();

    importDoorVariantRows([doorVariantImportRowData()]);

    expect(DoorModel::query()->count())->toBe(1)
        ->and($this->doorModel->fresh()->getAttributes())->toBe($doorModelAttributes)
        ->and(Component::query()->orderBy('id')->get()->map->getAttributes()->all())->toBe($componentAttributes);
});

test('an unknown door model fails the import', function () {
    $result = importDoorVariantRows([doorVariantImportRowData(['modelName' => 'უცნობი'])]);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors->errors[0]->column)->toBe('model_name')
        ->and(DoorModel::query()->count())->toBe(1)
        ->and(DoorVariant::query()->count())->toBe(0);
});

test('a missing component fails the import and is not created', function () {
    $result = importDoorVariantRows([doorVariantImportRowData(['bottomRailUniqueCode' => '000099999'])]);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors->errors[0]->column)->toBe('bottom_rail_unique_code')
        ->and(Component::query()->where('unique_code', '000099999')->exists())->toBeFalse()
        ->and(DoorVariant::query()->count())->toBe(0);
});

test('a product code of another saved variant fails the import', function () {
    DoorVariant::factory()->create(['product_code' => 'პნლა275002']);

    $result = importDoorVariantRows([doorVariantImportRowData()]);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors->errors[0]->column)->toBe('product_code')
        ->and(DoorVariant::query()->where('unique_code', '000041482')->exists())->toBeFalse();
});

test('nothing is saved when any row fails', function () {
    $variant = savedImportedDoorVariant();
    $variantAttributes = $variant->fresh()->getAttributes();

    $result = importDoorVariantRows([
        doorVariantImportRowData(['productFullName' => 'ახალი სახელი']),
        doorVariantImportRowData(['rowNumber' => 3, 'uniqueCode' => '000041483', 'productCode' => 'პნლა275003']),
        doorVariantImportRowData(['rowNumber' => 4, 'uniqueCode' => '000041484', 'productCode' => 'პნლა275004', 'color' => 'black']),
    ]);

    expect($result->hasErrors())->toBeTrue()
        ->and($result->errors->errors[0]->rowNumber)->toBe(4)
        ->and(doorVariantImportCounts($result))->toBe(['created' => 0, 'updated' => 0, 'unchanged' => 0])
        ->and(DoorVariant::query()->count())->toBe(1)
        ->and($variant->fresh()->getAttributes())->toBe($variantAttributes);
});

test('the preview counts what the import would do without saving anything', function () {
    $variant = savedImportedDoorVariant();
    $variantAttributes = $variant->fresh()->getAttributes();
    importDoorVariantRows([doorVariantImportRowData(['uniqueCode' => '000041483', 'productCode' => 'პნლა275003'])]);

    $preview = previewDoorVariantRows([
        doorVariantImportRowData(),
        doorVariantImportRowData(['rowNumber' => 3, 'uniqueCode' => '000041483', 'productCode' => 'პნლა275003', 'color' => 'ინოქსი']),
        doorVariantImportRowData(['rowNumber' => 4, 'uniqueCode' => '000041484', 'productCode' => 'პნლა275004']),
        doorVariantImportRowData(['rowNumber' => 5, 'uniqueCode' => '000041485', 'productCode' => 'პნლა275005']),
    ]);

    expect($preview->hasErrors())->toBeFalse()
        ->and(doorVariantImportCounts($preview))->toBe(['created' => 2, 'updated' => 1, 'unchanged' => 1])
        ->and(DoorVariant::query()->count())->toBe(2)
        ->and($variant->fresh()->getAttributes())->toBe($variantAttributes)
        ->and(DoorVariant::query()->where('unique_code', '000041483')->sole()->color)->toBe(DoorColor::Black);
});

test('a preview with errors returns the errors and no counts', function () {
    $preview = previewDoorVariantRows([doorVariantImportRowData(['color' => 'black'])]);

    expect($preview->hasErrors())->toBeTrue()
        ->and($preview->errors->totalCount)->toBe(1)
        ->and(doorVariantImportCounts($preview))->toBe(['created' => 0, 'updated' => 0, 'unchanged' => 0]);
});
