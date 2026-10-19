<?php

use App\Data\ComponentSpreadsheetData;
use App\Services\ComponentSpreadsheetReader;

/**
 * @param  list<list<mixed>>  $rows
 */
function readComponentSpreadsheet(array $rows, int $maxRows = ComponentSpreadsheetReader::MAX_ROWS): ComponentSpreadsheetData
{
    return (new ComponentSpreadsheetReader($maxRows))->read(writeSpreadsheet($rows));
}

test('a valid file is read into one row per component', function () {
    $result = readComponentSpreadsheet([componentImportHeaders(), array_values(componentImportRow())]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows)->toHaveCount(1);

    $row = $result->rows[0];

    expect($row->rowNumber)->toBe(2)
        ->and($row->type)->toBe('პროფილი გვერდითა')
        ->and($row->uniqueCode)->toBe('000015193')
        ->and($row->code)->toBe('გალუ4108')
        ->and($row->description)->toBe('პროფილი. ნობელალუმინა XGJB-1058P ვწ. ორმხრივი გვერდი')
        ->and($row->color)->toBe('შავი');
});

test('columns can be in any order', function () {
    $headers = array_reverse(componentImportHeaders());
    $row = componentImportRow();

    $result = readComponentSpreadsheet([$headers, array_map(fn (string $header): mixed => $row[$header], $headers)]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows[0]->type)->toBe('პროფილი გვერდითა')
        ->and($result->rows[0]->uniqueCode)->toBe('000015193')
        ->and($result->rows[0]->color)->toBe('შავი');
});

test('a missing column is reported and no rows are read', function () {
    $result = readComponentSpreadsheet([['type', 'unique_code', 'code', 'description'], ['ტიხარი', '000016298', 'გალუ4349', 'აღწერა']]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->rowNumber)->toBeNull()
        ->and($result->errors[0]->column)->toBe('color')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.missing_column', ['column' => 'color']));
});

test('an extra column is reported', function () {
    $result = readComponentSpreadsheet([[...componentImportHeaders(), 'notes'], [...array_values(componentImportRow()), 'შენიშვნა']]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->column)->toBe('notes')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.unknown_column', ['column' => 'notes']));
});

test('a duplicated column is reported', function () {
    $result = readComponentSpreadsheet([[...componentImportHeaders(), 'code'], [...array_values(componentImportRow()), 'გალუ4108']]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors)->toHaveCount(1)
        ->and($result->errors[0]->column)->toBe('code')
        ->and($result->errors[0]->message)->toBe(__('imports.errors.duplicate_column', ['column' => 'code']));
});

test('every header and cell is trimmed', function () {
    $headers = array_map(fn (string $header): string => "  {$header} ", componentImportHeaders());
    $values = array_map(fn (mixed $value): string => " {$value}\t", array_values(componentImportRow()));

    $result = readComponentSpreadsheet([$headers, $values]);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows[0]->type)->toBe('პროფილი გვერდითა')
        ->and($result->rows[0]->uniqueCode)->toBe('000015193')
        ->and($result->rows[0]->color)->toBe('შავი');
});

test('blank rows are skipped and row numbers match excel', function () {
    $result = readComponentSpreadsheet([
        [],
        componentImportHeaders(),
        array_values(componentImportRow()),
        [],
        array_values(componentImportRow(['unique_code' => '000015192'])),
    ]);

    expect($result->hasErrors())->toBeFalse()
        ->and(array_map(fn ($row): int => $row->rowNumber, $result->rows))->toBe([3, 5]);
});

test('an empty colour cell is read as empty text', function () {
    $result = readComponentSpreadsheet([componentImportHeaders(), array_values(componentImportRow(['color' => null]))]);

    expect($result->rows[0]->color)->toBe('');
});

test('numeric cells are read as text without a decimal part', function () {
    $result = readComponentSpreadsheet([componentImportHeaders(), array_values(componentImportRow(['unique_code' => 15193, 'code' => 4108.0]))]);

    expect($result->rows[0]->uniqueCode)->toBe('15193')
        ->and($result->rows[0]->code)->toBe('4108');
});

test('a file with more rows than the limit is rejected', function () {
    $row = array_values(componentImportRow());

    $result = readComponentSpreadsheet([componentImportHeaders(), $row, $row, $row], maxRows: 2);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.too_many_rows', ['max' => 2]));
});

test('a file with exactly the row limit is read', function () {
    $row = array_values(componentImportRow());

    $result = readComponentSpreadsheet([componentImportHeaders(), $row, $row], maxRows: 2);

    expect($result->hasErrors())->toBeFalse()
        ->and($result->rows)->toHaveCount(2);
});

test('the default row limit is 5000', function () {
    expect(ComponentSpreadsheetReader::MAX_ROWS)->toBe(5000)
        ->and(app(ComponentSpreadsheetReader::class))->toEqual(new ComponentSpreadsheetReader(5000));
});

test('a file that is not a spreadsheet is reported as unreadable', function () {
    $path = tempnam(sys_get_temp_dir(), 'import-test-');
    file_put_contents($path, 'not a spreadsheet');

    $result = app(ComponentSpreadsheetReader::class)->read($path);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.file_unreadable'));
});

test('an empty spreadsheet is reported as having no header row', function () {
    $result = readComponentSpreadsheet([]);

    expect($result->rows)->toBeEmpty()
        ->and($result->errors[0]->message)->toBe(__('imports.errors.no_header'));
});
