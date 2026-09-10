@php
    use App\Settings\GeneralSettings;
    use App\Support\Locales;

    /** @var GeneralSettings $settings */
    $settings = app(GeneralSettings::class);
    $enabledLocales = Locales::enabled();
    $currentLocale  = app()->getLocale();
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}" class="scroll-smooth" dir="ltr"
    prefix="og: http://ogp.me/ns#">

<head>
    <x-seo.head />

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600&display=swap"
        rel="stylesheet">

    @vite(['resources/css/app.css'])

    @stack('head')
</head>

<body
    class="antialiased bg-dark text-white font-sans selection:bg-primary selection:text-white overflow-x-hidden min-h-screen flex flex-col">
    <x-seo.gtm-body />

    <header
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 border-b border-white/5 bg-dark/80 backdrop-blur-md">
        <div class="max-w-7xl mx-auto px-6 h-20 flex items-center justify-between">
            <a href="{{ route('home', ['locale' => $currentLocale]) }}" class="flex items-center gap-2 group">
                @if ($settings->branding_logo_header)
                    <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->branding_logo_header) }}" alt="{{ $settings->site_name }}"
                        class="h-10 w-auto">
                @else
                    <span class="text-lg font-bold">{{ $settings->site_name }}</span>
                @endif
            </a>

            <nav class="hidden md:flex items-center gap-8 text-sm font-medium text-white/60">
            </nav>

            <div class="flex items-center gap-4">
                @if (count($enabledLocales) > 1)
                    <div
                        class="flex items-center gap-2 me-4 bg-white/5 p-1 rounded-full group cursor-pointer transition-all hover:bg-white/10">
                        @foreach ($enabledLocales as $locale)
                            <a rel="alternate" hreflang="{{ $locale->value }}"
                                href="{{ route('home', ['locale' => $locale->value]) }}"
                                class="px-2 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider transition-all {{ $currentLocale === $locale->value ? 'bg-primary text-white shadow-lg shadow-primary/20' : 'text-white/40 hover:text-white' }}">
                                {{ $locale->value }}
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="flex items-center gap-4">
                    <a href="{{ filament()->getPanel('admin')->getUrl() }}"
                        class="px-6 py-2.5 bg-primary hover:bg-primary-hover text-white rounded-full font-semibold transition-all shadow-lg shadow-primary/20">
                        {{ __('welcome.account') }}
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-grow">
        @yield('content')
    </main>

    <footer class="py-12 border-t border-white/5 bg-dark mt-auto">
        <div class="max-w-7xl mx-auto px-6 flex flex-col md:flex-row justify-between items-center gap-8">
            <div class="flex items-center gap-4">
                <a href="{{ route('home', ['locale' => $currentLocale]) }}" class="opacity-50 hover:opacity-100 transition-opacity">
                    @if ($settings->branding_logo_footer)
                        <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($settings->branding_logo_footer) }}" alt="{{ $settings->site_name }}"
                            class="size-8">
                    @endif
                </a>
                <span
                    class="text-sm font-semibold text-white/30 uppercase tracking-widest">{{ __('welcome.all_rights_reserved') }}</span>
            </div>
        </div>
    </footer>

    @stack('scripts')
</body>

</html>
