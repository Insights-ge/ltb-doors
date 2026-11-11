<div class="builder-review mt-4 space-y-4" x-show="canReview">
    <div class="builder-print-actions flex flex-wrap gap-2">
        <x-filament::button x-bind:disabled="!canReview" x-on:click="printDesign()" data-print-design>{{ __('sliding_door_builder.review.print') }}</x-filament::button>
    </div>
    <div class="builder-review-edit flex flex-wrap gap-2">
        <x-filament::button color="gray" x-on:click="goToStep(1)">{{ __('sliding_door_builder.review.edit_size') }}</x-filament::button>
        <x-filament::button color="gray" x-on:click="goToStep(2)">{{ __('sliding_door_builder.review.edit_system') }}</x-filament::button>
        <x-filament::button color="gray" x-on:click="goToStep(3)">{{ __('sliding_door_builder.review.edit_layout') }}</x-filament::button>
    </div>
    <dl class="grid gap-3 text-sm">
        @foreach (['openingWidth' => 'size.width', 'openingHeight' => 'size.height', 'modelName' => 'system.model', 'frameDesignCode' => 'system.design', 'variantName' => 'system.variant', 'colorLabel' => 'review.colour', 'layoutLabel' => 'layouts.title'] as $field => $label)
            <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.' . $label) }}</dt><dd class="text-gray-600 dark:text-gray-300" x-text="reviewDesign?.{{ $field }}"></dd></div>
        @endforeach
        <div><dt class="font-medium text-gray-950 dark:text-white">{{ __('sliding_door_builder.system.soft_close') }}</dt><dd x-text="reviewDesign?.softClose ? @js(__('sliding_door_builder.review.yes')) : @js(__('sliding_door_builder.review.no'))"></dd></div>
    </dl>
    <template x-for="door in (reviewDesign?.doors ?? [])" :key="door.id">
        <div class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h3 class="font-medium" x-text="previewDoorLabel(door.index)"></h3>
                <x-filament::button color="gray" size="sm" class="builder-review-edit" x-on:click="focusIssue({step:3,field:'builder-finishes',doorIndex:door.index})" x-bind:aria-label="@js(__('sliding_door_builder.review.edit')) + ' ' + previewDoorLabel(door.index)">{{ __('sliding_door_builder.review.edit') }}</x-filament::button>
            </div>
            <p class="text-sm" x-text="reviewDesign.layoutLabel"></p>
            <dl class="mt-2 space-y-2 text-sm">
                <template x-for="panel in door.panels" :key="panel.id">
                    <div><dt class="flex flex-wrap items-center justify-between gap-2"><span x-text="previewPanelLabel(panel.index)"></span><button type="button" class="builder-review-edit underline" x-on:click="focusIssue({step:3,field:'builder-finishes',doorIndex:door.index,panelIndex:panel.index})" x-bind:aria-label="@js(__('sliding_door_builder.review.edit')) + ' ' + previewDoorLabel(door.index) + ', ' + previewPanelLabel(panel.index)">{{ __('sliding_door_builder.review.edit') }}</button></dt><dd class="text-gray-600 dark:text-gray-300" x-text="panel.infill ? `${panel.infill.code} — ${panel.infill.description}` : @js(__('sliding_door_builder.finishes.unselected'))"></dd></div>
                </template>
            </dl>
        </div>
    </template>
    <ul class="space-y-1 text-sm text-gray-500 dark:text-gray-400">
        <template x-for="issue in [...validation.advisories, ...validation.assumptions]" :key="issue.code"><li x-text="issueMessage(issue)"></li></template>
    </ul>
</div>
