<?php

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Filament\Resources\DoorVariants\Pages\CreateDoorVariant;
use App\Filament\Resources\DoorVariants\Pages\EditDoorVariant;
use App\Filament\Resources\DoorVariants\Pages\ListDoorVariants;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->actingAs(User::where('email', 'demo@demo.com')->first());
});

test('door variants list page shows records', function () {
    $variant = DoorVariant::factory()->create();

    Livewire::test(ListDoorVariants::class)
        ->assertCanSeeTableRecords([$variant]);
});

test('a door variant can be created without a partition', function () {
    $doorModel = DoorModel::factory()->create();
    $sideProfile = Component::factory()->ofType(ComponentType::SideProfile)->create();
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();
    $bottomRail = Component::factory()->ofType(ComponentType::BottomRail)->create();
    $softCloseMechanism = Component::factory()->ofType(ComponentType::SoftCloseMechanism)->create();

    Livewire::test(CreateDoorVariant::class)
        ->fillForm([
            'door_model_id' => $doorModel->id,
            'color' => DoorColor::Black->value,
            'product_code' => 'პნლა999999',
            'frame_design_code' => 'გალუ9999',
            'unique_code' => '000088888',
            'product_full_name' => 'ტესტის სახელი',
            'height_range_min' => 1600,
            'height_range_max' => 2000,
            'side_profile_component_id' => $sideProfile->id,
            'top_rail_component_id' => $topRail->id,
            'bottom_rail_component_id' => $bottomRail->id,
            'soft_close_mechanism_component_id' => $softCloseMechanism->id,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('door_variants', [
        'unique_code' => '000088888',
        'partition_component_id' => null,
    ]);
});

test('creating a door variant requires the non-partition components', function () {
    Livewire::test(CreateDoorVariant::class)
        ->fillForm(['product_code' => 'პნლა999999'])
        ->call('create')
        ->assertHasFormErrors([
            'side_profile_component_id',
            'top_rail_component_id',
            'bottom_rail_component_id',
            'soft_close_mechanism_component_id',
        ]);
});

test('a door variant can be edited', function () {
    $variant = DoorVariant::factory()->create();

    Livewire::test(EditDoorVariant::class, ['record' => $variant->getKey()])
        ->fillForm(['product_code' => 'პნლა000001'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($variant->refresh()->product_code)->toBe('პნლა000001');
});
