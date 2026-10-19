<?php

use App\Data\DoorVariantSpreadsheetData;
use App\Services\DoorVariantSpreadsheetReader;

/**
 * @param  list<list<mixed>>  $rows
 */
function readDoorVariantSpreadsheet(array $rows, int $maxRows = DoorVariantSpreadsheetReader::MAX_ROWS): DoorVariantSpreadsheetData
{
    return (new DoorVariantSpreadsheetReader($maxRows))->read(writeSpreadsheet($rows));
}

test('a valid file is read into one row per door variant', function () {
    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow())]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows)->toHaveCount(1);

    $row = $result->rows[0];

    expect($row->rowNumber)->toBe(2)
        ->and($row->modelName)->toBe('მირზაია')
        ->and($row->productFullName)->toBe('პნლა275002 - გასაგორებელი კარი მირზაია, შავი, 1600-2000, გალუ4108')
        ->and($row->productCode)->toBe('პნლა275002')
        ->and($row->color)->toBe('შავი')
        ->and($row->frameDesignCode)->toBe('გალუ4108')
        ->and($row->uniqueCode)->toBe('000041482')
        ->and($row->heightRangeMin)->toBe('1600')
        ->and($row->heightRangeMax)->toBe('2000')
        ->and($row->sideProfileUniqueCode)->toBe('000015193')
        ->and($row->topRailUniqueCode)->toBe('000016298')
        ->and($row->bottomRailUniqueCode)->toBe('000016296')
        ->and($row->partitionUniqueCode)->toBe('000016298')
        ->and($row->softCloseMechanismUniqueCode)->toBe('000013473');
});

test('columns can be in any order', function () {
    $headers = array_reverse(doorVariantImportHeaders());
    $row = doorVariantImportRow();

    $result = readDoorVariantSpreadsheet([$headers, array_map(fn (string $header): mixed => $row[$header], $headers)]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows[0]->modelName)->toBe('მირზაია')
        ->and($result->rows[0]->uniqueCode)->toBe('000041482')
        ->and($result->rows[0]->softCloseMechanismUniqueCode)->toBe('000013473');
});

test('a missing column is reported and no rows are read', function () {
    $headers = array_values(array_diff(doorVariantImportHeaders(), ['partition_unique_code']));
    $row = doorVariantImportRow();

    $result = readDoorVariantSpreadsheet([$headers, array_map(fn (string $header): mixed => $row[$header], $headers)]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->rowNumber)->toBeNull()
        ->and($result->errors[0]->column)->toBe('partition_unique_code')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.missing_column', ['column' => 'partition_unique_code']));
});

test('a column from the old 33-column file is reported as unknown', function () {
    $result = readDoorVariantSpreadsheet([[...doorVariantImportHeaders(), 'min_height'], [...array_values(doorVariantImportRow()), '1600']]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->column)->toBe('min_height')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.unknown_column', ['column' => 'min_height']));
});

test('a duplicated column is reported', function () {
    $result = readDoorVariantSpreadsheet([[...doorVariantImportHeaders(), 'color'], [...array_values(doorVariantImportRow()), 'შავი']]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->column)->toBe('color')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.duplicate_column', ['column' => 'color']));
});

test('every header and cell is trimmed', function () {
    $headers = array_map(fn (string $header): string => "  {$header} ", doorVariantImportHeaders());
    $values = array_map(fn (mixed $value): string => " {$value}\t", array_values(doorVariantImportRow()));

    $result = readDoorVariantSpreadsheet([$headers, $values]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows[0]->modelName)->toBe('მირზაია')
        ->and($result->rows[0]->uniqueCode)->toBe('000041482')
        ->and($result->rows[0]->heightRangeMin)->toBe('1600');
});

test('blank rows are skipped and row numbers match excel', function () {
    $result = readDoorVariantSpreadsheet([
        [],
        doorVariantImportHeaders(),
        array_values(doorVariantImportRow()),
        [],
        array_values(doorVariantImportRow(['unique_code' => '000041483'])),
    ]);

    expect($result->hasErrors())->toBeFalse()
        ->and(array_map(fn ($row): int => $row->rowNumber, $result->rows))->toBe([3, 5]);
});

test('an empty partition cell is read as empty text', function () {
    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow(['partition_unique_code' => null]))]);

    expect($result->rows[0]->partitionUniqueCode)->toBe('');
});

test('numeric cells are read as text without a decimal part', function () {
    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow([
        'height_range_min' => 1600,
        'height_range_max' => 2000.0,
        'unique_code' => 41482,
    ]))]);

    expect($result->rows[0]->heightRangeMin)->toBe('1600')
        ->and($result->rows[0]->heightRangeMax)->toBe('2000')
        ->and($result->rows[0]->uniqueCode)->toBe('41482');
});

test('a height with a decimal part keeps it so the validator can reject it', function () {
    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), array_values(doorVariantImportRow(['height_range_min' => 1600.5]))]);

    expect($result->rows[0]->heightRangeMin)->toBe('1600.5');
});

test('a file with more rows than the limit is rejected', function () {
    $row = array_values(doorVariantImportRow());

    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), $row, $row, $row], maxRows: 2);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.too_many_rows', ['max' => 2]));
});

test('a file with exactly the row limit is read', function () {
    $row = array_values(doorVariantImportRow());

    $result = readDoorVariantSpreadsheet([doorVariantImportHeaders(), $row, $row], maxRows: 2);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows)->toHaveCount(2);
});

test('the default row limit is 10000', function () {
    expect(DoorVariantSpreadsheetReader::MAX_ROWS)->toBe(10000)
        ->and(app(DoorVariantSpreadsheetReader::class))->toEqual(new DoorVariantSpreadsheetReader(10000));
});

test('a file that is not a spreadsheet is reported as unreadable', function () {
    $path = tempnam(sys_get_temp_dir(), 'import-test-');
    file_put_contents($path, 'not a spreadsheet');

    $result = app(DoorVariantSpreadsheetReader::class)->read($path);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.file_unreadable'));
});

test('an empty spreadsheet is reported as having no header row', function () {
    $result = readDoorVariantSpreadsheet([]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.no_header'));
});
