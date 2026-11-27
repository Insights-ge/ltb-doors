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
        ->searchTable($component->unique_code)
        ->assertCanSeeTableRecords([$component]);
});

test('a component can be created', function () {
    Livewire::test(CreateComponent::class)
        ->assertFormFieldHidden('height_deduction')
        ->fillForm([
            'type' => ComponentType::SideProfile->value,
            'code' => 'გალუ1234',
            'description' => 'ტესტის აღწერა',
            'color' => DoorColor::Black->value,
            'unique_code' => '000099999',
            'height_deduction' => 38,
        ])
        ->assertFormFieldVisible('height_deduction')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('components', ['unique_code' => '000099999', 'height_deduction' => 38]);
});

test('a component can reuse a unique code that exists under another type', function () {
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();

    Livewire::test(CreateComponent::class)
        ->fillForm([
            'type' => ComponentType::Partition->value,
            'code' => $topRail->code,
            'description' => $topRail->description,
            'unique_code' => $topRail->unique_code,
            'height_deduction' => 38,
        ])
        ->assertFormFieldHidden('height_deduction')
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('components', ['unique_code' => $topRail->unique_code, 'type' => ComponentType::Partition->value, 'height_deduction' => null]);
});

test('a component cannot reuse a unique code that exists under the same type', function () {
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();

    Livewire::test(CreateComponent::class)
        ->fillForm([
            'type' => ComponentType::TopRail->value,
            'code' => 'გალუ1234',
            'description' => 'ტესტის აღწერა',
            'unique_code' => $topRail->unique_code,
        ])
        ->call('create')
        ->assertHasFormErrors(['unique_code' => 'unique']);
});

test('a component can be edited', function () {
    $component = Component::factory()->create();

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->fillForm(['code' => 'განახლებული'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($component->refresh()->code)->toBe('განახლებული');
});

test('a side profile height deduction is visible and cannot be changed on edit', function () {
    $component = Component::factory()->ofType(ComponentType::SideProfile)->create(['height_deduction' => 38]);
    $rail = Component::factory()->ofType(ComponentType::TopRail)->create();

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->assertFormFieldVisible('height_deduction')
        ->assertFormFieldDisabled('height_deduction')
        ->assertFormSet(['height_deduction' => 38])
        ->fillForm(['height_deduction' => 30])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($component->refresh()->height_deduction)->toBe(38);

    Livewire::test(EditComponent::class, ['record' => $rail->getKey()])
        ->assertFormFieldHidden('height_deduction');
});

test('an edited component cannot take a unique code that exists under the same type', function () {
    $topRail = Component::factory()->ofType(ComponentType::TopRail)->create();
    $otherTopRail = Component::factory()->ofType(ComponentType::TopRail)->create();

    Livewire::test(EditComponent::class, ['record' => $otherTopRail->getKey()])
        ->fillForm(['unique_code' => $topRail->unique_code])
        ->call('save')
        ->assertHasFormErrors(['unique_code' => 'unique']);
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

test('a component not in use can be deleted from the edit page', function () {
    $component = Component::factory()->create();

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->callAction('delete')
        ->assertRedirect();

    $this->assertModelMissing($component);
});

test('a component in use cannot be deleted from the edit page', function () {
    $variant = DoorVariant::factory()->create();
    $component = $variant->sideProfile;

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->callAction('delete')
        ->assertNotified(__('components.table.delete_in_use_title'));

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

test('the type field is disabled on the edit page', function () {
    $component = Component::factory()->create();

    Livewire::test(EditComponent::class, ['record' => $component->getKey()])
        ->assertFormFieldDisabled('type')
        ->assertFormFieldEnabled('code');
});
