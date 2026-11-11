@props(['steps', 'initialStep'])

<div class="builder-controls">
    @foreach ($steps as $step)
        <div
            id="builder-step-{{ $step->value }}"
            x-show="activeStep === {{ $step->value }}"
            @if ($step !== $initialStep) x-cloak @endif
        >
            <x-filament::section :heading="$step->getLabel()">
                <p class="builder-control-description text-sm text-gray-500 dark:text-gray-400">
                    {{ $step->getDescription() }}
                </p>
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Size)
                    <div class="mt-4 grid gap-4">
                        @foreach (['openingWidth' => 'width', 'openingHeight' => 'height'] as $field => $label)
                            <div>
                                <label for="builder-{{ $field }}" class="text-sm font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.size.' . $label) }}</label>
                                <x-filament::input.wrapper>
                                    <x-filament::input id="builder-{{ $field }}" type="number" min="0" step="any" inputmode="decimal" x-bind:value="previewConfiguration.{{ $field }}" x-on:change="setMeasurement('{{ $field }}', $event.target.value)" x-bind:aria-invalid="sizeErrors.{{ $field }}" aria-describedby="builder-{{ $field }}-error" />
                                </x-filament::input.wrapper>
                                <p id="builder-{{ $field }}-error" x-show="sizeErrors.{{ $field }}" x-cloak class="mt-1 text-sm text-danger-600 dark:text-danger-400">{{ __('sliding_door_builder.size.invalid') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Panels)
                    <fieldset class="mt-4" x-bind:disabled="!hasPreview">
                        <legend class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('sliding_door_builder.layouts.title') }}
                        </legend>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('sliding_door_builder.layouts.shared_description') }}
                            {{ __('sliding_door_builder.layouts.remapping_description') }}
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="preset in metadata.layoutPresets" :key="preset.id">
                                <x-filament::button
                                    color="gray"
                                    class="builder-preview-selector"
                                    x-on:click="selectLayout(preset.id)"
                                    x-bind:aria-pressed="selectedLayoutId === preset.id"
                                    x-bind:data-layout-preset="preset.id"
                                >
                                    <span x-text="preset.label"></span>
                                </x-filament::button>
                            </template>
                        </div>
                    </fieldset>
                    <fieldset class="mt-6" x-bind:disabled="!selectedPreviewPanel">
                        <legend class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('sliding_door_builder.finishes.title') }}
                        </legend>
                        <p x-show="!selectedPreviewPanel" class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('sliding_door_builder.finishes.select_panel') }}
                        </p>
                        <p x-show="selectedPreviewPanel" class="mt-1 text-sm text-gray-500 dark:text-gray-400" x-text="previewPanelAccessibleLabel(selectedPreviewPanel?.index)"></p>
                        <div class="mt-3 grid gap-2">
                            <x-filament::button
                                color="gray"
                                class="builder-preview-selector"
                                x-on:click="chooseInfill(null)"
                                x-bind:aria-pressed="paletteInfillId === null"
                                data-infill-clear
                            >
                                {{ __('sliding_door_builder.finishes.unselected') }}
                            </x-filament::button>
                            <template x-for="infill in catalog.infills" :key="infill.id">
                                <x-filament::button
                                    color="gray"
                                    class="builder-preview-selector"
                                    x-on:click="chooseInfill(infill.id)"
                                    x-bind:aria-pressed="paletteInfillId === infill.id"
                                    x-bind:data-infill-id="infill.id"
                                >
                                    <span class="flex items-center gap-2 whitespace-normal text-start">
                                        <span aria-hidden="true" class="h-6 w-6 shrink-0 rounded border border-gray-400" x-bind:style="{ backgroundColor: infillVisual(infill.id).fill }"></span>
                                        <span><span x-text="infill.code"></span> — <span x-text="infill.description"></span></span>
                                    </span>
                                </x-filament::button>
                            </template>
                        </div>
                    </fieldset>
                    <div class="mt-6 space-y-3">
                        <div>
                            <x-filament::button color="gray" class="builder-preview-selector" x-bind:disabled="!selectedPreviewPanel" x-on:click="applyFinishToAllDoors()" data-apply-finish-all>
                                {{ __('sliding_door_builder.finishes.apply_all') }}
                            </x-filament::button>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('sliding_door_builder.finishes.apply_all_description') }}</p>
                        </div>
                        <div>
                            <x-filament::button color="gray" class="builder-preview-selector" x-bind:disabled="!selectedPreviewDoor" x-on:click="copySelectedDoor()" data-copy-door>
                                {{ __('sliding_door_builder.finishes.copy_door') }}
                            </x-filament::button>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('sliding_door_builder.finishes.copy_description') }}</p>
                        </div>
                    </div>
                @endif
            </x-filament::section>
        </div>
    @endforeach
    <x-filament::modal
        id="builder-confirm-replacement"
        teleport="body"
        :heading="__('sliding_door_builder.finishes.replace_heading')"
        :description="__('sliding_door_builder.finishes.replace_description')"
    >
        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="cancelConfigurationChange()" data-cancel-replacement>
                {{ __('sliding_door_builder.finishes.cancel') }}
            </x-filament::button>
            <x-filament::button color="danger" x-on:click="confirmConfigurationChange()" data-confirm-replacement>
                {{ __('sliding_door_builder.finishes.replace') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
