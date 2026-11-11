<x-filament::section :heading="__('sliding_door_builder.summary.title')" class="builder-summary">
    <dl class="grid gap-2 text-sm">
        <div><dt>{{ __('sliding_door_builder.size.width') }}</dt><dd x-text="previewConfiguration.openingWidth ?? '—'"></dd></div>
        <div><dt>{{ __('sliding_door_builder.size.height') }}</dt><dd x-text="previewConfiguration.openingHeight ?? '—'"></dd></div>
        <div><dt>{{ __('sliding_door_builder.system.model') }}</dt><dd x-text="selectedModel?.name ?? '—'"></dd></div>
    </dl>
</x-filament::section>
