@props(['steps', 'initialStep'])

<div class="builder-controls">
    @foreach ($steps as $step)
        <div
            role="region"
            aria-label="{{ $step->getLabel() }}"
            tabindex="-1"
            class="builder-step-content"
            id="builder-step-{{ $step->value }}"
            x-show="activeStep === {{ $step->value }}"
            @if ($step !== $initialStep) x-cloak @endif
        >
            <x-filament::section :heading="$step->getLabel()">
                <p class="builder-control-description text-sm text-gray-500 dark:text-gray-400">
                    {{ $step->getDescription() }}
                </p>
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Size)
                    <div class="mt-4">{{ $this->sizeForm }}</div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::System)
                    <div class="mt-4">{{ $this->systemForm }}</div>
                    <div x-show="selectedVariant" class="mt-4 text-sm text-gray-500 dark:text-gray-400">
                        <p>{{ __('sliding_door_builder.system.range_variantHeight') }}: <span x-text="sizingReference?.variantHeight?.join(' – ')"></span> {{ __('sliding_door_builder.size.unit') }}</p>
                    </div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Infills)
                    <div class="mt-4">{{ $this->infillForm }}</div>
                @endif
                @if ($step === \App\Enums\SlidingDoorBuilderStep::Review)
                    <x-sliding-door-builder.review />
                @endif
                <x-slot name="footer">
                    <div class="builder-control-actions flex items-center justify-between gap-2">
                        @if ($step->value > 1)
                            <x-filament::button color="gray" x-on:click="goToStep(activeStep - 1)" x-bind:disabled="formBusy" wire:loading.attr="disabled" wire:target="validateBuilderSection" data-builder-previous>{{ __('sliding_door_builder.navigation.previous') }}</x-filament::button>
                        @else
                            <span></span>
                        @endif
                        @if ($step->value < 4)
                            <x-filament::button x-on:click="goToStep(activeStep + 1)" x-bind:disabled="formBusy" wire:loading.attr="disabled" wire:target="validateBuilderSection" data-builder-next>{{ __('sliding_door_builder.navigation.next') }}</x-filament::button>
                        @endif
                    </div>
                </x-slot>
            </x-filament::section>
        </div>
    @endforeach
    <div class="builder-control-actions mt-4">
        <x-filament::button color="gray" x-on:click="requestReset()" data-reset-builder>{{ __('sliding_door_builder.navigation.reset') }}</x-filament::button>
    </div>
    <x-filament::modal id="builder-confirm-reset" teleport="body" :heading="__('sliding_door_builder.navigation.reset_heading')" :description="__('sliding_door_builder.navigation.reset_description')">
        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="$dispatch('close-modal', { id: 'builder-confirm-reset' })">{{ __('sliding_door_builder.finishes.cancel') }}</x-filament::button>
            <x-filament::button color="danger" x-on:click="resetConfiguration()" data-confirm-reset>{{ __('sliding_door_builder.navigation.reset') }}</x-filament::button>
        </x-slot>
    </x-filament::modal>
    <x-filament::modal
        id="builder-confirm-replacement"
        teleport="body"
        :heading="__('sliding_door_builder.finishes.replace_heading')"
        :description="__('sliding_door_builder.finishes.replace_description')"
    >
        <x-slot name="footerActions">
            <x-filament::button color="gray" x-on:click="cancelConfigurationChange()" data-cancel-replacement>
                {{ __('sliding_door_builder.finishes.cancel') }}
            </x-filament::button>
            <x-filament::button color="danger" x-on:click="confirmConfigurationChange()" data-confirm-replacement>
                {{ __('sliding_door_builder.finishes.replace') }}
            </x-filament::button>
        </x-slot>
    </x-filament::modal>
</div>
