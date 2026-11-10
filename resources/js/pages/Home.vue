<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

type ContactInfo = {
    email: string;
    phone: string;
    address: string;
    map_coordinates: string;
    business_hours: string;
    map_url: string;
    facebook_url: string;
    instagram_url: string;
    venue_description: string;
};
type HomeCourt = { name: string; type: string; description: string; surface: string; image: string; imageAlt: string };
type HomePost = { title: string; slug: string; excerpt: string | null; image: string | null; imageAlt: string; category: string | null; publishedAt: string | null };

const props = defineProps<{
    content: Record<string, string>;
    contactInfo: ContactInfo;
    courtCount: number;
    courts: HomeCourt[];
    posts: HomePost[];
}>();

const heroTitle = (props.content.hero_title || '').replace(/\\n/g, '\n');
const venueHours = props.contactInfo.business_hours.match(/\d{1,2}:\d{2}/g) ?? [];
const opensAt = venueHours[0] ?? '05:00';
const closesAt = venueHours.at(-1) ?? '24:00';
const structuredData = JSON.stringify({
    '@context': 'https://schema.org',
    '@type': 'SportsActivityLocation',
    name: props.content.site_name,
    description: props.content.meta_description,
    image: props.content.og_image,
    url: '/',
});

function articleDate(value: string | null): string {
    if (!value) return '';
    return new Intl.DateTimeFormat('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' }).format(new Date(value));
}

const phoneHref = `tel:${props.contactInfo.phone.replace(/[^+\d]/g, '')}`;
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
        <component :is="'script'" head-key="structured-data" type="application/ld+json" v-text="structuredData" />
    </Head>

    <main class="ballburn-home">
        <div class="home-shell">
            <header class="home-header">
                <a class="home-brand" href="/" aria-label="BALLBURN - Trang chủ">BALLBURN<span>®</span><small>PICKLECLUB & BISTRO</small></a>
                <nav class="home-nav" aria-label="Điều hướng chính">
                    <a href="#home">Trang chủ</a><a href="#courts">Sân chơi</a><a href="#experience">Trải nghiệm</a><a href="#stories">Cẩm nang</a><a href="#visit">Liên hệ</a>
                </nav>
                <a class="home-button home-button--small" href="/booking">Đặt sân <span aria-hidden="true">↗</span></a>
            </header>

            <section id="home" class="home-hero" aria-labelledby="hero-title">
                <img class="hero-photo" :src="props.content.hero_image" alt="Các vận động viên chơi pickleball tại BALLBURN" fetchpriority="high" />
                <div class="hero-shade"></div>
                <div class="hero-copy">
                    <p class="home-eyebrow">PICKLEBALL · COMMUNITY · BISTRO</p>
                    <h1 id="hero-title">{{ heroTitle }}</h1>
                    <p class="hero-description">{{ props.content.hero_description }}</p>
                    <div class="hero-actions">
                        <a class="home-button" href="/booking">Tìm giờ chơi phù hợp <span aria-hidden="true">↗</span></a>
                        <a class="home-text-link home-text-link--light" href="#overview">Khám phá BALLBURN <span aria-hidden="true">↓</span></a>
                    </div>
                </div>
                <div class="hero-index"><span>01</span><i></i><span>04</span></div>
                <p class="hero-caption">CHƠI HẾT MÌNH. VUI HẾT CỠ. <span>HỒ TÂY · HÀ NỘI</span></p>
                <a class="hero-side-link" href="/about">CÂU CHUYỆN BALLBURN <span>↗</span></a>
            </section>

            <section id="overview" class="overview-section">
                <div class="section-kicker"><span>01</span><span>ĐIỂM HẸN CỦA BẠN</span></div>
                <div class="overview-main">
                    <div class="overview-copy">
                        <p class="home-eyebrow">MORE THAN JUST A GAME</p>
                        <h2>{{ props.content.about_title }}</h2>
                        <p>{{ props.content.about_text }}</p>
                        <a class="home-text-link" href="/about">Tìm hiểu về chúng tôi <span aria-hidden="true">↗</span></a>
                    </div>
                    <div class="overview-facts" aria-label="Thông tin BALLBURN">
                        <article><strong>{{ String(props.courtCount).padStart(2, '0') }}</strong><span>sân pickleball<br />đang hoạt động</span></article>
                        <article><strong>2.700<span>m²</span></strong><span>không gian thể thao<br />và kết nối</span></article>
                        <article><strong>{{ opensAt }}</strong><span>mở cửa mỗi ngày<br />đến {{ closesAt }}</span></article>
                    </div>
                </div>
            </section>

            <section id="courts" class="courts-section">
                <div class="section-topline">
                    <div><div class="section-kicker"><span>02</span><span>CHỌN SÂN, GỌI ĐỒNG ĐỘI</span></div><h2>{{ props.content.courts_title }}</h2></div>
                    <a class="home-text-link" href="/booking">Xem lịch và giá sân <span aria-hidden="true">↗</span></a>
                </div>
                <p class="section-description">{{ props.content.courts_text }}</p>
                <div v-if="props.courts.length" class="court-grid">
                    <a v-for="(court, index) in props.courts" :key="court.name" class="court-card" href="/booking">
                        <div class="court-photo"><img :src="court.image" :alt="court.imageAlt" loading="lazy" /><span class="court-number">{{ String(index + 1).padStart(2, '0') }}</span><span class="court-arrow" aria-hidden="true">↗</span></div>
                        <div class="court-info"><div class="court-meta"><span>{{ court.type }}</span><span>{{ court.surface }}</span></div><h3>{{ court.name }}</h3><p>{{ court.description }}</p></div>
                    </a>
                </div>
                <div v-else class="courts-empty"><p>Chúng tôi đang cập nhật danh sách sân.</p><a class="home-text-link" href="/contact">Liên hệ BALLBURN <span aria-hidden="true">↗</span></a></div>
                <div class="court-bottomline"><span>{{ String(props.courtCount).padStart(2, '0') }} SÂN SẴN SÀNG ĐÓN BẠN</span><a href="/booking">Chọn giờ chơi <span aria-hidden="true">↗</span></a></div>
            </section>
        </div>

        <section id="experience" class="experience-section">
            <div class="home-shell experience-shell">
                <div class="section-kicker section-kicker--light"><span>03</span><span>MỘT NGÀY Ở BALLBURN</span></div>
                <div class="experience-heading"><p class="home-eyebrow">PLAY · CONNECT · REPEAT</p><h2>Đến vì trận đấu.<br /><em>Ở lại vì cuộc vui.</em></h2><p>Từ cú giao bóng đầu tiên đến lúc ngồi lại cùng đồng đội, mọi trải nghiệm đều ở ngay bên nhau.</p></div>
                <div class="experience-grid">
                    <article><span class="experience-icon" aria-hidden="true">01</span><div><h3>Sân chơi quanh năm</h3><p>Tám sân có mái che, làm mát và hệ thống VAR tại sân trung tâm.</p></div><a href="/booking" aria-label="Khám phá sân chơi">↗</a></article>
                    <article><span class="experience-icon" aria-hidden="true">02</span><div><h3>Dễ bắt đầu</h3><p>Người mới có thể thuê vợt, mượn bóng và tìm hiểu lớp học phù hợp.</p></div><a href="/about#facilities" aria-label="Tìm hiểu tiện ích">↗</a></article>
                    <article><span class="experience-icon" aria-hidden="true">03</span><div><h3>Kết nối cộng đồng</h3><p>Giao lưu, lớp học và giải đấu cho các nhóm trình độ khác nhau.</p></div><a href="/about" aria-label="Tìm hiểu cộng đồng">↗</a></article>
                    <article><span class="experience-icon" aria-hidden="true">04</span><div><h3>Bistro sau trận</h3><p>Thưởng thức món ngon, nghỉ ngơi và kể lại những pha bóng hay.</p></div><a href="#visit" aria-label="Ghé thăm bistro">↗</a></article>
                </div>
            </div>
        </section>

        <div class="home-shell">
            <section class="booking-section" aria-labelledby="booking-title">
                <div class="booking-intro"><div class="section-kicker"><span>04</span><span>ĐẶT SÂN THẬT DỄ</span></div><h2 id="booking-title">Chọn giờ chơi.<br /><em>Hẹn nhau trên sân.</em></h2><p>Xem lịch theo ngày, chọn một hoặc nhiều sân rồi hoàn tất yêu cầu đặt chỗ ngay trên website.</p><a class="home-button" href="/booking">Bắt đầu đặt sân <span aria-hidden="true">↗</span></a></div>
                <ol class="booking-steps">
                    <li><span>01</span><div><h3>Chọn sân và thời gian</h3><p>Lịch trống và giá được cập nhật khi bạn chọn ngày chơi.</p></div></li>
                    <li><span>02</span><div><h3>Giữ chỗ trong 5 phút</h3><p>Điền thông tin đội chơi để tạo yêu cầu đặt chỗ tạm thời.</p></div></li>
                    <li><span>03</span><div><h3>Chuyển khoản, gửi xác nhận</h3><p>Làm theo hướng dẫn thanh toán và tải ảnh biên nhận lên.</p></div></li>
                </ol>
            </section>

            <section v-if="props.posts.length" id="stories" class="stories-section">
                <div class="section-topline"><div><div class="section-kicker"><span>05</span><span>ĐỌC, HỌC, CHƠI HAY HƠN</span></div><h2>Câu chuyện trên sân.</h2></div><a class="home-text-link" href="/blog">Xem tất cả bài viết <span aria-hidden="true">↗</span></a></div>
                <div class="story-grid">
                    <a v-for="(post, index) in props.posts" :key="post.slug" class="story-card" :class="{ 'story-card--featured': index === 0 }" :href="`/blog/${post.slug}`">
                        <div class="story-image"><img v-if="post.image" :src="post.image" :alt="post.imageAlt" loading="lazy" /><span v-else class="story-image-placeholder">BALLBURN</span><span class="story-arrow" aria-hidden="true">↗</span></div>
                        <div class="story-meta"><span>{{ post.category || 'CẨM NANG PICKLEBALL' }}</span><time v-if="post.publishedAt" :datetime="post.publishedAt">{{ articleDate(post.publishedAt) }}</time></div>
                        <h3>{{ post.title }}</h3><p v-if="post.excerpt">{{ post.excerpt }}</p>
                    </a>
                </div>
            </section>
        </div>

        <section id="visit" class="visit-section">
            <div class="home-shell visit-shell">
                <div class="visit-image"><img :src="'/images/pickleball/court-community.jpg'" alt="Không khí giao lưu tại sân pickleball BALLBURN" loading="lazy" /><span>HẸN GẶP BẠN Ở HỒ TÂY</span></div>
                <div class="visit-copy"><p class="home-eyebrow">BALLBURN PICKLECLUB & BISTRO</p><h2>{{ props.content.bistro_title }}</h2><p>{{ props.content.bistro_text }}</p><div class="visit-details"><p><span aria-hidden="true">⌖</span>{{ props.contactInfo.address }}</p><p class="visit-coordinates"><span aria-hidden="true">◎</span>{{ props.contactInfo.map_coordinates }}</p><p><span aria-hidden="true">◷</span>{{ props.contactInfo.business_hours }}</p><p v-if="props.contactInfo.venue_description"><span aria-hidden="true">✳</span>{{ props.contactInfo.venue_description }}</p></div><div class="visit-actions"><a class="home-button" :href="props.contactInfo.map_url" target="_blank" rel="noopener noreferrer">Chỉ đường đến BALLBURN <span aria-hidden="true">↗</span></a><a class="visit-phone" :href="phoneHref">Gọi {{ props.contactInfo.phone }}</a></div></div>
            </div>
        </section>

        <footer class="home-footer"><div class="home-shell footer-inner">
            <div class="footer-brand"><a class="home-brand home-brand--light" href="/">BALLBURN<span>®</span><small>PICKLECLUB & BISTRO</small></a><p>Chơi hết mình. Vui hết cỡ.</p><span>© {{ new Date().getFullYear() }} BALLBURN. All rights reserved.</span></div>
            <nav class="footer-nav" aria-label="Liên kết chân trang"><a href="/about">Về chúng tôi</a><a href="/booking">Sân chơi & đặt sân</a><a href="/blog">Tin tức</a><a href="/contact">Liên hệ</a></nav>
            <div class="footer-contact"><strong>614 Lạc Long Quân, Tây Hồ</strong><a :href="phoneHref">{{ props.contactInfo.phone }}</a><a v-if="props.contactInfo.email" :href="`mailto:${props.contactInfo.email}`">{{ props.contactInfo.email }}</a><div><a v-if="props.contactInfo.facebook_url" :href="props.contactInfo.facebook_url" target="_blank" rel="noopener noreferrer">Facebook ↗</a><a v-if="props.contactInfo.instagram_url" :href="props.contactInfo.instagram_url" target="_blank" rel="noopener noreferrer">Instagram ↗</a></div></div>
        </div></footer>
    </main>
</template>

<style>
@import '../../css/home.css';
</style>
