<x-filament::section :heading="__('sliding_door_builder.preview.title')" class="builder-preview" x-id="['builder-preview-drawing']">
    <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="previewSelectionAnnouncement"></p>
    <div class="builder-preview-layout">
        <div
            x-show="hasPreview"
            x-cloak
            class="builder-preview-selectors"
        >
            <div

                role="group"
                aria-label="{{ __('sliding_door_builder.preview.infills_label') }}"

                class="builder-preview-selector-list"
                x-bind:style="{ transform: `translateX(-${previewLabelOffset}px)` }"
            >
                <template x-for="infill in (previewDoors[0]?.infills ?? [])" :key="infill.id">
                    <span
                        class="builder-preview-infill-label"
                        x-bind:style="{ top: `${previewInfillPositions[infill.index] ?? 50}%` }"
                        x-text="previewInfillLabel(infill.index)"
                    ></span>
                </template>
            </div>
        </div>
        <div class="builder-preview-canvas">
            <svg
                x-show="hasPreview"
                x-cloak
                role="img"
                x-bind:id="$id('builder-preview-drawing')"
                aria-label="{{ __('sliding_door_builder.preview.drawing_label') }}"
                class="builder-preview-svg"
                wire:ignore
            ></svg>
            <div x-show="!hasPreview" class="builder-preview-placeholder">
                <x-filament::empty-state
                    :heading="__('sliding_door_builder.preview.empty_heading')"
                    :description="__('sliding_door_builder.preview.empty_description')"
                    :icon="\Filament\Support\Icons\Heroicon::OutlinedPhoto"
                    :contained="false"
                />
            </div>
        </div>
    </div>
    <div x-show="hasPreview" class="mt-4 space-y-3">
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('sliding_door_builder.preview.drag_hint') }}</p>
        <div data-remove-infill-drop class="rounded-lg border border-dashed border-gray-300 p-3 dark:border-gray-600" x-bind:class="{'ring-2 ring-primary-500': draggedInfillIndex !== null}">
            <x-filament::button color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedTrash" x-bind:disabled="!selectedPreviewInfill || paletteInfillId === null" x-on:click="setInfill(selectedPreviewInfill.index, null)" data-clear-infill>{{ __('sliding_door_builder.preview.remove_infill') }}</x-filament::button>
            <span class="ms-2 text-sm text-gray-500 dark:text-gray-400">{{ __('sliding_door_builder.preview.drop_remove') }}</span>
        </div>
    </div>
    <x-filament::modal id="builder-infill-picker" teleport="body" width="lg" :heading="__('sliding_door_builder.preview.choose_infill')">
        <p class="mb-3 text-sm" x-text="pickerIndex === null ? '' : previewInfillLabel(pickerIndex)"></p>
        {{ $this->quickInfillForm }}
    </x-filament::modal>
</x-filament::section>
