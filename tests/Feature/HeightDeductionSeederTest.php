<?php

use App\Enums\ComponentType;
use App\Models\Component;
use Database\Seeders\DoorCatalogSeeder;
use Database\Seeders\HeightDeductionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the height deduction seeder fills known side profiles and preserves configured deductions', function () {
    $this->seed(DoorCatalogSeeder::class);
    $profiles = Component::query()->where('type', ComponentType::SideProfile)->get();
    $configured = $profiles->firstWhere('code', 'გალუ4108');
    $configured->update(['height_deduction' => 37]);
    $otherType = Component::factory()->ofType(ComponentType::TopRail)->create(['code' => 'გალუ4108']);

    $this->seed(HeightDeductionSeeder::class);
    $this->seed(HeightDeductionSeeder::class);

    foreach ($profiles as $profile) {
        $expected = match (true) {
            $profile->is($configured) => 37,
            $profile->code === 'ალდ4722' => 30,
            default => 38,
        };

        expect($profile->refresh()->height_deduction)->toBe($expected);
    }

    expect($otherType->refresh()->height_deduction)->toBeNull();
});
