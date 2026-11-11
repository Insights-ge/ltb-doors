<?php

namespace App\Filament\Forms;

use App\Data\BuilderDoorModelData;
use App\Data\BuilderDoorVariantData;
use App\Filament\Pages\SlidingDoorBuilder;
use Closure;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\MessageBag;

class SlidingDoorBuilderForms
{
    public static function size(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        $fields = [];

        foreach (['openingWidth' => 'width', 'openingHeight' => 'height'] as $name => $label) {
            $fields[] = TextInput::make($name)
                ->id('builder-' . $name)
                ->label(__('sliding_door_builder.size.' . $label))
                ->inputMode('decimal')
                ->required()
                ->rules(['bail', 'numeric', 'gt:0'])
                ->mutateStateForValidationUsing(fn ($state) => is_string($state) ? str_replace(',', '.', trim($state)) : $state)
                ->live(onBlur: true)
                ->afterStateUpdated(fn () => $page->resetValidation('builderFields.' . $name))
                ->extraInputAttributes(fn (): array => [
                    'x-on:blur' => "setMeasurement('{$name}', \$event.target.value)",
                    'aria-invalid' => self::hasError($page, $name) ? 'true' : 'false',
                ])
                ->rule(fn (TextInput $component): Closure => function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                    if (is_numeric($value) && ! is_finite((float) $value)) {
                        $fail(__('validation.numeric', ['attribute' => $component->getValidationAttribute()]));
                    }
                });
        }

        return self::configure($schema)->components($fields);
    }

    public static function system(Schema $schema, SlidingDoorBuilder $page): Schema
    {
        return self::configure($schema)->components([
            Select::make('modelId')
                ->id('builder-model')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'modelId') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.model'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (): array => collect($page->builderCatalog->models)->mapWithKeys(fn (BuilderDoorModelData $model): array => [$model->id => $model->name])->all())
                ->required()
                ->live()
                ->afterStateUpdated(function (Set $set) use ($page): void {
                    $set('frameDesignCode', null);
                    $set('variantId', null);
                    $page->resetValidation();
                }),
            Select::make('frameDesignCode')
                ->id('builder-design')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'frameDesignCode') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.design'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(self::model($page, $get)->variants ?? [])->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->frameDesignCode => $variant->frameDesignCode])->all())
                ->disabled(fn (Get $get): bool => self::model($page, $get) === null)
                ->required(fn (Get $get): bool => self::model($page, $get) !== null)
                ->live()
                ->afterStateUpdated(function (Set $set) use ($page): void {
                    $set('variantId', null);
                    $page->resetValidation();
                }),
            Select::make('variantId')
                ->id('builder-variant')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'variantId') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.variant'))
                ->placeholder(__('sliding_door_builder.system.choose'))
                ->options(fn (Get $get): array => collect(self::variants($page, $get))->mapWithKeys(fn (BuilderDoorVariantData $variant): array => [$variant->id => $variant->color->getLabel() . ' — ' . $variant->name])->all())
                ->disabled(fn (Get $get): bool => self::variants($page, $get) === [])
                ->required(fn (Get $get): bool => self::variants($page, $get) !== [])
                ->live()
                ->afterStateUpdated(fn () => $page->resetValidation()),
            Checkbox::make('softClose')
                ->id('builder-soft-close')
                ->extraInputAttributes(fn (): array => ['aria-invalid' => self::hasError($page, 'softClose') ? 'true' : 'false'])
                ->label(__('sliding_door_builder.system.soft_close'))
                ->helperText(__('sliding_door_builder.system.soft_close_note'))
                ->visible(fn (Get $get): bool => (self::variant($page, $get)->hasSoftCloseMechanism ?? false) || (bool) $get('softClose'))
                ->rules(['boolean'])
                ->live()
                ->afterStateUpdated(fn () => $page->resetValidation('builderFields.softClose'))
                ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($page, $get): void {
                    if ($value && ! self::variant($page, $get)?->hasSoftCloseMechanism) {
                        $fail(__('sliding_door_builder.validation.soft_close_missing'));
                    }
                }),
        ]);
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
        return array_values(array_filter(self::model($page, $get)->variants ?? [], fn (BuilderDoorVariantData $variant): bool => $variant->frameDesignCode === $get('frameDesignCode')));
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
