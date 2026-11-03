<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

const props = defineProps<{ content: Record<string, string> }>();

const courts = [
    { number: '01', name: 'Sân tiêu chuẩn', detail: 'Không gian thi đấu thoáng đãng', image: 'https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=900&q=80' },
    { number: '02', name: 'Sân đôi ngoài trời', detail: 'Tận hưởng từng đường bóng', image: 'https://images.unsplash.com/photo-1622279457486-62dcc4a431d6?auto=format&fit=crop&w=900&q=80' },
    { number: '03', name: 'Sân có mái che', detail: 'Sẵn sàng cho mọi cuộc vui', image: 'https://images.unsplash.com/photo-1595435934249-5df7ed86e1c0?auto=format&fit=crop&w=900&q=80' },
];
</script>

<template>
    <Head :title="props.content.meta_title">
        <meta head-key="description" name="description" :content="props.content.meta_description" />
        <meta head-key="og:title" property="og:title" :content="props.content.meta_title" />
        <meta head-key="og:description" property="og:description" :content="props.content.meta_description" />
        <meta head-key="og:image" property="og:image" :content="props.content.og_image" />
        <meta head-key="og:type" property="og:type" content="website" />
        <meta head-key="twitter:card" name="twitter:card" content="summary_large_image" />
        <link head-key="canonical" rel="canonical" href="/" />
        <component
            :is="'script'"
            head-key="structured-data"
            type="application/ld+json"
            v-text="JSON.stringify({ '@context': 'https://schema.org', '@type': 'SportsActivityLocation', name: props.content.site_name, description: props.content.meta_description, image: props.content.og_image, url: '/' })"
        />
    </Head>
    <main class="ballburn-home">
        <div class="home-shell">
            <header class="home-header">
                <a class="home-brand" href="/" aria-label="BALLBURN - Trang chủ">BALLBURN<span>®</span></a>
                <nav class="home-nav" aria-label="Điều hướng chính">
                    <a href="#about">Câu chuyện</a>
                    <a href="#courts">Sân chơi</a>
                    <a href="#bistro">PickleClub & Bistro</a>
                </nav>
                <Link class="home-button home-button--small" href="/booking">Đặt sân <span aria-hidden="true">↗</span></Link>
            </header>

            <section class="home-hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="home-eyebrow">PICKLEBALL · COMMUNITY · BISTRO</p>
                    <h1 id="hero-title">{{ props.content.hero_title }}</h1>
                    <p class="hero-description">{{ props.content.hero_description }}</p>
                    <div class="hero-actions">
                        <Link class="home-button" href="/booking">Khám phá lịch sân <span aria-hidden="true">↗</span></Link>
                        <a class="home-text-link" href="#about">Khám phá BALLBURN <span aria-hidden="true">↓</span></a>
                    </div>
                    <p class="hero-note"><span></span> Sân chơi cho mọi trình độ</p>
                </div>
                <div class="hero-image">
                    <img :src="props.content.hero_image" :alt="`${props.content.site_name} — sân pickleball`" fetchpriority="high" />
                    <div class="hero-image-shade"></div>
                    <div class="hero-image-caption"><span>01 / 03</span><span>THE COURT IS YOURS</span></div>
                    <div class="hero-stamp"><span>PLAY</span><b>♥</b><span>REPEAT</span></div>
                </div>
                <div class="hero-bottomline"><span>YOUR NEW FAVORITE PLACE</span><span>EST. BALLBURN · VIỆT NAM</span></div>
            </section>

            <section id="about" class="intro-section">
                <div class="section-kicker"><span>01</span><span>CHÚNG TÔI LÀ AI</span></div>
                <div class="intro-copy">
                    <h2>{{ props.content.about_title }}</h2>
                    <p>{{ props.content.about_text }}</p>
                    <a class="home-text-link home-text-link--dark" href="#bistro">Tìm hiểu thêm <span aria-hidden="true">↗</span></a>
                </div>
            </section>

            <section id="courts" class="courts-section">
                <div class="section-topline"><div class="section-kicker"><span>02</span><span>SÂN CHƠI CỦA BẠN</span></div><a class="home-text-link home-text-link--dark" href="/booking">Xem tất cả sân <span aria-hidden="true">↗</span></a></div>
                <div class="courts-heading"><h2>{{ props.content.courts_title }}</h2><p>{{ props.content.courts_text }}</p></div>
                <div class="court-grid">
                    <article v-for="(court, index) in courts" :key="court.number" class="court-card" :class="`court-card--${index + 1}`">
                        <div class="court-photo"><img :src="court.image" :alt="`${court.name} tại BALLBURN`" loading="lazy" /><span class="court-number">{{ court.number }}</span><span class="court-arrow" aria-hidden="true">↗</span></div>
                        <div class="court-info"><div><h3>{{ court.name }}</h3><p>{{ court.detail }}</p></div><span class="court-plus" aria-hidden="true">+</span></div>
                    </article>
                </div>
            </section>
        </div>

        <section id="bistro" class="bistro-section">
            <div class="bistro-shell">
                <div class="section-kicker section-kicker--light"><span>03</span><span>HƠN CẢ MỘT TRẬN ĐẤU</span></div>
                <div class="bistro-content"><p class="home-eyebrow">PICKLECLUB & BISTRO</p><h2>{{ props.content.bistro_title }}</h2><p>{{ props.content.bistro_text }}</p><a class="home-button home-button--light" href="/contact">Ghé thăm chúng tôi <span aria-hidden="true">↗</span></a></div>
                <div class="bistro-orbit" aria-hidden="true">GOOD<br />VIBES<br /><span>ONLY</span></div>
            </div>
        </section>

        <div class="home-shell">
            <section class="booking-cta"><div><p class="home-eyebrow">READY WHEN YOU ARE</p><h2>{{ props.content.booking_title }}</h2></div><Link class="home-button" href="/booking">Đặt sân ngay <span aria-hidden="true">↗</span></Link></section>
            <footer class="home-footer"><a class="home-brand" href="/">BALLBURN<span>®</span></a><p>PickleClub & Bistro · Chơi hết mình, vui hết cỡ.</p><div><a href="/about">Về chúng tôi</a><a href="/contact">Liên hệ</a></div><span>© {{ new Date().getFullYear() }} BALLBURN</span></footer>
        </div>
    </main>
</template>

<style>
@import '../../css/home.css';
</style>
