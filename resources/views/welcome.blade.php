<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $seo['title'] ?? config('app.name', 'BALLBURN PickleClub & Bistro') }}</title>
        @if (isset($seo))
            <meta name="description" content="{{ $seo['description'] }}">
            <meta property="og:type" content="website">
            <meta property="og:title" content="{{ $seo['title'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            <meta property="og:image" content="{{ $seo['image'] }}">
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seo['title'] }}">
            <meta name="twitter:description" content="{{ $seo['description'] }}">
            <meta name="twitter:image" content="{{ $seo['image'] }}">
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <script id="about-structured-data" type="application/ld+json">{!! json_encode($seo['structured_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endif

        @vite(['src/assets/styles/global.css', 'src/app.js'])
    </head>
    <body>
        <div id="app"></div>
    </body>
</html>
