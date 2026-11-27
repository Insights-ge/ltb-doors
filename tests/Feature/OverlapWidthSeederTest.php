<?php

use App\Enums\ComponentType;
use App\Models\Component;
use Database\Seeders\DoorCatalogSeeder;
use Database\Seeders\OverlapWidthSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the overlap width seeder fills all catalog side profile colours and preserves configured widths', function () {
    $this->seed(DoorCatalogSeeder::class);
    $rail = Component::factory()->ofType(ComponentType::TopRail)->create(['code' => 'გალუ4108']);

    $this->seed(OverlapWidthSeeder::class);

    $profiles = Component::query()->where('type', ComponentType::SideProfile)->get();
    expect($profiles)->toHaveCount(18);

    foreach ($profiles as $profile) {
        $expected = match ($profile->code) {
            'გალუ4111', 'გალუ4171', 'გალუ4181' => 15,
            'გალუ4108', 'გალუ4312' => 20,
            'გალუ4355' => 27,
            'გალუ4323' => 40,
            'ალდ4722', 'გალუ4014' => 50,
            'გალუ4206' => 55,
        };

        expect($profile->overlap_width)->toBe($expected);
    }

    $configured = $profiles->firstWhere('code', 'გალუ4108');
    $configured->update(['overlap_width' => 25]);
    $this->seed(OverlapWidthSeeder::class);

    expect($configured->refresh()->overlap_width)->toBe(25)
        ->and($rail->refresh()->overlap_width)->toBeNull();
});
