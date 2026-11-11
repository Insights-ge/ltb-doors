<?php

use App\Enums\ComponentType;
use App\Models\Component;
use App\Models\DoorModel;
use App\Models\DoorVariant;
use Database\Seeders\DoorCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(DoorCatalogSeeder::class);
});

test('every variant component slot points to a component of that slot type', function () {
    $variants = DoorVariant::query()
        ->with(['sideProfile', 'topRail', 'bottomRail', 'partition', 'softCloseMechanism'])
        ->get();

    expect($variants)->not->toBeEmpty();

    $variants->each(function (DoorVariant $variant): void {
        expect($variant->sideProfile->type)->toBe(ComponentType::SideProfile)
            ->and($variant->topRail->type)->toBe(ComponentType::TopRail)
            ->and($variant->bottomRail->type)->toBe(ComponentType::BottomRail)
            ->and($variant->partition?->type ?? ComponentType::Partition)->toBe(ComponentType::Partition)
            ->and($variant->softCloseMechanism->type)->toBe(ComponentType::SoftCloseMechanism);
    });
});

test('a component used in slots of different types is stored once per type', function () {
    expect(Component::query()->where('unique_code', '000004465')->pluck('type')->all())
        ->toEqualCanonicalizing([ComponentType::SideProfile, ComponentType::TopRail]);
});

test('catalog seeding assigns one partition count per model without replacing existing records', function () {
    $ids = DoorModel::query()->pluck('id', 'name')->all();
    DoorModel::query()->update(['partition_count' => 0]);
    $variantIds = DoorVariant::query()->orderBy('id')->pluck('id')->all();
    $this->seed(DoorCatalogSeeder::class);

    expect(DoorModel::query()->pluck('id', 'name')->all())->toBe($ids);
    expect(DoorVariant::query()->orderBy('id')->pluck('id')->all())->toBe($variantIds);
    foreach (DoorModel::all() as $model) {
        expect($model->partition_count)->toBeInt()->toBeBetween(1, 4);
    }
});
