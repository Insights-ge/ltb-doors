<?php

use App\Models\DoorModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('door model layouts retain panel order and determine the computed divider count', function () {
    $layout = [
        ['type' => 'fixed', 'fixed_mm' => 400],
        ['type' => 'flexible', 'min_mm' => 800, 'weight' => 1],
        ['type' => 'fixed', 'fixed_mm' => 400],
    ];

    expect(Schema::hasColumn('door_models', 'partition_count'))->toBeFalse();

    $model = DoorModel::factory()->create();

    $model->refresh();
    expect($model->panel_layout)->toBeNull()
        ->and($model->minimumPanelStackHeight())->toBeNull()
        ->and($model->partition_count)->toBe(1);

    $model->update(['panel_layout' => $layout]);

    $model->refresh();
    expect($model->panel_layout)->toEqual($layout)
        ->and($model->minimumPanelStackHeight())->toBe(1600)
        ->and($model->partition_count)->toBe(2);

    $model->update(['panel_layout' => [$layout[1]]]);

    expect($model->refresh()->partition_count)->toBe(0);
});

test('dropping the legacy divider column preserves only explicit layouts', function () {
    $migration = require database_path('migrations/2026_11_20_000001_drop_partition_count_from_door_models_table.php');
    $legacy = DoorModel::factory()->create();
    $layout = [['type' => 'flexible', 'min_mm' => 200, 'weight' => 1]];
    $configured = DoorModel::factory()->create(['panel_layout' => $layout]);

    $migration->down();
    DB::table('door_models')->where('id', $legacy->id)->update(['partition_count' => 4]);
    $migration->up();

    expect($legacy->refresh()->panel_layout)->toBeNull()
        ->and($legacy->partition_count)->toBe(1)
        ->and($configured->refresh()->panel_layout)->toEqual($layout)
        ->and(Schema::hasColumn('door_models', 'partition_count'))->toBeFalse();
});
