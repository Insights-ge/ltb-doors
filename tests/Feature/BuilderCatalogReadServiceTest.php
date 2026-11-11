<?php

use App\Data\BuilderCatalogData;
use App\Data\BuilderComponentCompatibilityData;
use App\Data\BuilderDoorVariantData;
use App\Enums\ComponentType;
use App\Enums\DoorColor;
use App\Enums\DoorModelStatus;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use App\Models\Infill;
use App\Services\BuilderCatalogReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('the catalog contains only active models and their variants', function () {
    $active = DoorModel::factory()->create(['name' => 'Active system']);
    $inactive = DoorModel::factory()->create(['status' => DoorModelStatus::Passive]);
    $variant = DoorVariant::factory()->for($active)->create(['color' => DoorColor::Black]);
    DoorVariant::factory()->for($inactive)->create();

    $catalog = app(BuilderCatalogReadService::class)->read();

    expect($catalog)->toBeInstanceOf(BuilderCatalogData::class);
    expect($catalog->models)->toHaveCount(1);
    $model = $catalog->models[0];
    expect($model->id)->toBe($active->id)
        ->and($model->minHeight)->toBe($active->min_height)
        ->and($model->maxWidth)->toBe($active->max_width)
        ->and($model->notRecommendedHeightMin)->toBe($active->not_recommended_height_min)
        ->and($model->variants)->toHaveCount(1)
        ->and($model->variants[0]->id)->toBe($variant->id)
        ->and($model->variants[0]->color)->toBe(DoorColor::Black)
        ->and($model->variants[0]->heightRangeMin)->toBe($variant->height_range_min)
        ->and($model->variants[0]->heightRangeMax)->toBe($variant->height_range_max)
        ->and($model->variants[0]->hasPartition)->toBeTrue()
        ->and($model->variants[0]->hasSoftCloseMechanism)->toBeTrue();
});

test('component exclusions are preserved without excluding infills from the catalog', function () {
    $variant = DoorVariant::factory()->create();
    $excluded = Infill::factory()->create();
    $allowed = Infill::factory()->create();
    $variant->sideProfile->incompatibleInfills()->attach($excluded);
    $variant->partition->incompatibleInfills()->attach($allowed);
    $variant->bottomRail->incompatibleInfills()->attach([$allowed->id, $excluded->id]);
    $variant->softCloseMechanism->incompatibleInfills()->attach($excluded);

    $catalog = app(BuilderCatalogReadService::class)->read();
    $components = collect($catalog->models[0]->variants[0]->components)->keyBy(fn (BuilderComponentCompatibilityData $component): string => $component->type->value);

    expect($components[ComponentType::SideProfile->value]->incompatibleInfillIds)->toBe([$excluded->id]);
    expect($components[ComponentType::Partition->value]->incompatibleInfillIds)->toBe([$allowed->id]);
    expect($components[ComponentType::TopRail->value]->incompatibleInfillIds)->toBe([]);
    expect($components[ComponentType::BottomRail->value]->incompatibleInfillIds)->toBe([$excluded->id, $allowed->id]);
    expect($components[ComponentType::SoftCloseMechanism->value]->incompatibleInfillIds)->toBe([$excluded->id]);
    expect($components[ComponentType::SoftCloseMechanism->value]->id)->toBe($variant->soft_close_mechanism_component_id);
    expect(array_column($catalog->infills, 'id'))->toEqualCanonicalizing([$excluded->id, $allowed->id]);
});

test('models without variants are omitted and variants without partitions remain readable', function () {
    $empty = DoorModel::factory()->create([
        'name' => 'A system', 'not_recommended_height_min' => null, 'not_recommended_height_max' => null,
    ]);
    $variant = DoorVariant::factory()->withoutPartition()->create();
    DoorVariant::factory()->for($empty)->create();
    $withoutVariants = DoorModel::factory()->create();

    $catalog = app(BuilderCatalogReadService::class)->read();

    $models = collect($catalog->models)->keyBy('id');
    expect($models->has($withoutVariants->id))->toBeFalse();
    expect($models[$empty->id]->notRecommendedHeightMin)->toBeNull();
    expect($models[$empty->id]->notRecommendedHeightMax)->toBeNull();
    expect(array_column($models[$variant->door_model_id]->variants[0]->components, 'type'))
        ->not->toContain(ComponentType::Partition);
    expect($models[$variant->door_model_id]->variants[0]->hasPartition)->toBeFalse();
    expect($models[$variant->door_model_id]->variants[0]->hasSoftCloseMechanism)->toBeTrue();
});

test('each variant retains its own height range within the same model', function () {
    $model = DoorModel::factory()->create(['min_height' => 800, 'max_height' => 2600]);
    $lower = DoorVariant::factory()->for($model)->create(['height_range_min' => 1600, 'height_range_max' => 2000]);
    $upper = DoorVariant::factory()->for($model)->create(['height_range_min' => 2001, 'height_range_max' => 2500]);

    $variants = collect(app(BuilderCatalogReadService::class)->read()->models[0]->variants)->keyBy('id');

    expect([$variants[$lower->id]->heightRangeMin, $variants[$lower->id]->heightRangeMax])->toBe([1600, 2000]);
    expect([$variants[$upper->id]->heightRangeMin, $variants[$upper->id]->heightRangeMax])->toBe([2001, 2500]);
});

test('variant availability is serialized from the components actually present', function (array $types) {
    $components = array_map(
        fn (ComponentType $type): BuilderComponentCompatibilityData => new BuilderComponentCompatibilityData(
            id: 1,
            type: $type,
            incompatibleInfillIds: [],
            code: 'TEST', description: 'Component', color: null,
        ),
        $types,
    );
    $variant = new BuilderDoorVariantData(
        id: 1,
        name: 'System',
        color: DoorColor::Black,
        frameDesignCode: 'Frame',
        heightRangeMin: 1600,
        heightRangeMax: 2000,
        components: $components,
    );

    $serialized = json_decode(json_encode($variant, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);

    expect($serialized['hasPartition'])->toBe(in_array(ComponentType::Partition, $types, true));
    expect($serialized['hasSoftCloseMechanism'])->toBe(in_array(ComponentType::SoftCloseMechanism, $types, true));
})->with([
    'neither component' => [[]],
    'partition only' => [[ComponentType::Partition]],
    'soft close only' => [[ComponentType::SoftCloseMechanism]],
    'both components' => [[ComponentType::Partition, ComponentType::SoftCloseMechanism]],
]);

test('an empty database returns an empty catalog', function () {
    $catalog = app(BuilderCatalogReadService::class)->read();

    expect($catalog->models)->toBe([])->and($catalog->infills)->toBe([]);
});

test('catalog reads use a fixed number of queries as the catalog grows', function () {
    DoorVariant::factory()->create();
    Infill::factory()->create();
    $service = app(BuilderCatalogReadService::class);

    DB::enableQueryLog();
    try {
        $service->read();
        $smallCatalogQueries = count(DB::getQueryLog());
        DB::disableQueryLog();
        DoorVariant::factory()->count(4)->create();
        Infill::factory()->count(4)->create();
        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->read();

        expect(count(DB::getQueryLog()))->toBe($smallCatalogQueries);
    } finally {
        DB::disableQueryLog();
        DB::flushQueryLog();
    }
});

test('catalog data does not serialize supplier identifiers or admin fields', function () {
    DoorVariant::factory()->create(['unique_code' => 'PRIVATE-VARIANT']);
    Component::query()->update(['description' => 'PRIVATE-COMPONENT']);
    Infill::factory()->create(['unique_code' => 'PRIVATE-INFILL']);

    $json = json_encode(app(BuilderCatalogReadService::class)->read(), JSON_THROW_ON_ERROR);

    expect($json)->not->toContain('PRIVATE-VARIANT', 'PRIVATE-COMPONENT', 'PRIVATE-INFILL', 'unique_code', 'created_at', 'updated_at', 'status');
});

test('the builder exposes component names codes and colours for the selected variant review', function () {
    $variant = DoorVariant::factory()->create();
    $variant->sideProfile->update(['code' => 'SIDE-1', 'description' => 'Side profile', 'color' => DoorColor::Black]);
    $result = app(BuilderCatalogReadService::class)->read()->models[0]->variants[0];
    $components = collect($result->components)->keyBy(fn (BuilderComponentCompatibilityData $component): string => $component->type->value);

    expect($components[ComponentType::SideProfile->value]->code)->toBe('SIDE-1');
    expect($components[ComponentType::SideProfile->value]->description)->toBe('Side profile');
    expect($components[ComponentType::SideProfile->value]->color)->toBe(DoorColor::Black);
    expect($result->components)->toHaveCount(5);
});
