<?php

use App\Models\DoorModel;
use App\Models\DoorVariant;
use Database\Seeders\DoorCatalogSeeder;
use Database\Seeders\DoorLayoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DoorCatalogSeeder::class);
    $this->seed(DoorLayoutSeeder::class);
});

test('layout seeding stores all workbook layouts in panel order', function () {
    $expected = [
        'მირზაია' => [3, [0 => 400, 2 => 400], 800],
        'დავლური' => [3, [1 => 400], 800],
        'დაისი' => [3, [1 => 200, 2 => 400], 800],
        'დაირა' => [3, [0 => 400, 1 => 200], 800],
        'ფარცა' => [5, [], 200],
        'ნადური' => [4, [], 200],
        'სამაია' => [3, [], 200],
        'ჯეირანი' => [2, [], 200],
        'სიმდი' => [1, [], 200],
    ];
    $models = DoorModel::all()->keyBy('name');

    expect($models)->toHaveCount(count($expected));
    foreach ($expected as $name => [$count, $fixedHeights, $flexibleMinimum]) {
        $layout = array_fill(0, $count, ['type' => 'flexible', 'min_mm' => $flexibleMinimum, 'weight' => 1]);
        foreach ($fixedHeights as $index => $height) {
            $layout[$index] = ['type' => 'fixed', 'fixed_mm' => $height];
        }

        $model = $models->get($name);
        expect($model->panel_layout)->toEqual($layout)
            ->and($model->partition_count)->toBe($count - 1)
            ->and($model->minimumPanelStackHeight())->toBe($model->min_height);
    }
});

test('layout seeding backfills missing layouts and preserves existing records and custom layouts', function () {
    $ids = DoorModel::query()->pluck('id', 'name')->all();
    $variantIds = DoorVariant::query()->orderBy('id')->pluck('id')->all();
    $missing = DoorModel::query()->where('name', 'დავლური')->sole();
    $expected = $missing->panel_layout;
    $missing->update(['panel_layout' => null]);
    $configured = DoorModel::query()->where('name', 'სიმდი')->sole();
    $customLayout = [['type' => 'flexible', 'min_mm' => 250, 'weight' => 1]];
    $configured->update(['panel_layout' => $customLayout]);

    $this->seed(DoorLayoutSeeder::class);

    expect(DoorModel::query()->pluck('id', 'name')->all())->toBe($ids);
    expect(DoorVariant::query()->orderBy('id')->pluck('id')->all())->toBe($variantIds);
    expect($missing->refresh()->panel_layout)->toEqual($expected)
        ->and($configured->refresh()->panel_layout)->toEqual($customLayout);
});
