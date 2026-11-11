<div class="builder-review mt-4 space-y-4" x-show="canReview">
    <dl class="grid gap-3 text-sm">
        @foreach (['openingWidth' => 'size.width', 'openingHeight' => 'size.height', 'modelName' => 'system.model', 'frameDesignCode' => 'system.design', 'variantName' => 'system.variant', 'colorLabel' => 'review.colour'] as $field => $label)
            <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.' . $label) }}</dt><dd class="text-gray-600 dark:text-gray-300" x-text="reviewDesign?.{{ $field }}"></dd></div>
        @endforeach
        <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.system.soft_close') }}</dt><dd x-text="reviewDesign?.softClose ? @js(__('sliding_door_builder.review.yes')) : @js(__('sliding_door_builder.review.no'))"></dd></div>
        <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.review.divisions') }}</dt><dd x-text="layoutSummary"></dd></div>
    </dl>
    <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
        <h3 class="font-medium">{{ __('components.layout.components') }}</h3>
        <dl class="mt-2 space-y-3 text-sm">
            @foreach (\App\Enums\ComponentType::cases() as $type)
                <div data-review-component="{{ $type->value }}">
                    <dt class="font-medium text-gray-950 dark:text-white">{{ $type->getLabel() }}</dt>
                    <dd class="text-gray-600 dark:text-gray-300" x-text="reviewComponentLabel(@js($type->value)) || @js(__('sliding_door_builder.review.not_included'))"></dd>
                </div>
            @endforeach
        </dl>
    </div>
    <template x-for="door in (reviewDesign?.doors ?? []).slice(0, 1)" :key="door.id">
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-medium">{{ __('sliding_door_builder.review.shared_panels') }}</h3>
            </div>
            <p class="text-sm" x-text="layoutSummary"></p>
            <dl class="mt-2 space-y-2 text-sm">
                <template x-for="panel in door.panels" :key="panel.id">
                    <div><dt class="flex flex-wrap items-center justify-between gap-2"><span x-text="previewPanelLabel(panel.index)"></span></dt><dd class="text-gray-600 dark:text-gray-300" x-text="panel.infill ? `${panel.infill.code} — ${panel.infill.description}` : @js(__('sliding_door_builder.finishes.unselected'))"></dd></div>
                </template>
            </dl>
        </div>
    </template>
</div>
