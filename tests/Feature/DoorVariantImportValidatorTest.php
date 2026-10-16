<?php

use App\Data\DoorVariantImportRowData;
use App\Data\ImportErrorListData;
use App\Data\ImportRowErrorData;
use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Services\DoorVariantImportValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    createDoorVariantImportReferences();
});

/**
 * @param  list<DoorVariantImportRowData>  $rows
 */
function validateDoorVariantRows(array $rows): ImportErrorListData
{
    return app(DoorVariantImportValidator::class)->validate($rows);
}

/**
 * @return list<string> Each error as "row|column|message".
 */
function doorVariantValidationErrors(ImportErrorListData $result): array
{
    return array_map(fn (ImportRowErrorData $error): string => "{$error->rowNumber}|{$error->column}|{$error->message}", $result->errors);
}

test('a valid row has no errors', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData()])->hasErrors())->toBeFalse();
});

test('required fields must be filled in', function (string $field, string $column) {
    $result = validateDoorVariantRows([doorVariantImportRowData([$field => ''])]);

    expect(doorVariantValidationErrors($result))->toBe(["2|{$column}|" . __('door_variants.import.errors.required', ['column' => $column])]);
})->with([
    ['modelName', 'model_name'],
    ['productFullName', 'product_full_name'],
    ['productCode', 'product_code'],
    ['color', 'color'],
    ['frameDesignCode', 'frame_design_code'],
    ['uniqueCode', 'unique_code'],
    ['heightRangeMin', 'height_range_min'],
    ['heightRangeMax', 'height_range_max'],
    ['sideProfileUniqueCode', 'side_profile_unique_code'],
    ['topRailUniqueCode', 'top_rail_unique_code'],
    ['bottomRailUniqueCode', 'bottom_rail_unique_code'],
    ['softCloseMechanismUniqueCode', 'soft_close_mechanism_unique_code'],
]);

test('the partition is optional', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData(['partitionUniqueCode' => ''])])->hasErrors())->toBeFalse();
});

test('text longer than 255 characters is rejected', function (string $field, string $column) {
    $result = validateDoorVariantRows([doorVariantImportRowData([$field => str_repeat('ა', 256)])]);

    expect(doorVariantValidationErrors($result))->toBe(["2|{$column}|" . __('door_variants.import.errors.too_long', ['column' => $column, 'max' => 255])]);
})->with([
    ['modelName', 'model_name'],
    ['productCode', 'product_code'],
    ['frameDesignCode', 'frame_design_code'],
    ['uniqueCode', 'unique_code'],
    ['sideProfileUniqueCode', 'side_profile_unique_code'],
    ['partitionUniqueCode', 'partition_unique_code'],
]);

test('the full name may be up to 65535 bytes', function () {
    $fullName = str_repeat('ა', intdiv(65535, 3));

    expect(validateDoorVariantRows([doorVariantImportRowData(['productFullName' => $fullName])])->hasErrors())->toBeFalse();
});

test('a full name longer than 65535 bytes is rejected', function () {
    $result = validateDoorVariantRows([doorVariantImportRowData(['productFullName' => str_repeat('ა', intdiv(65535, 3) + 1)])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|product_full_name|' . __('door_variants.import.errors.full_name_too_long', ['column' => 'product_full_name'])]);
});

test('every georgian colour name is accepted', function (DoorColor $color) {
    expect(validateDoorVariantRows([doorVariantImportRowData(['color' => $color->georgianName()])])->hasErrors())->toBeFalse();
})->with(DoorColor::cases());

test('a colour that is not a georgian colour name is rejected', function (string $color) {
    $result = validateDoorVariantRows([doorVariantImportRowData(['color' => $color])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|color|' . __('door_variants.import.errors.invalid_color', [
        'value' => $color,
        'allowed' => 'შავი, ინოქსი, გრაფიტი, სილვერი',
    ])]);
})->with(['black', 'ინოქს']);

test('heights must be whole numbers from 0 to 65535', function (string $height) {
    $result = validateDoorVariantRows([doorVariantImportRowData(['heightRangeMax' => $height])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|height_range_max|' . __('door_variants.import.errors.invalid_height', [
        'column' => 'height_range_max',
        'value' => $height,
        'max' => 65535,
    ])]);
})->with(['2000.5', '-1', 'abc', '65536', '2 000']);

test('the lowest and highest heights are accepted', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData(['heightRangeMin' => '0', 'heightRangeMax' => '65535'])])->hasErrors())->toBeFalse();
});

test('the height range may not start above its end', function () {
    $result = validateDoorVariantRows([doorVariantImportRowData(['heightRangeMin' => '2001', 'heightRangeMax' => '2000'])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|height_range_min|' . __('door_variants.import.errors.height_range_reversed', ['min' => 2001, 'max' => 2000])]);
});

test('a height range of a single height is accepted', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData(['heightRangeMin' => '2000', 'heightRangeMax' => '2000'])])->hasErrors())->toBeFalse();
});

test('height ranges are not checked against the door model', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData(['heightRangeMin' => '1', 'heightRangeMax' => '9000'])])->hasErrors())->toBeFalse();
});

test('the door model must exist', function () {
    $result = validateDoorVariantRows([doorVariantImportRowData(['modelName' => 'უცნობი'])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|model_name|' . __('door_variants.import.errors.unknown_door_model', ['name' => 'უცნობი'])]);
});

test('variants of a passive door model are accepted', function () {
    DoorModel::query()->where('name', 'მირზაია')->update(['status' => 'passive']);

    expect(validateDoorVariantRows([doorVariantImportRowData()])->hasErrors())->toBeFalse();
});

test('every component must exist', function (string $field, string $column, ComponentType $type) {
    $result = validateDoorVariantRows([doorVariantImportRowData([$field => '000099999'])]);

    expect(doorVariantValidationErrors($result))->toBe(["2|{$column}|" . __('door_variants.import.errors.unknown_component', [
        'unique_code' => '000099999',
        'type' => $type->georgianName(),
    ])]);
})->with([
    ['sideProfileUniqueCode', 'side_profile_unique_code', ComponentType::SideProfile],
    ['topRailUniqueCode', 'top_rail_unique_code', ComponentType::TopRail],
    ['bottomRailUniqueCode', 'bottom_rail_unique_code', ComponentType::BottomRail],
    ['partitionUniqueCode', 'partition_unique_code', ComponentType::Partition],
    ['softCloseMechanismUniqueCode', 'soft_close_mechanism_unique_code', ComponentType::SoftCloseMechanism],
]);

test('a component that exists only under another type is rejected', function () {
    Component::factory()->ofType(ComponentType::TopRail)->create(['unique_code' => '000077777']);

    $result = validateDoorVariantRows([doorVariantImportRowData(['partitionUniqueCode' => '000077777'])]);

    expect(doorVariantValidationErrors($result))->toBe(['2|partition_unique_code|' . __('door_variants.import.errors.unknown_component', [
        'unique_code' => '000077777',
        'type' => 'ტიხარი',
    ])]);
});

test('a unique code may appear only once in the file', function () {
    $result = validateDoorVariantRows([
        doorVariantImportRowData(),
        doorVariantImportRowData(['rowNumber' => 3, 'productCode' => 'პნლა275003']),
    ]);

    expect(doorVariantValidationErrors($result))->toBe(['3|unique_code|' . __('door_variants.import.errors.duplicate_unique_code', [
        'unique_code' => '000041482',
        'row' => 2,
    ])]);
});

test('a product code may appear only once in the file', function () {
    $result = validateDoorVariantRows([
        doorVariantImportRowData(),
        doorVariantImportRowData(['rowNumber' => 3, 'uniqueCode' => '000041483']),
    ]);

    expect(doorVariantValidationErrors($result))->toBe(['3|product_code|' . __('door_variants.import.errors.duplicate_product_code', [
        'product_code' => 'პნლა275002',
        'row' => 2,
    ])]);
});

test('a product code of another saved variant is rejected', function () {
    DoorVariant::factory()->create(['product_code' => 'პნლა275002', 'unique_code' => '000041483']);

    $result = validateDoorVariantRows([doorVariantImportRowData()]);

    expect(doorVariantValidationErrors($result))->toBe(['2|product_code|' . __('door_variants.import.errors.product_code_taken', [
        'product_code' => 'პნლა275002',
        'unique_code' => '000041483',
    ])]);
});

test('a product code of another saved variant is rejected even when that variant gets a new one in the same file', function () {
    DoorVariant::factory()->create(['product_code' => 'პნლა275002', 'unique_code' => '000041483']);

    $result = validateDoorVariantRows([
        doorVariantImportRowData(),
        doorVariantImportRowData(['rowNumber' => 3, 'uniqueCode' => '000041483', 'productCode' => 'პნლა275003']),
    ]);

    expect(array_map(fn (ImportRowErrorData $error): string => "{$error->rowNumber}|{$error->column}", $result->errors))->toBe(['2|product_code']);
});

test('a saved variant may keep its own product code', function () {
    DoorVariant::factory()->create(['product_code' => 'პნლა275002', 'unique_code' => '000041482']);

    expect(validateDoorVariantRows([doorVariantImportRowData()])->hasErrors())->toBeFalse();
});

test('codes are not checked for a format', function () {
    Component::factory()->ofType(ComponentType::SideProfile)->create(['unique_code' => '15193']);

    expect(validateDoorVariantRows([doorVariantImportRowData([
        'uniqueCode' => '41482',
        'productCode' => 'any text',
        'sideProfileUniqueCode' => '15193',
    ])])->hasErrors())->toBeFalse();
});

test('the frame design code does not have to match the side profile', function () {
    expect(validateDoorVariantRows([doorVariantImportRowData(['frameDesignCode' => 'სხვა'])])->hasErrors())->toBeFalse();
});

test('all errors of a row are reported', function () {
    $result = validateDoorVariantRows([doorVariantImportRowData([
        'modelName' => 'x',
        'productFullName' => '',
        'productCode' => '',
        'color' => 'x',
        'frameDesignCode' => '',
        'uniqueCode' => '',
        'heightRangeMin' => 'x',
        'heightRangeMax' => '',
        'sideProfileUniqueCode' => 'x',
        'topRailUniqueCode' => '',
        'bottomRailUniqueCode' => 'x',
        'partitionUniqueCode' => 'x',
        'softCloseMechanismUniqueCode' => '',
    ])]);

    expect(array_map(fn (ImportRowErrorData $error): ?string => $error->column, $result->errors))->toBe([
        'model_name',
        'product_full_name',
        'product_code',
        'color',
        'frame_design_code',
        'unique_code',
        'height_range_min',
        'height_range_max',
        'side_profile_unique_code',
        'top_rail_unique_code',
        'bottom_rail_unique_code',
        'partition_unique_code',
        'soft_close_mechanism_unique_code',
    ]);
});

test('only the first 200 errors are kept but all of them are counted', function () {
    $rows = array_map(fn (int $index): DoorVariantImportRowData => doorVariantImportRowData([
        'rowNumber' => $index,
        'uniqueCode' => "u{$index}",
        'productCode' => "p{$index}",
        'color' => 'x',
    ]), range(2, 251));

    $result = validateDoorVariantRows($rows);

    expect($result->errors)->toHaveCount(200)
        ->and($result->totalCount)->toBe(250)
        ->and($result->errors[0]->rowNumber)->toBe(2)
        ->and($result->errors[199]->rowNumber)->toBe(201);
});

test('the saved records are read with a fixed number of queries', function () {
    $rows = array_map(fn (int $index): DoorVariantImportRowData => doorVariantImportRowData([
        'rowNumber' => $index,
        'uniqueCode' => "u{$index}",
        'productCode' => "p{$index}",
    ]), range(2, 101));

    DB::enableQueryLog();
    $result = validateDoorVariantRows($rows);

    expect($result->hasErrors())->toBeFalse()
        ->and(DB::getQueryLog())->toHaveCount(3);
});

test('an empty file has no errors', function () {
    expect(validateDoorVariantRows([])->hasErrors())->toBeFalse();
});
