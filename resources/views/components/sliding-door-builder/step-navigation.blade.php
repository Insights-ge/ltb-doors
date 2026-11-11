@props(['steps'])

<nav class="builder-step-navigation" aria-label="{{ __('sliding_door_builder.steps_label') }}">
    <ol class="builder-step-list">
        @foreach ($steps as $step)
            <li
                class="builder-step-item"
                x-bind:class="{ 'is-active': activeStep === {{ $step->value }} }"
                x-bind:aria-current="activeStep === {{ $step->value }} ? 'step' : null"
            >
                <x-filament::badge color="gray">{{ $step->value }}</x-filament::badge>
                <span class="builder-step-label">{{ $step->getLabel() }}</span>
            </li>
        @endforeach
    </ol>
</nav>
