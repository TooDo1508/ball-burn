<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            @if (($page['component'] ?? null) === 'Home' && isset($page['props']['seo']))
                <title>{{ $page['props']['seo']['title'] }} - {{ config('app.name') }}</title>
                <meta head-key="description" name="description" content="{{ $page['props']['seo']['description'] }}">
                <meta head-key="og:type" property="og:type" content="website">
                <meta head-key="og:title" property="og:title" content="{{ $page['props']['seo']['title'] }}">
                <meta head-key="og:description" property="og:description" content="{{ $page['props']['seo']['description'] }}">
                <meta head-key="og:site_name" property="og:site_name" content="{{ $page['props']['content']['site_name'] }}">
                <meta head-key="og:locale" property="og:locale" content="vi_VN">
                <meta head-key="og:url" property="og:url" content="{{ $page['props']['seo']['canonical'] }}">
                <meta head-key="og:image" property="og:image" content="{{ $page['props']['seo']['image'] }}">
                <meta head-key="og:image:alt" property="og:image:alt" content="Sân pickleball BALLBURN tại Tây Hồ, Hà Nội">
                <meta head-key="twitter:card" name="twitter:card" content="summary_large_image">
                <meta head-key="twitter:title" name="twitter:title" content="{{ $page['props']['seo']['title'] }}">
                <meta head-key="twitter:description" name="twitter:description" content="{{ $page['props']['seo']['description'] }}">
                <meta head-key="twitter:image" name="twitter:image" content="{{ $page['props']['seo']['image'] }}">
                <link head-key="canonical" rel="canonical" href="{{ $page['props']['seo']['canonical'] }}">
                <script head-key="structured-data" type="application/ld+json">{!! json_encode($page['props']['seo']['structured_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
            @else
            <title>{{ config('app.name', 'Laravel') }}</title>
            @endif
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
        @if (($page['component'] ?? null) === 'Home' && isset($page['props']['content'], $page['props']['contactInfo']))
            <noscript>
                <main>
                    <h1>{{ str_replace('\\n', ' ', $page['props']['content']['hero_title'] ?? 'BALLBURN PickleClub & Bistro') }}</h1>
                    <p>{{ $page['props']['content']['hero_description'] ?? $page['props']['content']['meta_description'] }}</p>
                    <section>
                        <h2>{{ $page['props']['content']['about_title'] ?? 'Về BALLBURN' }}</h2>
                        <p>{{ $page['props']['content']['about_text'] ?? '' }}</p>
                    </section>
                    <address>
                        {{ $page['props']['contactInfo']['address'] ?? '' }}<br>
                        {{ $page['props']['contactInfo']['phone'] ?? '' }}<br>
                        {{ $page['props']['contactInfo']['business_hours'] ?? '' }}
                    </address>
                    <nav aria-label="BALLBURN">
                        <a href="{{ \App\Support\SeoUrl::to('/about') }}">Giới thiệu</a>
                        <a href="{{ \App\Support\SeoUrl::to('/booking') }}">Đặt sân</a>
                        <a href="{{ \App\Support\SeoUrl::to('/blog') }}">Tin tức pickleball</a>
                        <a href="{{ \App\Support\SeoUrl::to('/contact') }}">Liên hệ</a>
                    </nav>
                </main>
            </noscript>
        @endif
    </body>
</html>
