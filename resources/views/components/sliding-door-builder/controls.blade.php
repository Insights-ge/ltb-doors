@props(['steps', 'initialStep'])

<div class="builder-controls">
    @foreach ($steps as $step)
        <div
            role="region"
            aria-label="{{ $step->getLabel() }}"
            tabindex="-1"
            class="builder-step-content"
            id="builder-step-{{ $step->value }}"
            x-show="activeStep === {{ $step->value }}"
            @if ($step !== $initialStep) x-cloak @endif
        >
            <x-filament::section :heading="$step->getLabel()">
                <p class="builder-control-description text-sm text-gray-500 dark:text-gray-400">
                    {{ $step->getDescription() }}
                </p>
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Size)
                    <div class="mt-4">{{ $this->sizeForm }}</div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::System)
                    <div class="mt-4">{{ $this->systemForm }}</div>
                    <div x-show="sizingReference" class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        <p>{{ __('sliding_door_builder.system.range_note') }}</p>
                        <dl class="mt-2 grid gap-1">
                            @foreach (['width', 'height', 'variantHeight'] as $range)
                                <div><dt class="inline">{{ __('sliding_door_builder.system.range_' . $range) }}:</dt> <dd class="inline"><span x-text="sizingReference?.{{ $range }}?.join(' – ')"></span> {{ __('sliding_door_builder.size.unit') }}</dd></div>
                            @endforeach
                            <div x-show="sizingReference?.discouragedHeight"><dt class="inline">{{ __('sliding_door_builder.system.discouraged_height') }}:</dt> <dd class="inline"><span x-text="sizingReference?.discouragedHeight?.join(' – ')"></span> {{ __('sliding_door_builder.size.unit') }}</dd></div>
                        </dl>
                    </div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Panels)
                    <fieldset id="builder-layouts" x-bind:aria-invalid="Boolean(fieldError('builder-layouts'))" aria-describedby="builder-layouts-error" tabindex="-1" class="mt-4" x-bind:disabled="!hasPreview">
                        <legend class="text-sm font-medium text-gray-950 dark:text-white">
                            {{ __('sliding_door_builder.layouts.title') }}
                        </legend>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ __('sliding_door_builder.layouts.shared_description') }}
                            {{ __('sliding_door_builder.layouts.remapping_description') }}
                        </p>
                        <p x-show="selectedVariant && !selectedVariant.hasPartition" class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ __('sliding_door_builder.layouts.no_partition') }}</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <template x-for="preset in metadata.layoutPresets" :key="preset.id">
                                <x-filament::button
                                    color="gray"
                                    class="builder-preview-selector"
                                    x-on:click="selectLayout(preset.id)"
                                    x-bind:aria-pressed="selectedLayoutId === preset.id"
                                    x-bind:disabled="!layoutIsAvailable(preset)"
                                    x-bind:data-layout-preset="preset.id"
                                >
                                    <span x-text="preset.label"></span>
                                </x-filament::button>
                            </template>
                        </div>
                    </fieldset>
                    <x-sliding-door-builder.field-error field="builder-layouts" />
                    <fieldset id="builder-finishes" x-bind:aria-invalid="Boolean(fieldError('builder-finishes'))" aria-describedby="builder-finishes-error" tabindex="-1" class="mt-6" x-bind:disabled="!selectedPreviewPanel">
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
                                    x-bind:disabled="infillIsIncompatible(infill.id)"
                                    x-bind:data-infill-id="infill.id"
                                >
                                    <span class="flex items-center gap-2 whitespace-normal text-start">
                                        <span aria-hidden="true" class="h-6 w-6 shrink-0 rounded border border-gray-400" x-bind:style="{ backgroundColor: infillVisual(infill.id).fill }"></span>
                                        <span><span x-text="infill.code"></span> — <span x-text="infill.description"></span>
                                        </span>
                                    </span>
                                </x-filament::button>
                            </template>
                        </div>
                    </fieldset>
                    <x-sliding-door-builder.field-error field="builder-finishes" />
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
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Review)
                    <x-sliding-door-builder.review />
                @endif
                <x-slot name="footer">
                    <div class="builder-control-actions flex items-center justify-between gap-2">
                        @if ($step->value > 1)
                            <x-filament::button color="gray" x-on:click="goToStep(activeStep - 1)" x-bind:disabled="formBusy" wire:loading.attr="disabled" wire:target="validateBuilderSection" data-builder-previous>{{ __('sliding_door_builder.navigation.previous') }}</x-filament::button>
                        @else
                            <span></span>
                        @endif
                        @if ($step->value < 4)
                            <x-filament::button x-on:click="goToStep(activeStep + 1)" x-bind:disabled="formBusy" wire:loading.attr="disabled" wire:target="validateBuilderSection" data-builder-next>{{ __('sliding_door_builder.navigation.next') }}</x-filament::button>
                        @endif
                    </div>
                </x-slot>
            </x-filament::section>
        </div>
    @endforeach
    <div class="builder-control-actions mt-4">
        <x-filament::button color="gray" x-on:click="requestReset()" data-reset-builder>{{ __('sliding_door_builder.navigation.reset') }}</x-filament::button>
    </div>
    <x-filament::modal id="builder-confirm-reset" teleport="body" :heading="__('sliding_door_builder.navigation.reset_heading')" :description="__('sliding_door_builder.navigation.reset_description')">
        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'builder-confirm-reset' })">{{ __('sliding_door_builder.finishes.cancel') }}</x-filament::button>
            <x-filament::button color="danger" x-on:click="resetConfiguration()" data-confirm-reset>{{ __('sliding_door_builder.navigation.reset') }}</x-filament::button>
        </x-slot>
    </x-filament::modal>
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
