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
use Filament\Forms\Components\Select;
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
        ->searchTable($variant->unique_code)
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

test('a door variant cannot be created with the product code of another door variant', function () {
    $variant = DoorVariant::factory()->create();

    Livewire::test(CreateDoorVariant::class)
        ->fillForm(['product_code' => $variant->product_code])
        ->call('create')
        ->assertHasFormErrors(['product_code' => 'unique']);
});

test('a door variant cannot be edited to the product code of another door variant', function () {
    $variant = DoorVariant::factory()->create();
    $otherVariant = DoorVariant::factory()->create();

    Livewire::test(EditDoorVariant::class, ['record' => $variant->getKey()])
        ->fillForm(['product_code' => $otherVariant->product_code])
        ->call('save')
        ->assertHasFormErrors(['product_code' => 'unique']);
});

test('a door variant can be saved with its own product code', function () {
    $variant = DoorVariant::factory()->create();

    Livewire::test(EditDoorVariant::class, ['record' => $variant->getKey()])
        ->call('save')
        ->assertHasNoFormErrors();
});

test('component fields only offer components of their own type', function () {
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();
    $partition = Component::factory()->ofType(ComponentType::Partition)->create(['unique_code' => $topRail->unique_code]);
    $variant = DoorVariant::factory()->create(['partition_component_id' => $partition->id]);

    Livewire::test(EditDoorVariant::class, ['record' => $variant->getKey()])
        ->assertFormFieldExists(
            'partition_component_id',
            fn (Select $field): bool => array_key_exists($partition->id, $field->getOptions())
                && ! array_key_exists($topRail->id, $field->getOptions()),
        );
});

test('a door variant cannot be saved with a component of another type', function () {
    $variant = DoorVariant::factory()->create();
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();

    Livewire::test(EditDoorVariant::class, ['record' => $variant->getKey()])
        ->fillForm(['partition_component_id' => $topRail->id])
        ->call('save')
        ->assertHasFormErrors(['partition_component_id']);
});
