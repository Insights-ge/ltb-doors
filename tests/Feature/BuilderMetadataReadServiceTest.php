<?php

use App\Enums\DoorColor;
use App\Enums\InfillTexture;
use App\Models\Infill;
use App\Services\BuilderCatalogReadService;
use App\Services\BuilderMetadataReadService;
use Database\Seeders\InfillSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('every catalog frame colour has a visual and a translated label', function (string $locale) {
    app()->setLocale($locale);
    $metadata = app(BuilderMetadataReadService::class)->read();

    expect(array_column($metadata->frameColors, 'color'))->toBe(DoorColor::cases());
    foreach ($metadata->frameColors as $frame) {
        expect($frame->label)->toBe($frame->color->getLabel());
        expect($frame->visual->fill)->toMatch('/\A#[0-9A-F]{6}\z/');
    }
    expect($metadata->infillVisuals)->toBe([]);
})->with(['en', 'ka']);

test('missing and invalid frame mappings use the configured neutral visual', function () {
    config([
        'builder.neutral_fill' => '#abcdef',
        'builder.frame_colors' => ['black' => 'url(invalid)', 'silver' => '#123456'],
    ]);

    $metadata = app(BuilderMetadataReadService::class)->read();

    expect($metadata->neutralVisual->fill)->toBe('#ABCDEF');
    foreach ($metadata->frameColors as $frame) {
        expect($frame->visual->fill)->toBe($frame->color === DoorColor::Silver ? '#123456' : '#ABCDEF');
    }
});

test('an invalid neutral colour falls back to a valid grey', function () {
    config(['builder.neutral_fill' => null, 'builder.frame_colors' => []]);

    $metadata = app(BuilderMetadataReadService::class)->read();

    expect($metadata->neutralVisual->fill)->toBe('#B8B8B8');
    foreach ($metadata->frameColors as $frame) {
        expect($frame->visual->fill)->toBe('#B8B8B8');
    }
});

test('infill colours and textures come from the database with safe fallbacks', function () {
    $wood = Infill::factory()->create(['visual_color' => '#aabbcc', 'visual_texture' => InfillTexture::WoodStraight]);
    $invalid = Infill::factory()->create();
    DB::table('infills')->where('id', $invalid->id)->update(['visual_color' => 'invalid', 'visual_texture' => 'invalid']);
    $default = Infill::factory()->create();
    $catalogBefore = json_encode(app(BuilderCatalogReadService::class)->read(), JSON_THROW_ON_ERROR);
    $metadata = app(BuilderMetadataReadService::class)->read();
    $visuals = collect($metadata->infillVisuals)->keyBy('infillId');

    expect($visuals[$wood->id]->visual->fill)->toBe('#AABBCC');
    expect($visuals[$wood->id]->visual->texture)->toBe(InfillTexture::WoodStraight);
    expect($visuals[$invalid->id]->visual->fill)->toBe($metadata->neutralVisual->fill);
    expect($visuals[$invalid->id]->visual->texture)->toBe(InfillTexture::Solid);
    expect($visuals[$default->id]->visual->fill)->toBe('#B8B8B8');
    expect(json_encode(app(BuilderCatalogReadService::class)->read(), JSON_THROW_ON_ERROR))->toBe($catalogBefore);
    $wood->update(['visual_color' => '#123456', 'visual_texture' => InfillTexture::Mirror]);
    $updated = collect(app(BuilderMetadataReadService::class)->read()->infillVisuals)->keyBy('infillId');
    expect($updated[$wood->id]->visual->texture)->toBe(InfillTexture::Mirror);
    expect($updated[$wood->id]->visual->fill)->toBe('#123456');
});

test('catalog infill seeding stores named material visuals and preserves existing IDs', function () {
    $this->seed(InfillSeeder::class);
    $ids = Infill::query()->pluck('id', 'unique_code')->all();
    $this->seed(InfillSeeder::class);

    expect(Infill::query()->pluck('id', 'unique_code')->all())->toBe($ids);
    expect(Infill::query()->count())->toBe(20);
    foreach (Infill::all() as $infill) {
        expect($infill->visual_color)->toMatch('/\A#[0-9A-F]{6}\z/');
        expect($infill->visual_texture)->toBeInstanceOf(InfillTexture::class);
    }
    expect(Infill::where('unique_code', '000041471')->firstOrFail()->visual_texture)->toBe(InfillTexture::WoodStraight);
    expect(Infill::where('unique_code', '000041474')->firstOrFail()->visual_texture)->toBe(InfillTexture::WoodCathedral);
    expect(Infill::where('unique_code', '000042052')->firstOrFail()->visual_texture)->toBe(InfillTexture::Mirror);
    expect(Infill::where('unique_code', '000039871')->firstOrFail()->visual_texture)->toBe(InfillTexture::Glass);
});
