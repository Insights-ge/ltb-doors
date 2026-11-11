<?php

use App\Enums\Role;
use App\Enums\SlidingDoorBuilderStep;
use App\Filament\Pages\SlidingDoorBuilder;
use App\Models\DoorVariant;
use App\Models\Infill;
use App\Models\User;
use Database\Seeders\AdminPermissionSeeder;
use Database\Seeders\GeneralSettingsSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\SuperAdminPermissionSeeder;
use Filament\Forms\Components\TextInput;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role as PermissionRole;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(GeneralSettingsSeeder::class);
});

test('guests are redirected to the admin login when opening the builder', function () {
    $url = SlidingDoorBuilder::getUrl(panel: 'admin');

    $this->get($url)
        ->assertRedirect(route('filament.admin.auth.login'))
        ->assertSessionHas('url.intended', $url);
});

test('guest JSON requests to the builder are unauthenticated', function () {
    $this->getJson(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertUnauthorized();
});

test('admins and super admins can open the builder using their session locale', function (Role $role, string $locale) {
    DoorVariant::factory()->create();
    Infill::factory()->create();
    PermissionRole::findOrCreate($role->value, 'web');
    $this->seed([RoleSeeder::class, AdminPermissionSeeder::class, SuperAdminPermissionSeeder::class]);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)
        ->withSession(['locale' => $locale])
        ->get(SlidingDoorBuilder::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSeeLivewire(SlidingDoorBuilder::class)
        ->assertSee(__('sliding_door_builder.title', [], $locale))
        ->assertSee(__('sliding_door_builder.steps.opening.label', [], $locale))
        ->assertSee(__('sliding_door_builder.preview.title', [], $locale))
        ->assertSee(__('sliding_door_builder.preview.drawing_label', [], $locale))
        ->assertSee(__('sliding_door_builder.preview.doors_label', [], $locale))
        ->assertSee(__('sliding_door_builder.preview.panels_label', [], $locale))
        ->assertSee(__('sliding_door_builder.summary.title', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.title', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.select_panel', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.apply_all', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.copy_door', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.replace_heading', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.replace_description', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.cancel', [], $locale))
        ->assertSee(__('sliding_door_builder.finishes.replace', [], $locale))
        ->assertDontSee('Demo in development')
        ->assertSee('lang="' . $locale . '"', false);

    foreach (SlidingDoorBuilderStep::cases() as $step) {
        $response->assertSee($step->getLabel());
        $response->assertSee($step->getDescription());
    }

    Livewire::test(SlidingDoorBuilder::class)
        ->assertSuccessful()
        ->assertViewIs('filament.admin.pages.sliding-door-builder');
    expect(SlidingDoorBuilder::canAccess())->toBeTrue();
    expect(SlidingDoorBuilder::shouldRegisterNavigation())->toBeTrue();
})->with([Role::Admin, Role::SuperAdmin])->with(['en', 'ka']);

test('users without builder permission cannot open the builder in either locale', function (?Role $role, string $locale) {
    $user = User::factory()->create();

    if ($role !== null) {
        PermissionRole::findOrCreate($role->value, 'web');
        $user->assignRole($role);
    }

    $this->actingAs($user)
        ->withSession(['locale' => $locale])
        ->get(SlidingDoorBuilder::getUrl(panel: 'admin'))
        ->assertForbidden();

    Livewire::test(SlidingDoorBuilder::class)->assertForbidden();
    expect(SlidingDoorBuilder::canAccess())->toBeFalse();
    expect(SlidingDoorBuilder::shouldRegisterNavigation())->toBeFalse();
})->with([null, Role::User])->with(['en', 'ka']);

test('admin roles without the builder permission cannot access the page', function (Role $role) {
    PermissionRole::findOrCreate($role->value, 'web');
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);

    $this->get(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertForbidden();
    Livewire::test(SlidingDoorBuilder::class)->assertForbidden();
    expect(SlidingDoorBuilder::shouldRegisterNavigation())->toBeFalse();
})->with([Role::Admin, Role::SuperAdmin]);

test('revoking builder permission blocks the next Livewire request', function () {
    $permission = Permission::findOrCreate('View:SlidingDoorBuilder', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    $component = Livewire::test(SlidingDoorBuilder::class)->assertSuccessful();

    $user->revokePermissionTo($permission);
    $user->unsetRelations();

    $component->call('$refresh')->assertForbidden();
    $this->get(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertForbidden();
    expect(SlidingDoorBuilder::shouldRegisterNavigation())->toBeFalse();
});

test('the builder permission grants page access without a specific role', function () {
    $permission = Permission::findOrCreate('View:SlidingDoorBuilder', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    $this->actingAs($user);

    $this->get(SlidingDoorBuilder::getUrl(panel: 'admin'))->assertOk();
    Livewire::test(SlidingDoorBuilder::class)->assertSuccessful();
    expect(SlidingDoorBuilder::shouldRegisterNavigation())->toBeTrue();
});

test('the permission seeders are repeatable and only grant builder access to admin roles', function () {
    foreach (Role::cases() as $role) {
        PermissionRole::findOrCreate($role->value, 'web');
    }

    $unrelatedPermission = Permission::findOrCreate('View:OtherPage', 'web');
    PermissionRole::findByName(Role::Admin->value, 'web')->givePermissionTo($unrelatedPermission);
    $this->seed([RoleSeeder::class, AdminPermissionSeeder::class, SuperAdminPermissionSeeder::class]);
    $this->seed([RoleSeeder::class, AdminPermissionSeeder::class, SuperAdminPermissionSeeder::class]);

    expect(Permission::query()->where('name', 'View:SlidingDoorBuilder')->where('guard_name', 'web')->count())->toBe(1);
    expect(PermissionRole::findByName(Role::Admin->value, 'web')->hasPermissionTo('View:SlidingDoorBuilder'))->toBeTrue();
    expect(PermissionRole::findByName(Role::SuperAdmin->value, 'web')->hasPermissionTo('View:SlidingDoorBuilder'))->toBeTrue();
    expect(PermissionRole::findByName(Role::User->value, 'web')->hasPermissionTo('View:SlidingDoorBuilder'))->toBeFalse();
    expect(PermissionRole::findByName(Role::Admin->value, 'web')->hasPermissionTo($unrelatedPermission))->toBeFalse();
});

test('the builder route has no locale prefix', function () {
    expect(SlidingDoorBuilder::getUrl(isAbsolute: false, panel: 'admin'))->toBe('/admin/sliding-door-builder');

    $this->get('/sliding-door-builder')->assertNotFound();
    $this->get('/en/sliding-door-builder')->assertNotFound();
    $this->get('/ka/sliding-door-builder')->assertNotFound();
});

test('empty catalogs render a localized empty state instead of configuration controls', function (bool $hasModel, string $locale) {
    if ($hasModel) {
        DoorVariant::factory()->create();
    }

    $permission = Permission::findOrCreate('View:SlidingDoorBuilder', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);

    $this->actingAs($user)->withSession(['locale' => $locale])
        ->get(SlidingDoorBuilder::getUrl(panel: 'admin'))
        ->assertOk()
        ->assertSee(__('sliding_door_builder.catalog.' . ($hasModel ? 'no_infills_heading' : 'no_models_heading'), [], $locale))
        ->assertDontSee('id="builder-model"', false)
        ->assertDontSee('data-export-design', false);
})->with([false, true])->with(['en', 'ka']);

test('builder field messages match the validation translations used by Filament', function (string $locale) {
    $permission = Permission::findOrCreate('View:SlidingDoorBuilder', 'web');
    $user = User::factory()->create();
    $user->givePermissionTo($permission);
    $this->actingAs($user)->withSession(['locale' => $locale]);
    app()->setLocale($locale);

    Livewire::test(SlidingDoorBuilder::class)
        ->assertViewHas('fieldValidationMessages', function (array $messages): bool {
            foreach (['openingWidth' => 'width', 'openingHeight' => 'height'] as $field => $label) {
                $attribute = TextInput::make($field)
                    ->label(__('sliding_door_builder.size.' . $label))
                    ->getValidationAttribute();

                foreach (['required' => '', 'numeric' => 'abc', 'gt' => -1] as $rule => $value) {
                    $expected = Validator::make(
                        ['measurement' => $value],
                        ['measurement' => 'bail|required|numeric|gt:0'],
                        [],
                        ['measurement' => $attribute],
                    )->errors()->first('measurement');

                    expect($messages['builder-' . $field][$rule])->toBe($expected);
                }
            }

            expect($messages['builder-model']['required'])->toBe(__('validation.required', [
                'attribute' => Str::lcfirst(__('sliding_door_builder.system.model')),
            ]));
            expect($messages['builder-finishes']['exists'])->toBe(__('validation.exists', [
                'attribute' => Str::lcfirst(__('sliding_door_builder.finishes.title')),
            ]));

            return true;
        });
})->with(['en', 'ka']);
