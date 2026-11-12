<!DOCTYPE html>
<html lang="vi">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="referrer" content="no-referrer">
        <meta name="venue-timezone" content="{{ config('venue.timezone', 'Asia/Ho_Chi_Minh') }}">

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
        @isset($initialContactSettings)
            <script id="initial-contact-settings" type="application/json">{!! json_encode($initialContactSettings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
        @endisset
        <div id="app">
            @if (request()->is('booking', 'booking/status/*'))
                <main role="status" aria-live="polite" style="min-height:45vh;display:grid;place-content:center;gap:8px;padding:40px 20px;text-align:center;color:#171717">
                    <strong>Đang mở lịch đặt sân…</strong>
                    <span style="font-size:14px;color:#666">Đang tải danh sách sân và khung giờ.</span>
                </main>
            @elseif (isset($initialBlogPost))
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
                    <section class="blog-hero" aria-labelledby="server-blog-heading">
                        <div class="blog-hero-image" aria-hidden="true" style="background-image:url('/images/pickleball/court-rally-wide.jpg')"></div>
                        <div class="blog-hero-copy"><p class="blog-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p><h1 id="server-blog-heading">Tin tức<br><em>pickleball</em></h1><p>Kinh nghiệm trên sân, câu chuyện cộng đồng và cảm hứng để mỗi trận đấu thêm trọn vẹn.</p></div>
                        <span class="blog-hero-index" aria-hidden="true">PLAY · LEARN · CONNECT</span>
                    </section>
                    <section class="blog-listing" aria-labelledby="server-blog-list-heading">
                        <header class="blog-section-heading"><div><p class="blog-eyebrow">GÓC KIẾN THỨC</p><h2 id="server-blog-list-heading">Mới trên sân</h2></div><p>Cập nhật từ BALLBURN dành cho người mới chơi lẫn những tay vợt đã quen sân.</p></header>
                        <div class="blog-layout">
                            <div class="blog-feed">
                                <div class="blog-post-list">
                                    @foreach ($initialBlogPosts as $post)
                                        <article class="blog-post-row">
                                            <a class="blog-post-thumb" href="/blog/{{ $post['slug'] }}" aria-label="Đọc bài: {{ $post['title'] }}">
                                                <img src="{{ $post['cover_image_url'] ?: '/images/pickleball/court-rally-wide.jpg' }}" alt="{{ $post['cover_image_alt'] ?: $post['title'] }}" loading="lazy">
                                            </a>
                                            <div class="blog-post-copy">
                                                <div class="blog-card-meta">@foreach ($post['categories'] as $category)<span>{{ $category }}</span>@endforeach @if ($post['published_at'])<time datetime="{{ $post['published_at'] }}">{{ \Illuminate\Support\Carbon::parse($post['published_at'])->locale('vi')->translatedFormat('j F Y') }}</time>@endif</div>
                                                <h3><a href="/blog/{{ $post['slug'] }}">{{ $post['title'] }}</a></h3>
                                                <p>{{ $post['excerpt'] }}</p>
                                                <a class="blog-read-link" href="/blog/{{ $post['slug'] }}">ĐỌC BÀI VIẾT <span aria-hidden="true">→︎</span></a>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                                @if ($initialBlogPostsNextPageUrl)<p class="blog-more"><a class="button button-outline" href="{{ $initialBlogPostsNextPageUrl }}">Xem thêm bài viết</a></p>@endif
                            </div>
                            <aside class="blog-sidebar" aria-label="Khám phá thêm">
                                <section class="blog-sidebar-card blog-newsletter-card"><span class="blog-sidebar-mark" aria-hidden="true">✳︎</span><p class="blog-eyebrow">HẸN GẶP BẠN TRÊN SÂN</p><h2>Chơi một trận nhé?</h2><p>Chọn khung giờ phù hợp và khám phá không khí pickleball tại BALLBURN.</p><a class="button button-primary" href="/booking">Đặt sân ngay <span aria-hidden="true">↗︎</span></a></section>
                                <a class="blog-sidebar-feature" href="/about"><img src="/images/pickleball/player-action.jpg" alt="Người chơi pickleball trên sân BALLBURN" loading="lazy"><span><strong>Chơi. Kết nối.<br>Lặp lại.</strong><small>Khám phá BALLBURN <span aria-hidden="true">↗︎</span></small></span></a>
                            </aside>
                        </div>
                    </section>
                    <section class="blog-cta"><p class="blog-eyebrow">SẴN SÀNG LÊN SÂN?</p><h2>Kiến thức tốt.<br><em>Trận đấu vui hơn.</em></h2><a class="button button-primary" href="/booking">Đặt sân tại BALLBURN <span aria-hidden="true">↗︎</span></a></section>
                </main>
            @endif
        </div>
    </body>
</html>
