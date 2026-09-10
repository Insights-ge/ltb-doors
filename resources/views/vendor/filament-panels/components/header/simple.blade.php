@props([
    'heading' => null,
    'logo' => true,
    'subheading' => null,
])

<header class="fi-simple-header">
    @if ($logo)
        <div class="fi-simple-header-logo-ctn">
            <x-filament-panels::logo />

            @include('filament.admin.components.brand-text')
        </div>
    @endif

    @if (filled($heading))
        <h1 class="fi-simple-header-heading">
            {{ $heading }}
        </h1>
    @endif

    @if (filled($subheading))
        <p class="fi-simple-header-subheading">
            {{ $subheading }}
        </p>
    @endif
</header>
