<x-filament-panels::page>

    {{-- Terminal output --}}
    @if ($commandOutput !== null)
        <div class="rounded-lg overflow-hidden border border-gray-800 dark:border-gray-700">
            <div class="flex items-center gap-2 bg-gray-800 dark:bg-gray-900 px-4 py-2">
                <div class="flex gap-1.5">
                    <span class="size-3 rounded-full bg-red-500"></span>
                    <span class="size-3 rounded-full bg-yellow-400"></span>
                    <span class="size-3 rounded-full bg-green-500"></span>
                </div>
                <span class="flex-1 text-center text-xs font-mono text-gray-400 select-none">
                    {{ $lastCommand }}
                </span>
                @if ($lastCommandFailed)
                    <span class="text-xs font-mono text-red-400">✗ failed</span>
                @else
                    <span class="text-xs font-mono text-green-400">✓ ok</span>
                @endif
            </div>
            <div class="bg-gray-950 dark:bg-black px-4 py-3 font-mono text-sm leading-relaxed whitespace-pre-wrap break-all {{ $lastCommandFailed ? 'text-red-400' : 'text-green-400' }}">{{ $commandOutput }}</div>
        </div>
    @endif

    {{-- General / combined commands --}}
    <x-filament::section :heading="__('cache_tools.sections.general')">
        <div class="flex flex-wrap gap-3">
            <x-filament::button
                color="warning"
                icon="heroicon-o-trash"
                wire:click="runCommand('optimize:clear')"
                wire:loading.attr="disabled"
                wire:target="runCommand('optimize:clear')"
            >
                {{ __('cache_tools.commands.optimize_clear') }}
            </x-filament::button>

            <x-filament::button
                color="warning"
                icon="heroicon-o-trash"
                wire:click="runCommand('filament:optimize-clear')"
                wire:loading.attr="disabled"
                wire:target="runCommand('filament:optimize-clear')"
            >
                {{ __('cache_tools.commands.filament_optimize_clear') }}
            </x-filament::button>

            <x-filament::button
                color="success"
                icon="heroicon-o-bolt"
                wire:click="runCommand('optimize')"
                wire:loading.attr="disabled"
                wire:target="runCommand('optimize')"
            >
                {{ __('cache_tools.commands.optimize') }}
            </x-filament::button>

            <x-filament::button
                color="success"
                icon="heroicon-o-bolt"
                wire:click="runCommand('filament:optimize')"
                wire:loading.attr="disabled"
                wire:target="runCommand('filament:optimize')"
            >
                {{ __('cache_tools.commands.filament_optimize') }}
            </x-filament::button>
        </div>
    </x-filament::section>

    {{-- Two-column: clear | cache --}}
    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

        {{-- Clear column --}}
        <x-filament::section :heading="__('cache_tools.sections.clear')">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.permission_cache_reset') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">permission:cache-reset</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('permission:cache-reset')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('permission:cache-reset')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.config_clear') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">config:clear</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('config:clear')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('config:clear')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.cache_clear') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">cache:clear</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('cache:clear')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('cache:clear')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.view_clear') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">view:clear</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('view:clear')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('view:clear')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.route_clear') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">route:clear</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('route:clear')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('route:clear')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.event_clear') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">event:clear</p>
                    </div>
                    <x-filament::button
                        color="danger"
                        size="sm"
                        icon="heroicon-o-trash"
                        wire:click="runCommand('event:clear')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('event:clear')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

        {{-- Cache / build column --}}
        <x-filament::section :heading="__('cache_tools.sections.cache')">
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.config_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">config:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('config:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('config:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.route_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">route:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('route:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('route:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.view_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">view:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('view:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('view:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.event_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">event:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('event:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('event:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.icons_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">icons:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('icons:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('icons:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>

                <hr class="border-gray-200 dark:border-white/10" />

                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-950 dark:text-white">{{ __('cache_tools.commands.permission_cache') }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">permission:cache</p>
                    </div>
                    <x-filament::button
                        color="success"
                        size="sm"
                        icon="heroicon-o-bolt"
                        wire:click="runCommand('permission:cache')"
                        wire:loading.attr="disabled"
                        wire:target="runCommand('permission:cache')"
                    >
                        {{ __('cache_tools.run') }}
                    </x-filament::button>
                </div>
            </div>
        </x-filament::section>

    </div>

</x-filament-panels::page>
