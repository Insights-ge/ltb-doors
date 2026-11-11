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
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Select;
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
        ->assertSee(__('sliding_door_builder.preview.infills_label', [], $locale))
        ->assertDontSee('builder-summary', false)
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
            expect($messages['builder-infill-0']['exists'])->toBe(__('validation.exists', [
                'attribute' => Str::lcfirst(__('sliding_door_builder.preview.infill_short_label', ['number' => 1])),
            ]));

            return true;
        });
})->with(['en', 'ka']);

test('builder size and system schemas use native Filament fields', function () {
    DoorVariant::factory()->create();
    Infill::factory()->create();
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);

    $component = Livewire::test(SlidingDoorBuilder::class);
    foreach (['openingWidth', 'openingHeight'] as $field) {
        $component->assertFormFieldExists($field, 'sizeForm', fn (Field $component): bool => $component instanceof TextInput);
    }
    $component->assertFormFieldExists('modelId', 'sizeForm', fn (Field $component): bool => $component instanceof Select);
    foreach (['frameDesignCode', 'variantId'] as $field) {
        $component->assertFormFieldExists($field, 'systemForm', fn (Field $component): bool => $component instanceof Select);
    }
    $component->assertFormFieldDoesNotExist('softClose', 'systemForm');
});

test('Filament validates builder measurements with the expected inline messages', function (string $value, string $rule, string $locale) {
    $variant = DoorVariant::factory()->create(['height_range_max' => 2600]);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);
    app()->setLocale($locale);

    $component = Livewire::test(SlidingDoorBuilder::class)
        ->set('builderFields.modelId', $variant->door_model_id)
        ->set('builderFields.openingWidth', $value)
        ->set('builderFields.openingHeight', '2400')
        ->call('validateBuilderSection', 1)
        ->assertReturned(false)
        ->assertHasErrors('builderFields.openingWidth');

    $message = __($rule === 'gt' ? 'validation.gt.numeric' : 'validation.' . $rule, [
        'attribute' => Str::lcfirst(__('sliding_door_builder.size.width')),
        'value' => 0,
    ]);
    expect($component->errors()->first('builderFields.openingWidth'))->toBe($message);
})->with([['', 'required'], ['abc', 'numeric'], ['0', 'gt'], ['-12', 'gt'], ['1e309', 'numeric']])->with(['en', 'ka']);

test('restoring and clearing Filament field state preserves raw measurements and validates product choices', function () {
    $variant = DoorVariant::factory()->create(['height_range_max' => 2600]);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);

    Livewire::test(SlidingDoorBuilder::class)
        ->call('restoreBuilderFields', [
            'openingWidth' => '1600,5', 'openingHeight' => '2400',
            'modelId' => $variant->door_model_id, 'frameDesignCode' => $variant->frame_design_code,
            'variantId' => $variant->id, 'softClose' => false,
        ])
        ->assertSet('builderFields.openingWidth', '1600,5')
        ->call('validateBuilderSection', 1)->assertReturned(true)
        ->call('validateBuilderSection', 2)->assertReturned(true)
        ->assertHasNoErrors()
        ->set('builderFields.variantId', 999999)
        ->call('validateBuilderSection', 2)->assertReturned(false)
        ->assertHasErrors('builderFields.variantId')
        ->call('restoreBuilderFields', [
            'openingWidth' => '', 'openingHeight' => '', 'modelId' => null,
            'frameDesignCode' => null, 'variantId' => null, 'softClose' => false,
        ])
        ->assertHasNoErrors()
        ->assertSet('builderFields.variantId', null);
});

test('model selection enables sizes and enforces both inclusive model ranges', function () {
    $variant = DoorVariant::factory()->create(['height_range_max' => 2600]);
    $variant->doorModel->update(['min_width' => 600, 'max_width' => 1000, 'min_height' => 1600, 'max_height' => 2500, 'partition_count' => 4]);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);

    $component = Livewire::test(SlidingDoorBuilder::class)
        ->assertFormFieldIsDisabled('openingWidth', 'sizeForm')
        ->assertFormFieldIsDisabled('openingHeight', 'sizeForm')
        ->call('validateBuilderSection', 1)->assertReturned(false)
        ->assertHasErrors('builderFields.modelId')
        ->set('builderFields.modelId', $variant->door_model_id)
        ->assertFormFieldIsEnabled('openingWidth', 'sizeForm')
        ->assertFormFieldIsEnabled('openingHeight', 'sizeForm');

    foreach ([[1200, 1600], [2000, 2500]] as [$width,$height]) {
        $component->set('builderFields.openingWidth', (string) $width)->set('builderFields.openingHeight', (string) $height)
            ->call('validateBuilderSection', 1)->assertReturned(true)->assertHasNoErrors();
    }
    foreach ([['openingWidth', 1199], ['openingWidth', 2001], ['openingHeight', 1599], ['openingHeight', 2501]] as [$field,$value]) {
        $component->set('builderFields.openingWidth', '1600')->set('builderFields.openingHeight', '2400')
            ->set('builderFields.' . $field, (string) $value)
            ->call('validateBuilderSection', 1)->assertReturned(false)->assertHasErrors('builderFields.' . $field);
    }
});

test('design and variant choices resolve only existing variants for the selected model and height', function () {
    $short = DoorVariant::factory()->create(['frame_design_code' => 'short', 'height_range_min' => 1600, 'height_range_max' => 2000]);
    $tall = DoorVariant::factory()->create(['door_model_id' => $short->door_model_id, 'frame_design_code' => 'tall', 'height_range_min' => 2200, 'height_range_max' => 2600]);
    $foreign = DoorVariant::factory()->create(['frame_design_code' => 'tall', 'height_range_min' => 2200, 'height_range_max' => 2600]);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);

    $component = Livewire::test(SlidingDoorBuilder::class)
        ->set('builderFields.modelId', $short->door_model_id)
        ->set('builderFields.openingWidth', '1600')
        ->set('builderFields.openingHeight', '2400')
        ->assertFormFieldExists('frameDesignCode', 'systemForm', fn (Select $field): bool => $field->getOptions() === ['tall' => 'tall'])
        ->set('builderFields.frameDesignCode', 'tall')
        ->assertFormFieldExists('variantId', 'systemForm', fn (Select $field): bool => array_keys($field->getOptions()) === [$tall->id])
        ->set('builderFields.variantId', $tall->id)
        ->call('validateBuilderSection', 2)->assertReturned(true)
        ->set('builderFields.variantId', $foreign->id)
        ->call('validateBuilderSection', 2)->assertReturned(false)->assertHasErrors('builderFields.variantId')
        ->set('builderFields.variantId', $short->id)
        ->call('validateBuilderSection', 2)->assertReturned(false)->assertHasErrors('builderFields.variantId');

    $component->set('builderFields.openingHeight', '2100')
        ->call('validateBuilderSection', 1)->assertReturned(false)->assertHasErrors('builderFields.openingHeight');
    foreach ([2200, 2600] as $height) {
        $component->set('builderFields.openingHeight', (string) $height)
            ->call('validateBuilderSection', 1)->assertReturned(true)->assertHasNoErrors();
    }
});

test('the infill control is a searchable Filament select with variant incompatibilities disabled', function () {
    $variant = DoorVariant::factory()->create();
    $allowed = Infill::factory()->create();
    $excluded = Infill::factory()->create();
    $variant->sideProfile->incompatibleInfills()->attach($excluded);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);

    Livewire::test(SlidingDoorBuilder::class)
        ->set('builderFields.modelId', $variant->door_model_id)
        ->set('builderFields.variantId', $variant->id)
        ->assertFormFieldExists('0', 'infillForm', function (Select $field) use ($allowed, $excluded): bool {
            $options = $field->getOptions();

            return $field->isSearchable()
                && isset($options[$allowed->id], $options[$excluded->id])
                && ! $field->isOptionDisabled($allowed->id, $options[$allowed->id])
                && $field->isOptionDisabled($excluded->id, $options[$excluded->id]);
        });
});

test('all model infills have visible numbered searchable selects with escaped material swatches', function () {
    $variant = DoorVariant::factory()->create();
    $variant->doorModel->update(['partition_count' => 4]);
    $infill = Infill::factory()->create(['description' => '<img src=x onerror=alert(1)>', 'visual_color' => '#AABBCC']);
    $user = User::factory()->create();
    $user->givePermissionTo(Permission::findOrCreate('View:SlidingDoorBuilder', 'web'));
    $this->actingAs($user);
    $component = Livewire::test(SlidingDoorBuilder::class)
        ->set('builderFields.modelId', $variant->door_model_id)
        ->set('builderFields.variantId', $variant->id);
    for ($index = 0; $index < 5; $index++) {
        $component->assertFormFieldExists((string) $index, 'infillForm', function (Select $field) use ($index, $infill): bool {
            $label = $field->getOptions()[$infill->id];

            return $field->isSearchable()
                && $field->getLabel() === __('sliding_door_builder.preview.infill_short_label', ['number' => $index + 1])
                && str_contains($label, 'background:#AABBCC')
                && str_contains($label, '&lt;img') && ! str_contains($label, '<img');
        });
    }
});
