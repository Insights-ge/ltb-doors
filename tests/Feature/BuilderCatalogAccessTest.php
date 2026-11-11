<?php

use App\Data\BuilderBrowserPayloadData;
use App\Data\BuilderComponentCompatibilityData;
use App\Enums\ComponentType;
use App\Enums\DoorModelStatus;
use App\Enums\Role;
use App\Filament\Pages\SlidingDoorBuilder;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Models\Infill;
use App\Models\User;
use App\Services\BuilderCatalogReadService;
use Database\Seeders\AdminPermissionSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([GeneralSettingsSeeder::class, RoleSeeder::class, AdminPermissionSeeder::class, SuperAdminPermissionSeeder::class]);
    $this->user = User::factory()->create();
    $this->user->assignRole(Role::Admin);
    $this->actingAs($this->user);
});

test('authorized roles receive only the builder field allowlist', function (Role $role) {
    $this->user->syncRoles($role);
    DoorVariant::factory()->create(['product_code' => 'INTERNAL-PRODUCT', 'unique_code' => 'PRIVATE-VARIANT']);
    Component::query()->update(['description' => 'PRIVATE-COMPONENT-DESCRIPTION']);
    Infill::factory()->create(['unique_code' => 'PRIVATE-INFILL']);

    $component = Livewire::test(SlidingDoorBuilder::class)->assertSuccessful();
    $payload = $component->viewData('builderPayload');
    expect($payload)->toBeInstanceOf(BuilderBrowserPayloadData::class);
    $json = json_encode($payload, JSON_THROW_ON_ERROR);
    $catalog = json_decode($json, true, flags: JSON_THROW_ON_ERROR)['catalog'];

    expect(array_keys($catalog['models'][0]))->toEqualCanonicalizing([
        'id', 'name', 'minHeight', 'maxHeight', 'minWidth', 'maxWidth', 'partitionCount',
        'notRecommendedHeightMin', 'notRecommendedHeightMax', 'variants',
    ]);
    expect(array_keys($catalog['models'][0]['variants'][0]))->toEqualCanonicalizing([
        'id', 'name', 'color', 'frameDesignCode', 'heightRangeMin', 'heightRangeMax',
        'hasPartition', 'hasSoftCloseMechanism', 'components',
    ]);
    foreach ($catalog['models'][0]['variants'][0]['components'] as $compatibility) {
        expect(array_keys($compatibility))->toEqualCanonicalizing(['id', 'type', 'incompatibleInfillIds']);
    }
    expect(array_keys($catalog['infills'][0]))->toEqualCanonicalizing(['id', 'code', 'description']);
    expect($json)->not->toContain('INTERNAL-PRODUCT', 'PRIVATE-VARIANT', 'PRIVATE-INFILL', 'PRIVATE-COMPONENT-DESCRIPTION');
    expect($json)->not->toContain('created_at', 'updated_at', 'unique_code', 'status', $this->user->email);
})->with([Role::Admin, Role::SuperAdmin]);

test('inactive systems and their variants never reach an authorized browser', function () {
    $inactive = DoorModel::factory()->create(['name' => 'PASSIVE-SYSTEM-SENTINEL', 'status' => DoorModelStatus::Passive]);
    DoorVariant::factory()->for($inactive)->create(['product_full_name' => 'PASSIVE-VARIANT-SENTINEL']);
    $active = DoorVariant::factory()->create();
    Infill::factory()->create();

    $payload = Livewire::test(SlidingDoorBuilder::class)->assertSuccessful()->viewData('builderPayload');

    expect(array_column($payload->catalog->models, 'id'))->toBe([$active->door_model_id]);
    expect(json_encode($payload, JSON_THROW_ON_ERROR))->not->toContain('PASSIVE-SYSTEM-SENTINEL', 'PASSIVE-VARIANT-SENTINEL');
    $this->get(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertOk()
        ->assertDontSee('PASSIVE-SYSTEM-SENTINEL', false)->assertDontSee('PASSIVE-VARIANT-SENTINEL', false);
});

test('incomplete data is omitted while usable siblings remain dense JSON lists', function () {
    $unusable = DoorModel::factory()->create(['name' => ' ']);
    DoorVariant::factory()->for($unusable)->create();
    $usable = DoorModel::factory()->create();
    DoorVariant::factory()->for($usable)->create(['product_full_name' => ' ']);
    $wrongComponent = DoorVariant::factory()->for($usable)->create();
    $wrongComponent->update(['side_profile_component_id' => $wrongComponent->top_rail_component_id]);
    $valid = DoorVariant::factory()->for($usable)->withoutPartition()->create();
    Infill::factory()->create(['description' => ' ']);
    $infill = Infill::factory()->create();

    $payload = Livewire::test(SlidingDoorBuilder::class)->assertSuccessful()->viewData('builderPayload');
    $catalog = json_decode(json_encode($payload, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR)['catalog'];

    expect(array_is_list($catalog['models']))->toBeTrue();
    expect(array_column($catalog['models'], 'id'))->toBe([$usable->id]);
    expect(array_is_list($catalog['models'][0]['variants']))->toBeTrue();
    expect(array_column($catalog['models'][0]['variants'], 'id'))->toBe([$valid->id]);
    expect($catalog['models'][0]['variants'][0]['hasPartition'])->toBeFalse();
    expect(array_is_list($catalog['infills']))->toBeTrue();
    expect(array_column($catalog['infills'], 'id'))->toBe([$infill->id]);
});

test('shared component exclusions remain component specific across variants', function () {
    $first = DoorVariant::factory()->create();
    $shared = DoorVariant::factory()->withoutPartition()->create(['side_profile_component_id' => $first->side_profile_component_id]);
    $unrelated = DoorVariant::factory()->create();
    $excluded = Infill::factory()->create();
    $partitionOnly = Infill::factory()->create();
    $first->sideProfile->incompatibleInfills()->attach($excluded);
    $first->partition->incompatibleInfills()->attach($partitionOnly);

    $payload = Livewire::test(SlidingDoorBuilder::class)->assertSuccessful()->viewData('builderPayload');
    $variants = collect($payload->catalog->models)->flatMap(fn ($model) => $model->variants)->keyBy('id');
    $components = collect($variants[$first->id]->components)->keyBy(fn (BuilderComponentCompatibilityData $component): string => $component->type->value);
    $sharedComponents = collect($variants[$shared->id]->components)->keyBy(fn (BuilderComponentCompatibilityData $component): string => $component->type->value);

    expect($components[ComponentType::SideProfile->value]->incompatibleInfillIds)->toBe([$excluded->id]);
    expect($sharedComponents[ComponentType::SideProfile->value]->incompatibleInfillIds)->toBe([$excluded->id]);
    expect($components[ComponentType::Partition->value]->incompatibleInfillIds)->toBe([$partitionOnly->id]);
    expect($sharedComponents->has(ComponentType::Partition->value))->toBeFalse();
    foreach ($variants[$unrelated->id]->components as $component) {
        expect($component->incompatibleInfillIds)->toBe([]);
    }
    expect(array_column($payload->catalog->infills, 'id'))->toEqualCanonicalizing([$excluded->id, $partitionOnly->id]);
});

test('catalog resource permissions do not grant builder access or load its data', function () {
    $this->user->syncRoles(Role::User);
    $this->user->givePermissionTo(['ViewAny:DoorModel', 'ViewAny:Infill']);
    $service = Mockery::mock(BuilderCatalogReadService::class);
    $service->shouldNotReceive('read');
    app()->instance(BuilderCatalogReadService::class, $service);

    $this->get(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertForbidden();
    Livewire::test(SlidingDoorBuilder::class)->assertForbidden();
});
