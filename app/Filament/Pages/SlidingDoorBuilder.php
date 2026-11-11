<?php

namespace App\Filament\Pages;

use App\Data\BuilderBrowserPayloadData;
use App\Enums\SlidingDoorBuilderStep;
use App\Services\BuilderCatalogReadService;
use App\Services\BuilderMetadataReadService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Str;

class SlidingDoorBuilder extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.admin.pages.sliding-door-builder';

    public static function getNavigationLabel(): string
    {
        return __('sliding_door_builder.navigation_label');
    }

    public function getTitle(): string
    {
        return __('sliding_door_builder.title');
    }

    /**
     * @return array{steps: list<SlidingDoorBuilderStep>, initialStep: SlidingDoorBuilderStep, builderPayload: BuilderBrowserPayloadData, fieldValidationMessages: array<string, array<string, string>>}
     */
    protected function getViewData(): array
    {
        return [
            'steps' => SlidingDoorBuilderStep::cases(),
            'initialStep' => SlidingDoorBuilderStep::Size,
            'fieldValidationMessages' => $this->fieldValidationMessages(),
            'builderPayload' => new BuilderBrowserPayloadData(
                catalog: app(BuilderCatalogReadService::class)->read(),
                metadata: app(BuilderMetadataReadService::class)->read(),
            ),
        ];
    }

    /**
     * Use the same label casing and validation translations as Filament fields.
     *
     * @return array<string, array<string, string>>
     */
    private function fieldValidationMessages(): array
    {
        $labels = [
            'builder-openingWidth' => __('sliding_door_builder.size.width'),
            'builder-openingHeight' => __('sliding_door_builder.size.height'),
            'builder-model' => __('sliding_door_builder.system.model'),
            'builder-design' => __('sliding_door_builder.system.design'),
            'builder-variant' => __('sliding_door_builder.system.variant'),
            'builder-layouts' => __('sliding_door_builder.layouts.title'),
            'builder-finishes' => __('sliding_door_builder.finishes.title'),
        ];
        $messages = [];

        foreach ($labels as $field => $label) {
            $attribute = Str::lcfirst($label);
            $messages[$field] = [
                'required' => __('validation.required', ['attribute' => $attribute]),
                'numeric' => __('validation.numeric', ['attribute' => $attribute]),
                'gt' => __('validation.gt.numeric', ['attribute' => $attribute, 'value' => 0]),
                'exists' => __('validation.exists', ['attribute' => $attribute]),
                'in' => __('validation.in', ['attribute' => $attribute]),
            ];
        }

        return $messages;
    }
}
