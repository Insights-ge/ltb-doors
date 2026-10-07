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
    $doorModel = DoorModel::factory()->create(['name' => 'ძველი']);

    Livewire::test(EditDoorModel::class, ['record' => $doorModel->getKey()])
        ->fillForm(['name' => 'ახალი'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($doorModel->refresh()->name)->toBe('ახალი');
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
    $doorModel = DoorModel::factory()->create();

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
