<div class="builder-review mt-4 space-y-4" x-show="canReview">
    <dl class="grid gap-3 text-sm">
        @foreach (['openingWidth' => 'size.width', 'openingHeight' => 'size.height', 'modelName' => 'system.model', 'frameDesignCode' => 'system.design', 'variantName' => 'system.variant', 'colorLabel' => 'review.colour', 'layoutLabel' => 'layouts.title'] as $field => $label)
            <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.' . $label) }}</dt><dd class="text-gray-600 dark:text-gray-300" x-text="reviewDesign?.{{ $field }}"></dd></div>
        @endforeach
        <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.system.soft_close') }}</dt><dd x-text="reviewDesign?.softClose ? @js(__('sliding_door_builder.review.yes')) : @js(__('sliding_door_builder.review.no'))"></dd></div>
    </dl>
    <template x-for="door in (reviewDesign?.doors ?? [])" :key="door.id">
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <h3 class="font-medium" x-text="previewDoorLabel(door.index)"></h3>
            <p class="text-sm" x-text="reviewDesign.layoutLabel"></p>
            <dl class="mt-2 space-y-2 text-sm">
                <template x-for="panel in door.panels" :key="panel.id">
                    <div><dt x-text="previewPanelLabel(panel.index)"></dt><dd class="text-gray-600 dark:text-gray-300" x-text="panel.infill ? `${panel.infill.code} — ${panel.infill.description}` : @js(__('sliding_door_builder.finishes.unselected'))"></dd></div>
                </template>
            </dl>
        </div>
    </template>
    <ul class="space-y-1 text-sm text-gray-500 dark:text-gray-400">
        <template x-for="issue in [...validation.advisories, ...validation.assumptions]" :key="issue.code"><li x-text="issueMessage(issue)"></li></template>
    </ul>
</div>
