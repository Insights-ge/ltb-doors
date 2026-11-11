<?php

namespace App\Filament\Pages;

use App\Data\BuilderBrowserPayloadData;
use App\Data\BuilderCatalogData;
use App\Enums\SlidingDoorBuilderStep;
use App\Filament\Forms\SlidingDoorBuilderForms;
use App\Services\BuilderCatalogReadService;
use App\Services\BuilderMetadataReadService;
use BackedEnum;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;

/**
 * @property-read BuilderCatalogData $builderCatalog
 * @property-read Schema $sizeForm
 * @property-read Schema $infillForm
 * @property-read Schema $systemForm
 */
class SlidingDoorBuilder extends Page
{
    use HasPageShield;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?int $navigationSort = 0;

    /** @var array<string, mixed> */
    public array $builderFields = [
        'openingWidth' => '', 'openingHeight' => '', 'modelId' => null,
        'frameDesignCode' => null, 'variantId' => null,
    ];

    public ?int $selectedInfillId = null;

    protected string $view = 'filament.admin.pages.sliding-door-builder';

    public static function getNavigationLabel(): string
    {
        return __('sliding_door_builder.navigation_label');
    }

    #[Computed]
    public function builderCatalog(): BuilderCatalogData
    {
        return app(BuilderCatalogReadService::class)->read();
    }

    public function sizeForm(Schema $schema): Schema
    {
        return SlidingDoorBuilderForms::size($schema, $this);
    }

    public function systemForm(Schema $schema): Schema
    {
        return SlidingDoorBuilderForms::system($schema, $this);
    }

    public function infillForm(Schema $schema): Schema
    {
        return SlidingDoorBuilderForms::infill($schema, $this);
    }

    public function validateBuilderSection(int $step): bool
    {
        if (! in_array($step, [1, 2], true)) {
            return false;
        }

        try {
            ($step === 1 ? $this->sizeForm : $this->systemForm)->getState();
        } catch (ValidationException $exception) {
            $this->setErrorBag($exception->validator->errors());

            return false;
        }

        if ($step === 1) {
            $width = $this->builderFields['openingWidth'];
            $height = $this->builderFields['openingHeight'];
            $width = is_string($width) ? str_replace(',', '.', $width) : $width;
            $height = is_string($height) ? str_replace(',', '.', $height) : $height;

            if (! is_numeric($width) || ! is_numeric($height) || (float) $height <= 0) {
                return false;
            }

            $width = (float) $width;
            $height = (float) $height;
            $ratio = $width / $height;

            if (! is_finite($ratio) || $ratio / 2 <= 0) {
                $this->addError('builderFields.openingWidth', __('sliding_door_builder.validation.geometry_invalid'));

                return false;
            }
        }

        return true;
    }

    /** @param array<string, mixed> $fields */
    public function restoreBuilderFields(array $fields): void
    {
        $validated = Validator::make($fields, [
            'openingWidth' => ['nullable', 'string', 'max:100'],
            'openingHeight' => ['nullable', 'string', 'max:100'],
            'modelId' => ['nullable', 'integer'],
            'frameDesignCode' => ['nullable', 'string', 'max:255'],
            'variantId' => ['nullable', 'integer'],
        ])->validate();
        foreach (['openingWidth', 'openingHeight', 'modelId', 'frameDesignCode', 'variantId'] as $field) {
            if (array_key_exists($field, $validated)) {
                $this->builderFields[$field] = $validated[$field];
            }
        }
        $this->resetValidation();
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
                catalog: $this->builderCatalog,
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
            'builder-finishes' => __('sliding_door_builder.finishes.title'),
        ];
        $messages = [];

        foreach ($labels as $field => $label) {
            $attribute = Str::lcfirst($label);
            $messages[$field] = [
                'required' => __('validation.required', ['attribute' => $attribute]),
                'numeric' => __('validation.numeric', ['attribute' => $attribute]),
                'gt' => __('validation.gt.numeric', ['attribute' => $attribute, 'value' => 0]),
                'min' => __('validation.min.numeric', ['attribute' => $attribute]),
                'max' => __('validation.max.numeric', ['attribute' => $attribute]),
                'exists' => __('validation.exists', ['attribute' => $attribute]),
                'in' => __('validation.in', ['attribute' => $attribute]),
            ];
        }

        return $messages;
    }
}
