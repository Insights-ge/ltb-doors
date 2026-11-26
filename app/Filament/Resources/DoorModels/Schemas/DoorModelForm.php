<?php

namespace App\Filament\Resources\DoorModels\Schemas;

use App\Enums\DoorModelStatus;
use App\Enums\DoorPanelType;
use Closure;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Operation;

class DoorModelForm
{
    // JavaScript's Number.MAX_SAFE_INTEGER.
    private const MAX_SAFE_PANEL_INTEGER = 9_007_199_254_740_991;

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(__('door_models.form.name'))
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->required(),
                Select::make('status')
                    ->label(__('door_models.form.status'))
                    ->options(DoorModelStatus::class)
                    ->default(DoorModelStatus::Active)
                    ->required(),
                Section::make(__('door_models.form.sizing_section'))
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('min_height')
                                    ->label(__('door_models.form.min_height'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_height')
                                    ->label(__('door_models.form.max_height'))
                                    ->live(onBlur: true)
                                    ->numeric()
                                    ->required(),
                                TextInput::make('min_width')
                                    ->label(__('door_models.form.min_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('max_width')
                                    ->label(__('door_models.form.max_width'))
                                    ->numeric()
                                    ->required(),
                                TextInput::make('not_recommended_height_min')
                                    ->label(__('door_models.form.not_recommended_height_min'))
                                    ->numeric(),
                                TextInput::make('not_recommended_height_max')
                                    ->label(__('door_models.form.not_recommended_height_max'))
                                    ->live(onBlur: true)
                                    ->numeric(),
                            ]),
                    ])
                    ->disabledOn(Operation::Edit)
                    ->columnSpanFull(),
                Repeater::make('panel_layout')
                    ->label(__('door_models.form.panels'))
                    ->addActionLabel(__('door_models.form.add_panel'))
                    ->defaultItems(1)
                    ->required()
                    ->minItems(1)
                    ->deletable(fn (?array $state): bool => count($state ?? []) > 1)
                    ->maxItems(7)
                    ->reorderableWithButtons()
                    ->live()
                    ->columns(3)
                    ->schema([
                        Select::make('type')
                            ->label(__('door_models.form.panel_type'))
                            ->options(DoorPanelType::class)
                            ->default(DoorPanelType::Flexible->value)
                            ->required()
                            ->live(),
                        self::panelInteger('fixed_mm')->visible(fn (Get $get): bool => $get('type') === DoorPanelType::Fixed),
                        self::panelInteger('min_mm')->visible(fn (Get $get): bool => $get('type') === DoorPanelType::Flexible),
                        self::panelInteger('weight')->default(1)->visible(fn (Get $get): bool => $get('type') === DoorPanelType::Flexible),
                    ])
                    ->rule(fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                        if (! is_array($value) || $value === []) {
                            return;
                        }

                        // At least one flexible panel required
                        if (! in_array(DoorPanelType::Flexible->value, array_column($value, 'type'), true)) {
                            $fail(__('door_models.form.panel_requires_flexible'));

                            return;
                        }

                        $maximum = max((int) $get('max_height'), (int) $get('not_recommended_height_max'));
                        if ($maximum > 0 && self::panelStackHeight($value) > $maximum) {
                            $fail(__('door_models.form.panel_exceeds_maximum', ['maximum' => $maximum]));
                        }
                    })
                    ->columnSpanFull(),
            ]);
    }

    private static function panelInteger(string $field): TextInput
    {
        return TextInput::make($field)
            ->label(__("door_models.form.panel_{$field}"))
            ->integer()
            ->minValue(1)
            ->maxValue(self::MAX_SAFE_PANEL_INTEGER)
            ->required()
            ->live(onBlur: true)
            ->dehydrateStateUsing(fn (mixed $state): int => (int) $state);
    }

    private static function panelStackHeight(array $panels): int
    {
        $height = 0;
        foreach ($panels as $panel) {
            $field = ($panel['type'] ?? null) === DoorPanelType::Fixed->value ? 'fixed_mm' : 'min_mm';
            $value = filter_var($panel[$field] ?? null, FILTER_VALIDATE_INT, [
                'options' => ['min_range' => 1, 'max_range' => self::MAX_SAFE_PANEL_INTEGER],
            ]);
            $height += $value === false ? 0 : $value;
        }

        return $height;
    }
}
