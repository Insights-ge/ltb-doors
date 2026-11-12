<x-filament::section :heading="__('sliding_door_builder.preview.title')" class="builder-preview" x-id="['builder-preview-drawing']">
    <p class="sr-only" role="status" aria-live="polite" aria-atomic="true" x-text="previewSelectionAnnouncement"></p>
    <div
        x-show="hasPreview"
        x-cloak
        x-on:keydown.escape.stop.prevent="clearPreviewSelection()"
        class="builder-preview-selectors space-y-3 mb-4"
    >
        <div

            role="group"
            aria-label="{{ __('sliding_door_builder.preview.infills_label') }}"

            class="flex flex-wrap gap-2"
        >
            <template x-for="infill in (previewDoors[0]?.infills ?? [])" :key="infill.id">
                <x-filament::button
                    color="gray"
                    size="sm"
                    class="builder-preview-selector"
                    x-on:click="isEmptyInfill(infill.index) ? openInfillPicker(infill.index) : selectInfillIndex(infill.index)"
                    aria-keyshortcuts="Alt+ArrowUp Alt+ArrowDown"
                    x-on:keydown.alt.arrow-up.prevent.stop="swapInfills(infill.index, infill.index - 1)"
                    x-on:keydown.alt.arrow-down.prevent.stop="swapInfills(infill.index, infill.index + 1)"
                    x-bind:aria-pressed="previewSelection.infillInstanceId === infill.id"
                    x-bind:aria-controls="$id('builder-preview-drawing')"
                    x-bind:aria-label="previewInfillAccessibleLabel(infill.index)"
                    x-bind:data-preview-infill-control="infill.id"
                >
                    <span x-show="isEmptyInfill(infill.index)" aria-hidden="true">+</span>
                    <span x-text="previewInfillLabel(infill.index)"></span>
                </x-filament::button>
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
