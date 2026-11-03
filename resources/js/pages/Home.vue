<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

const props = defineProps<{ content: Record<string, string> }>();

const courts = [
    { number: '01', name: 'Tám sân tiêu chuẩn', detail: 'Không gian rộng 2.700 m² cho mọi cuộc hẹn', alt: 'Một trận pickleball đôi đang diễn ra tại sân trong nhà', image: '/images/pickleball/court-rally-wide.jpg' },
    { number: '02', name: 'Trận đấu có VAR', detail: 'Xem lại pha bóng, chơi thêm phần hào hứng', alt: 'Vận động viên chuẩn bị đánh bóng trong trận pickleball', image: '/images/pickleball/player-action.jpg' },
    { number: '03', name: 'Mát mẻ quanh năm', detail: 'Mái che và hệ thống làm mát hiện đại', alt: 'Người chơi cầm vợt tại một sân pickleball có mái che', image: '/images/pickleball/paddle-court.jpg' },
];

const structuredData = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'SportsActivityLocation',
    name: props.content.site_name,
    description: props.content.meta_description,
    image: props.content.og_image,
    url: '/',
});
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
                    <a href="/">Trang chủ</a>
                    <a href="/about">Về chúng tôi</a>
                    <a href="/booking">Sân chơi</a>
                    <a href="/blog">Tin tức</a>
                    <a href="/contact">Liên hệ</a>
                </nav>
                <a class="home-button home-button--small" href="/booking">Đặt sân <span aria-hidden="true">↗</span></a>
            </header>

            <section class="home-hero" aria-labelledby="hero-title">
                <div class="hero-copy">
                    <p class="home-eyebrow">PICKLEBALL · COMMUNITY · BISTRO</p>
                    <h1 id="hero-title">{{ props.content.hero_title }}</h1>
                    <p class="hero-description">{{ props.content.hero_description }}</p>
                    <div class="hero-actions">
                        <a class="home-button" href="/booking">Khám phá lịch sân <span aria-hidden="true">↗</span></a>
                        <a class="home-text-link" href="#about">Khám phá BALLBURN <span aria-hidden="true">↓</span></a>
                    </div>
                    <p class="hero-note"><span></span> Sân chơi cho mọi trình độ</p>
                </div>
                <div class="hero-image">
                    <img :src="props.content.hero_image" alt="Một trận đấu pickleball đôi trên sân trong nhà" fetchpriority="high" />
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
                    <a class="home-text-link home-text-link--dark" href="/about">Khám phá câu chuyện BALLBURN <span aria-hidden="true">↗</span></a>
                </div>
            </section>

            <section id="courts" class="courts-section">
                <div class="section-topline"><div class="section-kicker"><span>02</span><span>SÂN CHƠI CỦA BẠN</span></div><a class="home-text-link home-text-link--dark" href="/booking">Xem tất cả sân <span aria-hidden="true">↗</span></a></div>
                <div class="courts-heading"><h2>{{ props.content.courts_title }}</h2><p>{{ props.content.courts_text }}</p></div>
                <div class="court-grid">
                    <article v-for="(court, index) in courts" :key="court.number" class="court-card" :class="`court-card--${index + 1}`">
                        <div class="court-photo"><img :src="court.image" :alt="court.alt" loading="lazy" /><span class="court-number">{{ court.number }}</span><span class="court-arrow" aria-hidden="true">↗</span></div>
                        <div class="court-info"><div><h3>{{ court.name }}</h3><p>{{ court.detail }}</p></div><span class="court-plus" aria-hidden="true">+</span></div>
                    </article>
                </div>
            </section>

            <section class="home-facilities" aria-labelledby="home-facilities-title">
                <div class="section-kicker"><span>03</span><span>MỘT ĐIỂM HẸN, NHIỀU TRẢI NGHIỆM</span></div>
                <div class="home-facilities-content">
                    <div class="home-facilities-heading"><p class="home-eyebrow">PLAY · LEARN · CONNECT</p><h2 id="home-facilities-title">Ngày vui<br /><em>không chỉ có một trận.</em></h2><p>Từ cú giao bóng đầu tiên đến lúc ngồi lại cùng đồng đội, BALLBURN có đủ những điều khiến bạn muốn dành trọn buổi ở đây.</p></div>
                    <div class="home-facility-grid">
                        <article><span>01</span><h3>Mượn vợt, thuê bóng</h3><p>Chưa có đồ chơi? Cứ đến sân, đội ngũ sẽ hỗ trợ bạn chuẩn bị.</p></article>
                        <article><span>02</span><h3>Lớp học & giao lưu</h3><p>Học thêm kỹ thuật, tìm bạn chơi và tham gia các giải đấu cộng đồng.</p></article>
                        <article><span>03</span><h3>Bistro sau trận</h3><p>Ghé lounge dùng bữa, gọi món yêu thích và kể lại pha bóng vừa rồi.</p></article>
                        <article><span>04</span><h3>Đến dễ dàng</h3><p>Bãi đỗ xe rộng rãi ngay tại khu sân, thuận tiện cho cả nhóm.</p></article>
                    </div>
                </div>
            </section>
        </div>

        <section id="bistro" class="bistro-section">
            <div class="bistro-shell">
                <div class="section-kicker section-kicker--light"><span>04</span><span>HƠN CẢ MỘT TRẬN ĐẤU</span></div>
                <div class="bistro-content"><p class="home-eyebrow">PICKLECLUB & BISTRO</p><h2>{{ props.content.bistro_title }}</h2><p>{{ props.content.bistro_text }}</p><a class="home-button home-button--light" href="/contact">Ghé thăm chúng tôi <span aria-hidden="true">↗</span></a></div>
                <div class="bistro-orbit" aria-hidden="true">GOOD<br />VIBES<br /><span>ONLY</span></div>
            </div>
        </section>

        <div class="home-shell">
            <section class="booking-cta"><div><p class="home-eyebrow">READY WHEN YOU ARE</p><h2>{{ props.content.booking_title }}</h2></div><a class="home-button" href="/booking">Đặt sân ngay <span aria-hidden="true">↗</span></a></section>
            <footer class="home-footer">
                <div class="home-footer-brand">
                    <a class="home-brand" href="/" aria-label="BALLBURN - Trang chủ">BALLBURN<span>®</span></a>
                    <p>Chơi hết mình. Vui hết cỡ.</p>
                    <span>© {{ new Date().getFullYear() }} BALLBURN. All rights reserved.</span>
                </div>
                <div class="home-footer-center">
                    <nav class="home-footer-nav" aria-label="Liên kết chân trang">
                        <a href="/">Trang chủ</a><a href="/about">Về chúng tôi</a><a href="/booking">Sân chơi</a><a href="/blog">Tin tức</a><a href="/contact">Liên hệ</a>
                    </nav>
                </div>
                <div class="home-footer-contact">
                    <strong>Thông tin liên hệ</strong>
                    <a href="tel:0886688614"><span aria-hidden="true">☎</span><span>0886 688 614</span></a>
                    <p><span aria-hidden="true">◷</span><span>Mỗi ngày · 05:00–24:00</span></p>
                    <a href="https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">⌖</span><span>614 Lạc Long Quân, Nhật Tân,<br />Tây Hồ, Hà Nội</span></a>
                    <div class="home-footer-socials" aria-label="Mạng xã hội BALLBURN">
                        <a href="https://www.facebook.com/ballburnvietnam" aria-label="Facebook BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.6-1.5h2V3.8c-.4-.1-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v1.6H7v3h3.1v8H14Z"/></svg></a>
                        <a href="https://www.instagram.com/ballburnpickleclubbistro" aria-label="Instagram BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r=".8" class="social-dot"/></svg></a>
                    </div>
                </div>
            </footer>
        </div>
    </main>
</template>

<style>
@import '../../css/home.css';
</style>
