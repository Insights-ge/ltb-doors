@php
    use App\Settings\GeneralSettings;
    use App\Support\Locales;

    /** @var GeneralSettings $settings */
    $settings = app(GeneralSettings::class);

    $enabledLocales  = Locales::enabled();
    $currentLocale   = app()->getLocale();
    $defaultLocale   = $settings->default_locale;

    $regionalOf = fn (string $code): string => str_replace('-', '_', \App\Enums\Locale::from($code)->regional());
    $hreflangOf = fn (string $code): string => strtolower($code);

    $params      = $params ?? [];
    $canonical   = url()->current();
    $xDefault    = route('home', array_merge(['locale' => $defaultLocale], $params));

    $title       = $title       ?? trim($__env->yieldContent('title', $settings->site_name));
    $description = $description ?? trim($__env->yieldContent('meta_description', $settings->default_meta_description ?? ''));
    $keywords    = $keywords    ?? trim($__env->yieldContent('meta_keywords', $settings->default_meta_keywords ?? ''));

    $ogImage  = $ogImage  ?? ($settings->og_image ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->og_image) : null);
    $nowIso   = \Illuminate\Support\Carbon::now()->toIso8601String();

    $base   = url('/');
    $orgId  = $base . '#org';
    $siteId = $base . '#website';

    $langsForSchema = array_map(
        fn (\App\Enums\Locale $l) => str_replace('_', '-', $l->regional()),
        $enabledLocales,
    );

    $graph = [
        '@context' => 'https://schema.org',
        '@graph'   => [
            [
                '@type'  => 'Organization',
                '@id'    => $orgId,
                'name'   => $settings->site_name,
                'url'    => $base,
                'logo'   => [
                    '@type' => 'ImageObject',
                    'url'   => $settings->branding_logo_main ? \Illuminate\Support\Facades\Storage::disk('public')->url($settings->branding_logo_main) : null,
                ],
            ],
            [
                '@type'      => 'WebSite',
                '@id'        => $siteId,
                'url'        => $base,
                'name'       => $settings->site_name,
                'inLanguage' => $langsForSchema,
                'publisher'  => ['@id' => $orgId],
            ],
            [
                '@type'       => 'WebPage',
                '@id'         => $canonical . '#webpage',
                'url'         => $canonical,
                'name'        => $title,
                'inLanguage'  => str_replace('_', '-', \App\Enums\Locale::from($currentLocale)->regional()),
                'description' => $description,
                'isPartOf'    => ['@id' => $siteId],
                'publisher'   => ['@id' => $orgId],
                'dateModified' => $nowIso,
            ],
        ],
    ];
@endphp

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
@if ($keywords)
    <meta name="keywords" content="{{ $keywords }}">
@endif

<meta name="robots" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">
<meta name="googlebot" content="index,follow,max-snippet:-1,max-image-preview:large,max-video-preview:-1">
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="format-detection" content="telephone=no">

<link rel="canonical" href="{{ $canonical }}">

<meta property="og:site_name" content="{{ $settings->site_name }}">
<meta property="og:type" content="{{ $ogType ?? $settings->og_type }}">
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
@if ($ogImage)
    <meta property="og:image" content="{{ $ogImage }}">
    <meta property="og:image:width" content="{{ $settings->og_image_width }}">
    <meta property="og:image:height" content="{{ $settings->og_image_height }}">
@endif
<meta property="og:locale" content="{{ $regionalOf($currentLocale) }}">

@foreach ($enabledLocales as $alt)
    @if ($alt->value !== $currentLocale)
        <meta property="og:locale:alternate" content="{{ $regionalOf($alt->value) }}">
    @endif
@endforeach

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
@if ($ogImage)
    <meta name="twitter:image" content="{{ $ogImage }}">
@endif
@if ($settings->social_facebook)
    <meta property="og:see_also" content="{{ $settings->social_facebook }}">
@endif

<link rel="alternate" hreflang="x-default" href="{{ $xDefault }}">
@foreach ($enabledLocales as $loc)
    <link rel="alternate" hreflang="{{ $hreflangOf($loc->value) }}" href="{{ route('home', array_merge(['locale' => $loc->value], $params)) }}">
@endforeach

<script type="application/ld+json">
    {!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
</script>

<x-seo.gtm-head />
