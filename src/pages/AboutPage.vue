<script setup>
import { onMounted } from 'vue';
import BaseButton from '../components/ui/BaseButton.vue';
import '../assets/styles/about.css';

const title = 'Về BALLBURN | PickleClub & Bistro tại Tây Hồ';
const description = 'Khám phá tổ hợp pickleball 2.700 m² với 8 sân, VAR, bistro và hoạt động cộng đồng tại Tây Hồ, Hà Nội.';
const heroImage = '/images/pickleball/court-rally-wide.jpg';

function setMeta(selector, attribute, value) {
    let element = document.head.querySelector(selector);
    if (!element) {
        element = document.createElement(selector.startsWith('link') ? 'link' : 'meta');
        if (selector.includes('property=')) element.setAttribute('property', selector.match(/property="([^"]+)/)[1]);
        if (selector.includes('name=')) element.setAttribute('name', selector.match(/name="([^"]+)/)[1]);
        document.head.append(element);
    }
    element.setAttribute(attribute, value);
}

onMounted(() => {
    const absoluteHeroImage = new URL(heroImage, window.location.origin).href;
    document.title = title;
    setMeta('meta[name="description"]', 'content', description);
    setMeta('meta[property="og:type"]', 'content', 'website');
    setMeta('meta[property="og:title"]', 'content', title);
    setMeta('meta[property="og:description"]', 'content', description);
    setMeta('meta[property="og:image"]', 'content', absoluteHeroImage);
    setMeta('meta[name="twitter:card"]', 'content', 'summary_large_image');
    setMeta('link[rel="canonical"]', 'href', `${window.location.origin}/about`);
    document.head.querySelector('meta[property="article:published_time"]')?.remove();
    document.head.querySelector('meta[property="article:modified_time"]')?.remove();

    let structuredData = document.querySelector('#structured-data');
    if (!structuredData) {
        structuredData = document.createElement('script');
        structuredData.id = 'structured-data';
        structuredData.type = 'application/ld+json';
        document.head.append(structuredData);
    }
    structuredData.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'BALLBURN PickleClub & Bistro',
        description,
        url: `${window.location.origin}/about`,
        image: absoluteHeroImage,
    });
});
</script>

<template>
    <main class="about-page">
        <section class="about-hero" aria-labelledby="about-title">
            <img :src="heroImage" alt="Một trận pickleball đôi diễn ra tại sân trong nhà" fetchpriority="high" />
            <div class="about-hero-overlay"></div>
            <div class="about-hero-copy">
                <p class="eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1 id="about-title">CHƠI HẾT MÌNH.<br /><em>VUI HẾT CỠ.</em></h1>
                <p>Tám sân chơi. Một bistro. Rất nhiều lý do<br />để bạn ở lại thêm một chút.</p>
            </div>
        </section>

        <div class="about-container">
            <section id="story" class="about-story">
                <div class="about-story-copy">
                    <p class="about-label"><span>01</span><span>CÂU CHUYỆN BALLBURN</span></p>
                    <h2>Chạm vợt đầu ngày.<br /><em>Thêm bạn cuối trận.</em></h2>
                    <p class="about-lead">BALLBURN là tổ hợp pickleball, ẩm thực và giải trí rộng 2.700 m² giữa khu vực Hồ Tây. Tám sân có mái che cùng hệ thống làm mát hiện đại để những buổi chơi có thể diễn ra từ sáng sớm đến đêm muộn.</p>
                    <p>Người mới có thể thuê vợt, mượn bóng và bắt đầu với một buổi học. Người chơi lâu năm có thể rủ đồng đội vào sân, xem lại những pha bóng qua hệ thống VAR hoặc tham gia các giải giao lưu định kỳ.</p>
                    <p>Trận đấu kết thúc cũng là lúc một cuộc vui khác bắt đầu: cả nhóm chuyển sang bistro và lounge để dùng bữa, nghỉ ngơi và kể lại những pha bóng hay nhất. Tại BALLBURN, bạn có thể đến chỉ để chơi — rồi phát hiện mình muốn ở lại lâu hơn.</p>
                </div>
                <figure class="about-story-image">
                    <img :src="'/images/pickleball/paddle-court.jpg'" alt="Người chơi cầm vợt pickleball trong khu sân có mái che" loading="lazy" />
                    <figcaption><span>01 / 04</span> MỖI TRẬN ĐẤU LÀ MỘT CUỘC HẸN</figcaption>
                </figure>
            </section>

            <section class="about-stats" aria-label="BALLBURN trong những con số">
                <article><strong>08</strong><span>sân pickleball</span></article>
                <article><strong>2.700<span>m²</span></strong><span>không gian trải nghiệm</span></article>
                <article><strong>05:00–24:00</strong><span>mở cửa mỗi ngày</span></article>
            </section>

            <section class="about-pillars" aria-label="Trải nghiệm tại BALLBURN">
                <article>
                    <span class="pillar-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><rect x="4" y="6" width="24" height="20" rx="2"/><path d="M4 16h24M16 6v20M10 16h.01M22 16h.01"/></svg></span>
                    <h3>Tám sân có mái che</h3><p>Hệ thống làm mát giúp trận đấu luôn dễ chịu, bất kể thời tiết.</p>
                </article>
                <article>
                    <span class="pillar-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><rect x="5" y="7" width="22" height="18" rx="2"/><path d="M9 11h14M9 16h8M9 21h5M22 18l2 2 4-5"/></svg></span>
                    <h3>VAR tại sân</h3><p>Xem lại những pha bóng kịch tính và làm trận đấu thêm phần hào hứng.</p>
                </article>
                <article>
                    <span class="pillar-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><path d="M5 18h22l-2 9H7l-2-9ZM8 18l2-8h12l2 8M13 10V6h6v4M11 22h.01M21 22h.01"/></svg></span>
                    <h3>Bistro & lounge</h3><p>Ẩm thực và không gian thư giãn để cuộc vui không dừng sau tiếng còi.</p>
                </article>
                <article>
                    <span class="pillar-icon" aria-hidden="true"><svg viewBox="0 0 32 32"><circle cx="16" cy="10" r="4"/><circle cx="8" cy="22" r="4"/><circle cx="24" cy="22" r="4"/><path d="M13 13 10 18m9-5 3 5m-10 4h8"/></svg></span>
                    <h3>Cộng đồng năng động</h3><p>Lớp học, giải giao lưu và hoạt động dành cho nhiều trình độ.</p>
                </article>
            </section>

            <section class="about-manifesto">
                <div class="about-manifesto-image"><img :src="'/images/pickleball/court-community.jpg'" alt="Các sân pickleball và người chơi cùng tham gia một buổi giao lưu" loading="lazy" /></div>
                <div class="about-manifesto-copy"><p class="eyebrow">MORE THAN JUST A GAME</p><h2>Chơi.<br />Kết nối.<br /><em>Lặp lại.</em></h2><p>Đến vì một trận đấu. Ở lại vì những người bạn, món ngon và năng lượng tích cực quanh tám sân chơi.</p><BaseButton to="/booking" variant="light">Tìm sân chơi của bạn <span aria-hidden="true">↗︎</span></BaseButton></div>
            </section>

            <section id="facilities" class="about-facilities">
                <div class="facility-heading"><p class="about-label"><span>02</span><span>KHÔNG GIAN BALLBURN</span></p><h2>Mọi thứ cho<br /><em>một ngày trọn vẹn.</em></h2><p>Từ lúc thay giày đến khi nâng ly sau trận, mọi điểm dừng đều sẵn sàng cho bạn. Và nếu bạn mới chơi, đội ngũ luôn sẵn lòng giúp bạn bắt nhịp.</p></div>
                <div class="facility-list">
                    <article><span>01</span><div><h3>8 sân tiêu chuẩn</h3><p>Quy mô lớn, bố trí cho những buổi chơi thường ngày và hoạt động giao lưu.</p></div></article>
                    <article><span>02</span><div><h3>Mái che & làm mát</h3><p>Hệ thống làm mát hiện đại giúp bạn thoải mái vận động quanh năm.</p></div></article>
                    <article><span>03</span><div><h3>Cho thuê vợt & bóng</h3><p>Tiện lợi cho người mới bắt đầu hoặc khách muốn trải nghiệm trước.</p></div></article>
                    <article><span>04</span><div><h3>Lớp học & giải đấu</h3><p>Cơ hội học thêm kỹ thuật, gặp bạn chơi và thử sức qua các giải giao lưu.</p></div></article>
                    <article><span>05</span><div><h3>Bistro & khu lounge</h3><p>Thực đơn đa dạng, chỗ nghỉ rộng rãi và không gian thư giãn sau trận.</p></div></article>
                    <article><span>06</span><div><h3>Bãi đỗ xe tại sân</h3><p>Thuận tiện để cả nhóm di chuyển và gửi xe trong cùng khuôn viên.</p></div></article>
                </div>
            </section>

            <section class="about-visit">
                <div><p class="about-label"><span>03</span><span>HẸN GẶP Ở HỒ TÂY</span></p><h2>Ngay tại<br /><em>614 Lạc Long Quân.</em></h2><p>Chúng tôi mở cửa mỗi ngày từ 05:00 đến 24:00. Chọn giờ chơi, rủ đồng đội và để BALLBURN lo phần còn lại.</p></div>
                <div class="about-visit-actions"><a class="button button--outline" href="https://maps.app.goo.gl/q4FVTh8toEBiqxgo9" target="_blank" rel="noopener noreferrer">Mở Google Maps <span aria-hidden="true">↗︎</span></a><a class="button button--outline" href="tel:0886688614">Gọi 0886 688 614 <span aria-hidden="true">↗︎</span></a></div>
            </section>
        </div>
        <section class="about-final-cta"><div><p class="eyebrow">YOUR COURT IS WAITING</p><h2>Trận đấu tiếp theo<br />bắt đầu từ đây.</h2></div><BaseButton to="/booking">Đặt sân ngay <span aria-hidden="true">↗︎</span></BaseButton></section>
    </main>
</template>
