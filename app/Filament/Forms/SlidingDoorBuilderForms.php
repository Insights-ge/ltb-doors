<?php

namespace App\Filament\Forms;

use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;
use App\Data\BuilderInfillData;
use App\Enums\InfillTexture;
use App\Enums\OpeningMeasurement;
use App\Filament\Pages\SlidingDoorBuilder;
use App\Rules\OpeningMeasurementRule;
use App\Services\BuilderOpeningSizing;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\MessageBag;

class SlidingDoorBuilderForms
{
    private const OPENING_BOUNDS = [
        'width' => ['min' => 985, 'max' => 5460],
        'height' => ['min' => 238, 'max' => 2738],
    ];

    public static function size(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        $modelField = Select::make('modelId')
            ->searchable()
            ->id('builder-model')
            ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'modelId') ? 'true' : 'false'])
            ->label(__('sliding_door_builder.system.model'))
            ->placeholder(__('sliding_door_builder.system.choose'))
            ->disabled(fn (Get $get): bool => ! in_array(self::id($get('doorCount')), self::doorCounts($page, $get), true))
            ->options(fn (Get $get): array => collect($page->builderCatalog->models)->filter(fn (BuilderDoorModelData $model): bool => self::supportsSize($model, $get, self::id($get('doorCount'))))->mapWithKeys(fn (BuilderDoorModelData $model): array => [$model->id => $model->name])->all())
            ->helperText(function (Get $get) use ($page): ?string {
                $model = self::model($page, $get);
                if ($model === null) {
                    return null;
                }
                $summary = __('sliding_door_builder.size.partition_summary', ['count' => $model->partitionCount, 'infills' => $model->partitionCount + 1]);

                return is_string($summary) ? $summary : null;
            })
            ->required()
            ->live()
            ->afterStateUpdated(function (Set $set) use ($page): void {
                $set('frameDesignCode', null);
                $set('variantId', null);
                $page->resetValidation();
            });
        $fields = [];

        foreach (OpeningMeasurement::cases() as $measurement) {
            $name = $measurement->value;
            $fields[] = TextInput::make($name)
                ->id('builder-' . $name)
                ->label(__('sliding_door_builder.size.' . $measurement->labelKey()))
                ->inputMode('decimal')
                ->required()
                ->rules(['bail', 'numeric'])
                ->rule(fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                    if (is_numeric($value) && ! is_finite((float) $value)) {
                        $fail(__('validation.numeric', ['attribute' => $component->getValidationAttribute()]));
                    }
                })
                ->rule('gt:0')
                ->minValue(fn (Get $get): int => self::sizeBound($page, $get, $measurement, 'min'))
                ->maxValue(fn (Get $get): int => self::sizeBound($page, $get, $measurement, 'max'))
                ->helperText(fn (Get $get): string => self::sizeBound($page, $get, $measurement, 'min') . '–' . self::sizeBound($page, $get, $measurement, 'max') . ' ' . __('sliding_door_builder.size.unit'))
                ->mutateStateForValidationUsing(fn ($state) => is_string($state) ? str_replace(',', '.', trim($state)) : $state)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set) use ($page): void {
                    if (! in_array(self::id($get('doorCount')), self::doorCounts($page, $get), true)) {
                        $set('doorCount', null);
                    }
                    $model = self::model($page, $get);
                    if ($model !== null && ! self::supportsSize($model, $get, self::id($get('doorCount')))) {
                        $set('modelId', null);
                        $set('frameDesignCode', null);
                        $set('variantId', null);
                    }
                    if (self::variant($page, $get) === null) {
                        $set('frameDesignCode', null);
                        $set('variantId', null);
                    }
                    $page->resetValidation();
                })
                ->rule(fn (Get $get): OpeningMeasurementRule => new OpeningMeasurementRule(
                    $measurement,
                    self::model($page, $get),
                    self::id($get('doorCount')),
                    BuilderOpeningSizing::measurement($get('openingWidth')),
                    BuilderOpeningSizing::measurement($get('openingHeight')),
                ))
                ->extraInputAttributes(fn (): array => [
                    'x-on:blur' => "setMeasurement('{$name}', \$event.target.value)",
                    'aria-invalid' => self::hasError($page, $name) ? 'true' : 'false',
                ]);
        }

        $fields[] = Select::make('doorCount')
            ->id('builder-doorCount')
            ->label(__('sliding_door_builder.size.door_count'))
            ->placeholder(__('sliding_door_builder.system.choose'))
            ->searchable()
            ->options(fn (Get $get): array => array_combine(self::doorCounts($page, $get), self::doorCounts($page, $get)))
            ->disabled(fn (Get $get): bool => self::doorCounts($page, $get) === [])
            ->helperText(function (Get $get) use ($page): ?string {
                if (BuilderOpeningSizing::measurement($get('openingWidth')) === null || BuilderOpeningSizing::measurement($get('openingHeight')) === null || self::doorCounts($page, $get) !== []) {
                    return null;
                }
                $message = __('sliding_door_builder.size.no_door_counts');

                return is_string($message) ? $message : null;
            })
            ->required()
            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($page, $get): void {
                if (! in_array(self::id($value), self::doorCounts($page, $get), true)) {
                    $fail(__('validation.in', ['attribute' => __('sliding_door_builder.size.door_count')]));
                }
            })
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) use ($page): void {
                $model = self::model($page, $get);
                if ($model !== null && ! self::supportsSize($model, $get, self::id($get('doorCount')))) {
                    $set('modelId', null);
                    $set('frameDesignCode', null);
                    $set('variantId', null);
                }
                $page->resetValidation();
            });
        $fields[] = $modelField;

        return self::configure($schema)->components($fields);
    }

    public static function system(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        return self::configure($schema)->components([
            Select::make('frameDesignCode')
                ->searchable()
                ->id('builder-design')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'frameDesignCode') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.design'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(self::matchingVariants($page, $get))->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->frameDesignCode => $variant->frameDesignCode])->all())
                ->disabled(fn (Get $get): bool => self::model($page, $get) === null)
                ->required(fn (Get $get): bool => self::model($page, $get) !== null)
                ->live()
                ->afterStateUpdated(function (Set $set) use ($page): void {
                    $set('variantId', null);
                    $page->resetValidation();
                }),
            Select::make('variantId')
                ->searchable()
                ->id('builder-variant')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'variantId') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.variant'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(self::variants($page, $get))->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->id => $variant->color->getLabel() . ' — ' . $variant->name])->all())
                ->disabled(fn (Get $get): bool => ! filled($get('frameDesignCode')))
                ->required(fn (Get $get): bool => filled($get('frameDesignCode')))
                ->live()
                ->afterStateUpdated(fn () => $page->resetValidation()),
        ]);
    }

    public static function infill(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        $fields = [];
        foreach ($page->builderCatalog->models as $model) {
            if ($model->id !== self::id($page->builderFields['modelId'])) {
                continue;
            }
            for ($index = 0; $index <= $model->partitionCount; $index++) {
                $field = 'builder-infill-' . $index;
                $fields[] = self::infillSelect((string) $index, $page)
                    ->id($field)
                    ->label(__('sliding_door_builder.preview.infill_short_label', ['number' => $index + 1]))
                    ->placeholder(__('sliding_door_builder.finishes.unselected'))
                    ->belowContent(view('components.sliding-door-builder.field-error', ['field' => $field]))
                    ->extraInputAttributes([
                        'x-on:focusin' => 'selectInfillIndex(' . $index . ')',
                        'x-bind:aria-invalid' => "Boolean(fieldError('{$field}'))",
                        'aria-describedby' => $field . '-error',
                    ]);
            }
        }

        return $schema->statePath('infillSelections')->columns(1)->components($fields);
    }

    public static function quickInfill(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        return $schema->columns(1)->components([
            self::infillSelect('quickInfillId', $page)
                ->id('builder-quick-infill')
                ->label(__('infills.singular'))
                ->placeholder(__('sliding_door_builder.system.choose')),
        ]);
    }

    private static function infillSelect(string $name, SlidingDoorBuilder $page): Select
    {
        return Select::make($name)->searchable()->allowHtml()
            ->options(fn (): array => collect($page->builderCatalog->infills)->mapWithKeys(fn (BuilderInfillData $infill): array => [$infill->id => self::infillOptionLabel($page, $infill)])->all())
            ->disableOptionWhen(fn (int|string $value): bool => in_array(self::id($value), self::incompatibleInfills($page), true));
    }

    private static function infillOptionLabel(SlidingDoorBuilder $page, BuilderInfillData $infill): string
    {
        $visual = $page->builderMetadata->neutralVisual;
        foreach ($page->builderMetadata->infillVisuals as $mapping) {
            if ($mapping->infillId === $infill->id) {
                $visual = $mapping->visual;
                break;
            }
        }
        $background = match ($visual->texture) {
            InfillTexture::WoodStraight, InfillTexture::WoodCathedral => 'repeating-linear-gradient(90deg, transparent 0 3px, #0002 3px 4px), ' . $visual->fill,
            InfillTexture::Mirror => 'linear-gradient(135deg, #0003, #fff9, transparent, #fff6, #0003), ' . $visual->fill,
            InfillTexture::Glass => 'linear-gradient(135deg, transparent 35%, #fff9 49%, transparent 60%), ' . $visual->fill,
            default => $visual->fill,
        };

        return '<span style="display:flex;align-items:center;gap:0.5rem"><span aria-hidden="true" style="display:inline-block;width:1.5rem;height:1.5rem;flex-shrink:0;border:1px solid #888;border-radius:0.25rem;background:' . $background . '"></span><span>' . e($infill->code) . ' — ' . e($infill->description) . '</span></span>';
    }

    /** @return list<int> */
    private static function incompatibleInfills(SlidingDoorBuilder $page): array
    {
        foreach ($page->builderCatalog->models as $model) {
            if ($model->id !== self::id($page->builderFields['modelId'])) {
                continue;
            }
            foreach ($model->variants as $variant) {
                if ($variant->id === self::id($page->builderFields['variantId'])) {
                    $ids = [];
                    foreach ($variant->components as $component) {
                        $ids = [...$ids, ...$component->incompatibleInfillIds];
                    }

                    return array_values(array_unique($ids));
                }
            }
        }

        return array_map(fn (BuilderInfillData $infill): int => $infill->id, $page->builderCatalog->infills);
    }

    private static function configure(Schema $schema): Schema
    {
        return $schema->statePath('builderFields')->columns(1)->extraAttributes([
            // Re-query after rendering instead of retaining a detached error node.
            'x-on:form-validation-error.window' => "\$nextTick(() => \$el.querySelector('[data-validation-error]')?.closest('[data-field-wrapper]')?.scrollIntoView({ block: 'nearest' }))",
        ]);
    }

    private static function hasError(SlidingDoorBuilder $page, string $field): bool
    {
        $errors = $page->getErrorBag();

        return $errors instanceof MessageBag && $errors->has('builderFields.' . $field);
    }

    private static function id(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    private static function model(SlidingDoorBuilder $page, Get $get): ?BuilderDoorModelData
    {
        foreach ($page->builderCatalog->models as $model) {
            if ($model->id === self::id($get('modelId'))) {
                return $model;
            }
        }

        return null;
    }

    /** @return list<BuilderDoorVariantData> */
    private static function variants(SlidingDoorBuilder $page, Get $get): array
    {
        return array_values(array_filter(self::matchingVariants($page, $get), fn (BuilderDoorVariantData $variant): bool => $variant->frameDesignCode === $get('frameDesignCode')));
    }

    /** @return list<BuilderDoorVariantData> */
    private static function matchingVariants(SlidingDoorBuilder $page, Get $get): array
    {
        $model = self::model($page, $get);

        return $model === null ? [] : BuilderOpeningSizing::matchingVariants(
            $model,
            BuilderOpeningSizing::measurement($get('openingWidth')),
            BuilderOpeningSizing::measurement($get('openingHeight')),
            self::id($get('doorCount')),
        );
    }

    private static function sizeBound(SlidingDoorBuilder $page, Get $get, OpeningMeasurement $measurement, string $bound): int
    {
        $model = self::model($page, $get);

        $modelBound = match ([$measurement, $bound]) {
            [OpeningMeasurement::Height, 'min'] => $model === null ? null : $model->minimumHeight() + BuilderOpeningSizing::heightBoundDeduction($model, $bound),
            [OpeningMeasurement::Height, 'max'] => $model === null ? null : BuilderOpeningSizing::maximumHeight($model) + BuilderOpeningSizing::heightBoundDeduction($model, $bound),
            default => null,
        };
        $openingBound = self::OPENING_BOUNDS[$measurement->labelKey()][$bound];

        return $bound === 'min' ? max($openingBound, $modelBound ?? $openingBound) : min($openingBound, $modelBound ?? $openingBound);
    }

    private static function supportsSize(BuilderDoorModelData $model, Get $get, ?int $count): bool
    {
        $width = BuilderOpeningSizing::measurement($get('openingWidth'));
        $openingHeight = BuilderOpeningSizing::measurement($get('openingHeight'));

        return $width !== null && $openingHeight !== null && in_array($count, [2, 3, 4, 5], true)
            && $width >= self::OPENING_BOUNDS['width']['min'] && $width <= self::OPENING_BOUNDS['width']['max']
            && $openingHeight >= self::OPENING_BOUNDS['height']['min'] && $openingHeight <= self::OPENING_BOUNDS['height']['max']
            && BuilderOpeningSizing::matchingVariants($model, $width, $openingHeight, $count) !== [];
    }

    /** @return list<int> */
    private static function doorCounts(SlidingDoorBuilder $page, Get $get): array
    {
        return array_values(array_filter([2, 3, 4, 5], fn (int $count): bool => array_any($page->builderCatalog->models, fn (BuilderDoorModelData $model): bool => self::supportsSize($model, $get, $count))));
    }

    private static function variant(SlidingDoorBuilder $page, Get $get): ?BuilderDoorVariantData
    {
        foreach (self::variants($page, $get) as $variant) {
            if ($variant->id === self::id($get('variantId'))) {
                return $variant;
            }
        }

        return null;
    }
}
