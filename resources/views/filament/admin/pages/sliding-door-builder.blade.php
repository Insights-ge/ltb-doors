<x-filament-panels::page>
    <div
        x-load
        x-load-src="{{ \Illuminate\Support\Facades\Vite::asset('resources/js/sliding-door-builder.js') }}"
        x-data="slidingDoorBuilder({ initialStep: {{ $initialStep->value }}, payload: @js($builderPayload), previewLabels: @js(__('sliding_door_builder.preview')), validationLabels: @js(__('sliding_door_builder.validation')), storageKey: @js('sliding-door-builder:' . auth()->id()) })"
        x-on:modal-closed.window="if ($event.detail.id === 'builder-confirm-replacement') pendingChange = null"
        class="sliding-door-builder grid grid-cols-1 gap-4 sm:gap-6"
    >
        @if ($builderPayload->catalog->models === [])
            <x-filament::empty-state
                :heading="__('sliding_door_builder.catalog.no_models_heading')"
                :description="__('sliding_door_builder.catalog.no_models_description')"
            />
        @elseif ($builderPayload->catalog->infills === [])
            <x-filament::empty-state
                :heading="__('sliding_door_builder.catalog.no_infills_heading')"
                :description="__('sliding_door_builder.catalog.no_infills_description')"
            />
        @else
            <p x-show="draftStatus" x-cloak role="status" class="builder-draft-status text-sm text-gray-500 dark:text-gray-400" x-text="@js(__('sliding_door_builder.drafts'))[draftStatus] ?? ''"></p>
            <x-sliding-door-builder.step-navigation :steps="$steps" />

            <div x-show="validation.errors.length && (validationAttempted || previewConfiguration.modelId)" x-cloak role="alert" class="builder-validation rounded-lg border border-danger-300 p-4 text-sm text-danger-700 dark:border-danger-700 dark:text-danger-300">
                <p class="font-medium">{{ __('sliding_door_builder.validation.heading') }}</p>
                <ul class="mt-2 list-inside list-disc">
                    <template x-for="(issue, index) in validation.errors" :key="index"><li><button type="button" class="underline focus-visible:outline-2 focus-visible:outline-offset-2" x-on:click="focusIssue(issue)" x-text="issueMessage(issue)"></button></li></template>
                </ul>
            </div>
            <div class="builder-workspace grid items-start gap-4 sm:gap-6">
                <x-sliding-door-builder.controls :steps="$steps" :initial-step="$initialStep" />
                <x-sliding-door-builder.preview />
                <x-sliding-door-builder.summary />
            </div>
        @endif
    </div>
</x-filament-panels::page>
