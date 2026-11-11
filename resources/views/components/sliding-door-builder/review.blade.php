<div class="builder-review mt-4 space-y-4" x-show="canReview">
    <dl class="grid grid-cols-1 gap-x-6 gap-y-3 text-sm sm:grid-cols-2">
        @foreach (['openingWidth' => 'size.width', 'openingHeight' => 'size.height', 'modelName' => 'system.model', 'frameDesignCode' => 'system.design', 'variantName' => 'system.variant', 'colorLabel' => 'review.colour'] as $field => $label)
            <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.' . $label) }}</dt><dd class="text-gray-600 dark:text-gray-300" x-text="reviewDesign?.{{ $field }}"></dd></div>
        @endforeach
        <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.system.soft_close') }}</dt><dd x-text="reviewDesign?.softClose ? @js(__('sliding_door_builder.review.yes')) : @js(__('sliding_door_builder.review.no'))"></dd></div>
    </dl>
    <div class="flex flex-wrap gap-3">
        <x-filament::modal id="builder-review-components" teleport="body" width="2xl" :heading="__('components.layout.components')">
            <x-slot name="trigger">
                <x-filament::button color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedWrenchScrewdriver" data-review-components>
                    {{ __('components.layout.components') }}
                </x-filament::button>
            </x-slot>
            <dl class="mt-2 space-y-3 text-sm">
                @foreach (\App\Enums\ComponentType::cases() as $type)
                    <div data-review-component="{{ $type->value }}">
                        <dt class="font-medium text-gray-950 dark:text-white">{{ $type->getLabel() }}</dt>
                        <dd class="text-gray-600 dark:text-gray-300" x-text="reviewComponentLabel(@js($type->value)) || @js(__('sliding_door_builder.review.not_included'))"></dd>
                    </div>
                @endforeach
            </dl>
        </x-filament::modal>
        <x-filament::modal id="builder-review-panels" teleport="body" width="2xl" :heading="__('sliding_door_builder.review.shared_panels')">
            <x-slot name="trigger">
                <x-filament::button color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedSquares2x2" data-review-panels>
                    {{ __('sliding_door_builder.review.shared_panels') }}
                </x-filament::button>
            </x-slot>
            <template x-for="door in (reviewDesign?.doors ?? []).slice(0, 1)" :key="door.id">
                <div>
                    <dl class="mt-2 space-y-2 text-sm">
                        <template x-for="panel in door.panels" :key="panel.id">
                            <div><dt class="flex flex-wrap items-center justify-between gap-2"><span x-text="previewPanelLabel(panel.index)"></span></dt><dd class="text-gray-600 dark:text-gray-300" x-text="panel.infill ? `${panel.infill.code} — ${panel.infill.description}` : @js(__('sliding_door_builder.finishes.unselected'))"></dd></div>
                        </template>
                    </dl>
                </div>
            </template>
        </x-filament::modal>
    </div>
</div>
