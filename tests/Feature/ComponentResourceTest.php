<?php

use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Filament\Resources\Components\Pages\CreateComponent;
use App\Filament\Resources\Components\Pages\EditComponent;
use App\Filament\Resources\Components\Pages\ListComponents;
use App\Models\Component;
use App\Models\DoorVariant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed();

    $this->actingAs(User::where('email', 'demo@demo.com')->first());
});

test('components list page shows records', function () {
    $component = Component::factory()->create();

    Livewire::test(ListComponents::class)
        ->assertCanSeeTableRecords([$component]);
});

test('a component can be created', function () {
    Livewire::test(CreateComponent::class)
        ->fillForm([
            'type' => ComponentType::SideProfile->value,
            'code' => 'გალუ1234',
            'description' => 'ტესტის აღწერა',
            'color' => DoorColor::Black->value,
            'unique_code' => '000099999',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('components', ['unique_code' => '000099999']);
});

test('a component can be edited', function () {
    $component = Component::factory()->create();

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->fillForm(['code' => 'განახლებული'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($component->refresh()->code)->toBe('განახლებული');
});

test('a component not in use can be deleted', function () {
    $component = Component::factory()->create();

    Livewire::test(ListComponents::class)
        ->callAction(TestAction::make('delete')->table($component));

    $this->assertModelMissing($component);
});

test('a component in use cannot be deleted', function () {
    $variant = DoorVariant::factory()->create();
    $component = $variant->sideProfile;

    Livewire::test(ListComponents::class)
        ->callAction(TestAction::make('delete')->table($component));

    $this->assertModelExists($component);
});

test('components can be filtered by multiple types', function () {
    $filteredTypes = Livewire::test(ListComponents::class)
        ->filterTable('type', [ComponentType::SideProfile->value, ComponentType::TopRail->value])
        ->instance()
        ->getFilteredTableQuery()
        ->pluck('type')
        ->unique();

    expect($filteredTypes)->toHaveCount(2)
        ->and($filteredTypes->all())->toEqualCanonicalizing([ComponentType::SideProfile, ComponentType::TopRail]);
});
