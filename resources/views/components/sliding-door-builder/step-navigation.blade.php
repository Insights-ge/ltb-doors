@props(['steps'])

<div class="builder-step-navigation">
    <x-filament::tabs :label="__('sliding_door_builder.steps_label')">
        @foreach ($steps as $step)
            <x-filament::tabs.item
                :badge="$step->value"
                :alpine-active="'activeStep === ' . $step->value"
                x-on:click="goToStep({{ $step->value }})"
                x-bind:aria-current="activeStep === {{ $step->value }} ? 'step' : null"
                aria-controls="builder-step-{{ $step->value }}"
            >
                {{ $step->getLabel() }}
            </x-filament::tabs.item>
        @endforeach
    </x-filament::tabs>
</div>
