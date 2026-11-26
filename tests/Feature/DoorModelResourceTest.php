<?php

use App\Enums\DoorModelStatus;
use App\Filament\Resources\DoorModels\Pages\CreateDoorModel;
use App\Filament\Resources\DoorModels\Pages\EditDoorModel;
use App\Filament\Resources\DoorModels\Pages\ListDoorModels;
use App\Models\DoorModel;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->actingAs(User::where('email', 'demo@demo.com')->first());
});

test('door models list page shows records', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(ListDoorModels::class)
        ->assertCanSeeTableRecords([$doorModel]);
});

test('a door model can be created', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm([
            'name' => 'ტესტი',
            'status' => DoorModelStatus::Passive,
            'min_height' => 800,
            'max_height' => 2600,
            'min_width' => 500,
            'max_width' => 1100,
            'not_recommended_height_min' => 2601,
            'not_recommended_height_max' => 2700,
            'panel_layout' => [['type' => 'flexible', 'min_mm' => 800, 'weight' => 1]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('door_models', ['name' => 'ტესტი', 'status' => DoorModelStatus::Passive]);
});

test('creating a door model requires a name', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm(['name' => ''])
        ->call('create')
        ->assertHasFormErrors(['name' => 'required']);
});

test('a door model can be edited', function () {
    $doorModel = DoorModel::factory()->create([
        'name' => 'ძველი',
        'panel_layout' => [['type' => 'flexible', 'min_mm' => 800, 'weight' => 1]],
    ]);

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->fillForm(['name' => 'ახალი'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($doorModel->refresh()->name)->toBe('ახალი');
});

test('a door model cannot be created with the name of another door model', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(CreateDoorModel::class)
        ->fillForm(['name' => $doorModel->name])
        ->call('create')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('a door model cannot be renamed to the name of another door model', function () {
    $doorModel = DoorModel::factory()->create();
    $otherDoorModel = DoorModel::factory()->create();

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->fillForm(['name' => $otherDoorModel->name])
        ->call('save')
        ->assertHasFormErrors(['name' => 'unique']);
});

test('a door model can be saved with its own name', function () {
    $doorModel = DoorModel::factory()->create(['panel_layout' => [['type' => 'flexible', 'min_mm' => 800, 'weight' => 1]]]);

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('sizing fields are disabled on the edit page', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->assertFormFieldDisabled('min_height')
        ->assertFormFieldDisabled('max_height')
        ->assertFormFieldDisabled('min_width')
        ->assertFormFieldDisabled('max_width')
        ->assertFormFieldDisabled('not_recommended_height_min')
        ->assertFormFieldDisabled('not_recommended_height_max')
        ->assertFormFieldEnabled('name');
});

test('door models cannot be deleted from the list page', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(ListDoorModels::class)
        ->assertActionDoesNotExist(TestAction::make('delete')->table($doorModel))
        ->assertActionDoesNotExist(TestAction::make('delete')->table()->bulk());
});

test('door models cannot be deleted from the edit page', function () {
    $doorModel = DoorModel::factory()->create();

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->assertActionDoesNotExist('delete');
});

test('the door model status can be changed', function () {
    $doorModel = DoorModel::factory()->create(['panel_layout' => [['type' => 'flexible', 'min_mm' => 800, 'weight' => 1]]]);

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->fillForm(['status' => DoorModelStatus::Passive])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($doorModel->refresh()->status)->toBe(DoorModelStatus::Passive);
});

test('seeded door models are active', function () {
    expect(DoorModel::query()->count())->toBeGreaterThan(0)
        ->and(DoorModel::query()->where('status', '!=', DoorModelStatus::Active)->exists())->toBeFalse();
});

test('the create form saves a panel layout with integer values and active fields only', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm([
            'name' => 'Panel layout test',
            'min_height' => 1200,
            'max_height' => 2600,
            'min_width' => 500,
            'max_width' => 1100,
            'panel_layout' => [
                ['type' => 'fixed', 'fixed_mm' => '400', 'min_mm' => '200', 'weight' => '1'],
                ['type' => 'flexible', 'fixed_mm' => '400', 'min_mm' => '800', 'weight' => '2'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $model = DoorModel::query()->where('name', 'Panel layout test')->sole();
    expect($model->panel_layout)->toEqual([
        ['type' => 'fixed', 'fixed_mm' => 400],
        ['type' => 'flexible', 'min_mm' => 800, 'weight' => 2],
    ])->and($model->minimumPanelStackHeight())->toBe(1200)
        ->and($model->partition_count)->toBe(1);
});

test('the edit form persists reordered panels', function () {
    $fixed = ['type' => 'fixed', 'fixed_mm' => 400];
    $flexible = ['type' => 'flexible', 'min_mm' => 800, 'weight' => 1];
    $model = DoorModel::factory()->create(['panel_layout' => [$fixed, $flexible]]);

    Livewire::test(EditDoorModel::class, ['record' => $model->getKey()])
        ->fillForm(['panel_layout' => [$flexible, $fixed]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($model->refresh()->panel_layout)->toEqual([$flexible, $fixed]);
});

test('the create and edit forms reject an empty panel layout', function () {
    Livewire::test(CreateDoorModel::class)
        ->fillForm([
            'name' => 'Empty layout',
            'min_height' => 800,
            'max_height' => 2600,
            'min_width' => 500,
            'max_width' => 1100,
            'panel_layout' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['panel_layout' => 'required']);

    $layout = [['type' => 'flexible', 'min_mm' => 800, 'weight' => 1]];
    $model = DoorModel::factory()->create(['panel_layout' => $layout]);

    Livewire::test(EditDoorModel::class, ['record' => $model->getKey()])
        ->fillForm(['panel_layout' => []])
        ->call('save')
        ->assertHasFormErrors(['panel_layout' => 'required']);

    expect($model->refresh()->panel_layout)->toEqual($layout);
});

test('the edit form rejects layouts with no flexible panels or an excessive stack height', function (array $layout, string $message) {
    $model = DoorModel::factory()->create();

    Livewire::test(EditDoorModel::class, ['record' => $model->getKey()])
        ->fillForm(['panel_layout' => $layout])
        ->call('save')
        ->assertHasFormErrors(['panel_layout' => __($message, ['maximum' => 2700])]);

    expect($model->refresh()->panel_layout)->toBeNull();
})->with([
    [[['type' => 'fixed', 'fixed_mm' => 400]], 'door_models.form.panel_requires_flexible'],
    [[['type' => 'fixed', 'fixed_mm' => 1000], ['type' => 'flexible', 'min_mm' => 1701, 'weight' => 1]], 'door_models.form.panel_exceeds_maximum'],
]);
