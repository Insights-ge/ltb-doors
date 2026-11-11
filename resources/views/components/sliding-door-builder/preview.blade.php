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
            aria-label="{{ __('sliding_door_builder.preview.panels_label') }}"

            class="flex flex-wrap gap-2"
        >
            <template x-for="panel in (previewDoors[0]?.panels ?? [])" :key="panel.id">
                <x-filament::button
                    color="gray"
                    size="sm"
                    class="builder-preview-selector"
                    x-on:click="selectPanelIndex(panel.index)"
                    x-bind:aria-pressed="previewSelection.panelId === panel.id"
                    x-bind:aria-controls="$id('builder-preview-drawing')"
                    x-bind:aria-label="previewPanelAccessibleLabel(panel.index)"
                    x-bind:data-preview-panel-control="panel.id"
                >
                    <span x-text="previewPanelLabel(panel.index)"></span>
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
</x-filament::section>
