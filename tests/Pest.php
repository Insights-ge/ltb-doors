<?php

use App\Data\ComponentImportRowData;
use App\Data\DoorVariantImportRowData;
use App\Enums\ComponentType;
use App\Enums\ComponentImportColumn;
use App\Enums\DoorVariantImportColumn;
use App\Models\Component;
use App\Models\DoorModel;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(TestCase::class)
 // ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Writes the rows to a temporary .xlsx file and returns its path. Each row is a list of cell values; an empty list is a blank row.
 *
 * @param  list<list<mixed>>  $rows
 */
function writeSpreadsheet(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'import-test-') . '.xlsx';
    $writer = new Writer;
    $writer->openToFile($path);

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

/**
 * @return list<string>
 */
function componentImportHeaders(): array
{
    return array_column(ComponentImportColumn::cases(), 'value');
}

/**
 * A valid component spreadsheet row, keyed by header.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function componentImportRow(array $overrides = []): array
{
    return array_merge([
        'type' => 'პროფილი გვერდითა',
        'unique_code' => '000015193',
        'code' => 'გალუ4108',
        'description' => 'პროფილი. ნობელალუმინა XGJB-1058P ვწ. ორმხრივი გვერდი',
        'color' => 'შავი',
    ], $overrides);
}

/**
 * A valid component import row DTO.
 *
 * @param  array<string, mixed>  $overrides  Constructor arguments to change.
 */
function componentImportRowData(array $overrides = []): ComponentImportRowData
{
    return new ComponentImportRowData(...array_merge([
        'rowNumber' => 2,
        'type' => 'პროფილი გვერდითა',
        'uniqueCode' => '000015193',
        'code' => 'გალუ4108',
        'description' => 'პროფილი. ნობელალუმინა XGJB-1058P ვწ. ორმხრივი გვერდი',
        'color' => 'შავი',
    ], $overrides));
}

/**
 * @return list<string>
 */
function doorVariantImportHeaders(): array
{
    return array_column(DoorVariantImportColumn::cases(), 'value');
}

/**
 * A valid door variant spreadsheet row (the first row of the seeded catalog), keyed by header.
 *
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function doorVariantImportRow(array $overrides = []): array
{
    return array_merge([
        'model_name' => 'მირზაია',
        'product_full_name' => 'პნლა275002 - გასაგორებელი კარი მირზაია, შავი, 1600-2000, გალუ4108',
        'product_code' => 'პნლა275002',
        'color' => 'შავი',
        'frame_design_code' => 'გალუ4108',
        'unique_code' => '000041482',
        'height_range_min' => '1600',
        'height_range_max' => '2000',
        'side_profile_unique_code' => '000015193',
        'top_rail_unique_code' => '000016298',
        'bottom_rail_unique_code' => '000016296',
        'partition_unique_code' => '000016298',
        'soft_close_mechanism_unique_code' => '000013473',
    ], $overrides);
}

/**
 * A valid door variant import row DTO, matching doorVariantImportRow().
 *
 * @param  array<string, mixed>  $overrides  Constructor arguments to change.
 */
function doorVariantImportRowData(array $overrides = []): DoorVariantImportRowData
{
    return new DoorVariantImportRowData(...array_merge([
        'rowNumber' => 2,
        'modelName' => 'მირზაია',
        'productFullName' => 'პნლა275002 - გასაგორებელი კარი მირზაია, შავი, 1600-2000, გალუ4108',
        'productCode' => 'პნლა275002',
        'color' => 'შავი',
        'frameDesignCode' => 'გალუ4108',
        'uniqueCode' => '000041482',
        'heightRangeMin' => '1600',
        'heightRangeMax' => '2000',
        'sideProfileUniqueCode' => '000015193',
        'topRailUniqueCode' => '000016298',
        'bottomRailUniqueCode' => '000016296',
        'partitionUniqueCode' => '000016298',
        'softCloseMechanismUniqueCode' => '000013473',
    ], $overrides));
}

/**
 * Saves the door model and the components that doorVariantImportRow() refers to.
 */
function createDoorVariantImportReferences(): DoorModel
{
    foreach ([
        ComponentType::SideProfile->value => '000015193',
        ComponentType::TopRail->value => '000016298',
        ComponentType::BottomRail->value => '000016296',
        ComponentType::Partition->value => '000016298',
        ComponentType::SoftCloseMechanism->value => '000013473',
    ] as $type => $uniqueCode) {
        Component::factory()->ofType(ComponentType::from($type))->create(['unique_code' => $uniqueCode]);
    }

    return DoorModel::factory()->create(['name' => 'მირზაია']);
}
