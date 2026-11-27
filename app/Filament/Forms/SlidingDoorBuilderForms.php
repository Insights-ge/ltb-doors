<?php

namespace App\Filament\Forms;

use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;
use App\Data\BuilderInfillData;
use App\Enums\OpeningMeasurement;
use App\Filament\Pages\SlidingDoorBuilder;
use App\Rules\OpeningMeasurementRule;
use App\Services\BuilderOpeningSizing;
use App\Services\SlidingDoorBuilderFormService;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class SlidingDoorBuilderForms
{
    public static function size(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        $modelField = Select::make('modelId')
            ->searchable()
            ->id('builder-model')
            ->extraInputAttributes(fn (): array => ['aria-invalid' => SlidingDoorBuilderFormService::hasError($page, 'modelId') ? 'true' : 'false'])
            ->label(__('sliding_door_builder.system.model'))
            ->placeholder(__('sliding_door_builder.system.choose'))
            ->disabled(fn (Get $get): bool => ! in_array(SlidingDoorBuilderFormService::id($get('doorCount')), SlidingDoorBuilderFormService::doorCounts($page, $get), true))
            ->options(fn (Get $get): array => collect($page->builderCatalog->models)->filter(fn (BuilderDoorModelData $model): bool => SlidingDoorBuilderFormService::supportsSize($model, $get, SlidingDoorBuilderFormService::id($get('doorCount'))))->mapWithKeys(fn (BuilderDoorModelData $model): array => [$model->id => $model->name])->all())
            ->helperText(function (Get $get) use ($page): ?string {
                $model = SlidingDoorBuilderFormService::model($page, $get);
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
                ->minValue(fn (Get $get): int => SlidingDoorBuilderFormService::sizeBound($page, $get, $measurement, 'min'))
                ->maxValue(fn (Get $get): int => SlidingDoorBuilderFormService::sizeBound($page, $get, $measurement, 'max'))
                ->helperText(fn (Get $get): string => SlidingDoorBuilderFormService::sizeBound($page, $get, $measurement, 'min') . '–' . SlidingDoorBuilderFormService::sizeBound($page, $get, $measurement, 'max') . ' ' . __('sliding_door_builder.size.unit'))
                ->mutateStateForValidationUsing(fn ($state) => is_string($state) ? str_replace(',', '.', trim($state)) : $state)
                ->live(onBlur: true)
                ->afterStateUpdated(function (Get $get, Set $set) use ($page): void {
                    if (! in_array(SlidingDoorBuilderFormService::id($get('doorCount')), SlidingDoorBuilderFormService::doorCounts($page, $get), true)) {
                        $set('doorCount', null);
                    }
                    $model = SlidingDoorBuilderFormService::model($page, $get);
                    if ($model !== null && ! SlidingDoorBuilderFormService::supportsSize($model, $get, SlidingDoorBuilderFormService::id($get('doorCount')))) {
                        $set('modelId', null);
                        $set('frameDesignCode', null);
                        $set('variantId', null);
                    }
                    if (SlidingDoorBuilderFormService::variant($page, $get) === null) {
                        $set('frameDesignCode', null);
                        $set('variantId', null);
                    }
                    $page->resetValidation();
                })
                ->rule(fn (Get $get): OpeningMeasurementRule => new OpeningMeasurementRule(
                    $measurement,
                    SlidingDoorBuilderFormService::model($page, $get),
                    SlidingDoorBuilderFormService::id($get('doorCount')),
                    BuilderOpeningSizing::measurement($get('openingWidth')),
                    BuilderOpeningSizing::measurement($get('openingHeight')),
                ))
                ->extraInputAttributes(fn (): array => [
                    'x-on:blur' => "setMeasurement('{$name}', \$event.target.value)",
                    'aria-invalid' => SlidingDoorBuilderFormService::hasError($page, $name) ? 'true' : 'false',
                ]);
        }

        $fields[] = Select::make('doorCount')
            ->id('builder-doorCount')
            ->label(__('sliding_door_builder.size.door_count'))
            ->placeholder(__('sliding_door_builder.system.choose'))
            ->searchable()
            ->options(fn (Get $get): array => array_combine(SlidingDoorBuilderFormService::doorCounts($page, $get), SlidingDoorBuilderFormService::doorCounts($page, $get)))
            ->disabled(fn (Get $get): bool => SlidingDoorBuilderFormService::doorCounts($page, $get) === [])
            ->helperText(function (Get $get) use ($page): ?string {
                if (BuilderOpeningSizing::measurement($get('openingWidth')) === null || BuilderOpeningSizing::measurement($get('openingHeight')) === null || SlidingDoorBuilderFormService::doorCounts($page, $get) !== []) {
                    return null;
                }
                $message = __('sliding_door_builder.size.no_door_counts');

                return is_string($message) ? $message : null;
            })
            ->required()
            ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($page, $get): void {
                if (! in_array(SlidingDoorBuilderFormService::id($value), SlidingDoorBuilderFormService::doorCounts($page, $get), true)) {
                    $fail(__('validation.in', ['attribute' => __('sliding_door_builder.size.door_count')]));
                }
            })
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) use ($page): void {
                $model = SlidingDoorBuilderFormService::model($page, $get);
                if ($model !== null && ! SlidingDoorBuilderFormService::supportsSize($model, $get, SlidingDoorBuilderFormService::id($get('doorCount')))) {
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
                ->extraInputAttributes(fn (): array => ['aria-invalid' => SlidingDoorBuilderFormService::hasError($page, 'frameDesignCode') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.design'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(SlidingDoorBuilderFormService::matchingVariants($page, $get))->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->frameDesignCode => $variant->frameDesignCode])->all())
                ->disabled(fn (Get $get): bool => SlidingDoorBuilderFormService::model($page, $get) === null)
                ->required(fn (Get $get): bool => SlidingDoorBuilderFormService::model($page, $get) !== null)
                ->live()
                ->afterStateUpdated(function (Set $set) use ($page): void {
                    $set('variantId', null);
                    $page->resetValidation();
                }),
            Select::make('variantId')
                ->searchable()
                ->id('builder-variant')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => SlidingDoorBuilderFormService::hasError($page, 'variantId') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.variant'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(SlidingDoorBuilderFormService::variants($page, $get))->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->id => $variant->color->getLabel() . ' — ' . $variant->name])->all())
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
            if ($model->id !== SlidingDoorBuilderFormService::id($page->builderFields['modelId'])) {
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
            ->options(fn (): array => collect($page->builderCatalog->infills)->mapWithKeys(fn (BuilderInfillData $infill): array => [$infill->id => SlidingDoorBuilderFormService::infillOptionLabel($page, $infill)])->all())
            ->disableOptionWhen(fn (int|string $value): bool => in_array(SlidingDoorBuilderFormService::id($value), SlidingDoorBuilderFormService::incompatibleInfills($page), true));
    }

    private static function configure(Schema $schema): Schema
    {
        return $schema->statePath('builderFields')->columns(1)->extraAttributes([
            // Re-query after rendering instead of retaining a detached error node.
            'x-on:form-validation-error.window' => "\$nextTick(() => \$el.querySelector('[data-validation-error]')?.closest('[data-field-wrapper]')?.scrollIntoView({ block: 'nearest' }))",
        ]);
    }
}
