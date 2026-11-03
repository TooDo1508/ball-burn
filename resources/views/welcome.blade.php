<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $seo['title'] ?? config('app.name', 'BALLBURN PickleClub & Bistro') }}</title>
        @if (isset($seo))
            <meta name="description" content="{{ $seo['description'] }}">
            <meta property="og:type" content="{{ $seo['type'] ?? 'website' }}">
            <meta property="og:title" content="{{ $seo['title'] }}">
            <meta property="og:description" content="{{ $seo['description'] }}">
            @if (!empty($seo['image']))<meta property="og:image" content="{{ $seo['image'] }}">@endif
            <meta property="og:url" content="{{ $seo['canonical'] }}">
            @if (!empty($seo['published_at']))<meta property="article:published_time" content="{{ $seo['published_at'] }}">@endif
            @if (!empty($seo['modified_at']))<meta property="article:modified_time" content="{{ $seo['modified_at'] }}">@endif
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="{{ $seo['title'] }}">
            <meta name="twitter:description" content="{{ $seo['description'] }}">
            @if (!empty($seo['image']))<meta name="twitter:image" content="{{ $seo['image'] }}">@endif
            <link rel="canonical" href="{{ $seo['canonical'] }}">
            <script id="structured-data" type="application/ld+json">{!! json_encode($seo['structured_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endif

        @vite(['src/assets/styles/global.css', 'src/app.js'])
    </head>
    <body>
        @if (isset($initialBlogPost))
            <script id="initial-blog-data" type="application/json">{!! json_encode(['type' => 'article', 'post' => $initialBlogPost, 'seo' => $seo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @elseif (isset($initialBlogPosts))
            <script id="initial-blog-data" type="application/json">{!! json_encode(['type' => 'list', 'posts' => $initialBlogPosts, 'next_page_url' => $initialBlogPostsNextPageUrl ?? null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endif
        <div id="app">
            @if (isset($initialBlogPost))
                <main class="app-container blog-article-page">
                    <article class="blog-article">
                        <nav class="blog-breadcrumb" aria-label="Điều hướng"><a href="/">Trang chủ</a><span aria-hidden="true">/</span><a href="/blog">Tin tức</a><span aria-hidden="true">/</span><span>{{ $initialBlogPost['title'] }}</span></nav>
                        <header class="blog-article-header">
                            <div class="blog-card-meta">
                                @foreach ($initialBlogPost['categories'] as $category)<span>{{ $category }}</span>@endforeach
                                @if ($initialBlogPost['published_at'])<time datetime="{{ $initialBlogPost['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($initialBlogPost['published_at'])->locale('vi')->translatedFormat('j F Y') }}</time>@endif
                            </div>
                            <h1>{{ $initialBlogPost['title'] }}</h1>
                            @if ($initialBlogPost['excerpt'])<p class="blog-article-excerpt">{{ $initialBlogPost['excerpt'] }}</p>@endif
                            <p class="blog-article-author">Biên tập bởi {{ $initialBlogPost['author'] }}</p>
                        </header>
                        @if ($initialBlogPost['cover_image_url'])<img class="blog-article-cover" src="{{ $initialBlogPost['cover_image_url'] }}" alt="{{ $initialBlogPost['cover_image_alt'] ?: $initialBlogPost['title'] }}">@endif
                        <div class="blog-article-content">{!! $initialBlogPost['body'] !!}</div>
                    </article>
                </main>
            @elseif (isset($initialBlogPosts))
                <main class="app-container blog-page">
                    <section class="blog-listing" aria-labelledby="server-blog-heading">
                        <header class="blog-section-heading"><div><p class="blog-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p><h1 id="server-blog-heading">Cẩm nang pickleball</h1></div><p>Luật chơi, chiến thuật, sức khỏe và cảm hứng cho cộng đồng pickleball tại TP. Hồ Chí Minh.</p></header>
                        <div class="blog-card-grid">
                            @foreach ($initialBlogPosts as $index => $post)
                                <article class="blog-card {{ $index === 0 ? 'blog-card-featured' : '' }}">
                                    <a class="blog-card-image" href="/blog/{{ $post['slug'] }}" aria-label="Đọc bài: {{ $post['title'] }}">
                                        @if ($post['cover_image_url'])<img src="{{ $post['cover_image_url'] }}" alt="{{ $post['cover_image_alt'] ?: $post['title'] }}" loading="lazy">@else<span class="blog-card-image-fallback">BALLBURN</span>@endif
                                    </a>
                                    <div class="blog-card-copy">
                                        <div class="blog-card-meta">@foreach ($post['categories'] as $category)<span>{{ $category }}</span>@endforeach</div>
                                        <h2><a href="/blog/{{ $post['slug'] }}">{{ $post['title'] }}</a></h2>
                                        <p>{{ $post['excerpt'] }}</p>
                                        <a class="blog-read-link" href="/blog/{{ $post['slug'] }}">ĐỌC BÀI VIẾT <span aria-hidden="true">→</span></a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </section>
                </main>
            @endif
        </div>
    </body>
</html>
