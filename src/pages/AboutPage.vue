<script setup>
import { onMounted } from 'vue';
import BaseButton from '../components/ui/BaseButton.vue';
import '../assets/styles/about.css';

const title = 'Về BALLBURN | PickleClub & Bistro';
const description = 'Khám phá BALLBURN PickleClub & Bistro tại TP. Hồ Chí Minh — điểm hẹn sân pickleball, ẩm thực và cộng đồng dành cho mọi người yêu vận động.';
const heroImage = 'https://images.unsplash.com/photo-1626224583764-f87db24ac4ea?auto=format&fit=crop&w=2000&q=85';

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
    document.title = title;
    setMeta('meta[name="description"]', 'content', description);
    setMeta('meta[property="og:title"]', 'content', title);
    setMeta('meta[property="og:description"]', 'content', description);
    setMeta('meta[property="og:image"]', 'content', heroImage);
    setMeta('link[rel="canonical"]', 'href', `${window.location.origin}/about`);

    let structuredData = document.querySelector('#about-structured-data');
    if (!structuredData) {
        structuredData = document.createElement('script');
        structuredData.id = 'about-structured-data';
        structuredData.type = 'application/ld+json';
        document.head.append(structuredData);
    }
    structuredData.textContent = JSON.stringify({
        '@context': 'https://schema.org',
        '@type': 'Organization',
        name: 'BALLBURN PickleClub & Bistro',
        description,
        url: `${window.location.origin}/about`,
        image: heroImage,
    });
});
</script>

<template>
    <main class="about-page">
        <section class="about-hero" aria-labelledby="about-title">
            <img :src="heroImage" alt="Người chơi giao lưu trên sân pickleball tại BALLBURN" fetchpriority="high" />
            <div class="about-hero-overlay"></div>
            <div class="about-hero-copy">
                <p class="eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1 id="about-title">VỀ CHÚNG TÔI</h1>
                <p>Điểm hẹn cho thể thao, ẩm thực<br />và những kết nối thật.</p>
            </div>
        </section>

        <div class="about-container">
            <section id="story" class="about-story">
                <div class="about-label"><span>01</span><span>CÂU CHUYỆN BALLBURN</span></div>
                <div class="about-story-copy">
                    <h2>Chơi hết mình.<br /><em>Kết nối dài lâu.</em></h2>
                    <p class="about-lead">BALLBURN PickleClub & Bistro tại TP. Hồ Chí Minh là không gian nơi niềm vui vận động gặp gỡ sự hiếu khách — điểm hẹn để bạn bước vào sân, làm quen với pickleball và dành thời gian chất lượng bên những người mình quý.</p>
                    <p>Chúng tôi tin một trận đấu hay không chỉ nằm ở điểm số. Đó còn là lần đầu cầm vợt, một pha bóng khiến cả nhóm bật cười, hay câu chuyện tiếp tục bên bàn ăn sau trận. Vì thế, BALLBURN kết hợp sân pickleball, bistro và không gian cộng đồng trong cùng một trải nghiệm.</p>
                    <p>Dù bạn mới tìm hiểu pickleball hay đã có nhóm chơi quen thuộc, hãy đến theo cách của bạn: vận động, gặp gỡ và tận hưởng khoảng thời gian ngoài sân.</p>
                </div>
                <figure class="about-story-image">
                    <img src="https://images.unsplash.com/photo-1554068865-24cecd4e34b8?auto=format&fit=crop&w=900&q=85" alt="Sân pickleball với vạch sân và lưới" loading="lazy" />
                    <figcaption>PLAY · CONNECT · REPEAT</figcaption>
                </figure>
            </section>

            <section class="about-pillars" aria-label="Trải nghiệm tại BALLBURN">
                <article><span aria-hidden="true">⌑</span><h3>Sân pickleball</h3><p>Bốn sân trong nhà theo định hướng thiết kế của BALLBURN, dành cho những buổi chơi và giao lưu quanh năm.</p></article>
                <article><span aria-hidden="true">♧</span><h3>Bistro & café</h3><p>Thêm một điểm dừng để thưởng thức đồ ăn, thức uống và tiếp tục câu chuyện sau trận đấu.</p></article>
                <article><span aria-hidden="true">◎</span><h3>Cộng đồng</h3><p>Một không gian cởi mở để người mới bắt đầu và người chơi lâu năm cùng gặp gỡ.</p></article>
                <article><span aria-hidden="true">◇</span><h3>Tiện nghi</h3><p>Khu locker và phòng thay đồ giúp bạn thoải mái chuẩn bị trước và sau khi vận động.</p></article>
            </section>

            <section class="about-manifesto">
                <div class="about-manifesto-image"><img src="https://images.unsplash.com/photo-1546483875-ad9014c88eba?auto=format&fit=crop&w=1400&q=85" alt="Bạn bè cùng tham gia một buổi chơi thể thao" loading="lazy" /></div>
                <div class="about-manifesto-copy"><p class="eyebrow">MORE THAN JUST A GAME</p><h2>Chơi.<br />Kết nối.<br /><em>Lặp lại.</em></h2><p>Một trận đấu có thể bắt đầu cuộc vui. Những người bạn gặp trên sân có thể khiến bạn quay lại.</p><BaseButton to="/booking">Tìm sân chơi của bạn <span aria-hidden="true">↗</span></BaseButton></div>
            </section>

            <section id="facilities" class="about-facilities">
                <div class="about-label"><span>02</span><span>KHÔNG GIAN BALLBURN</span></div>
                <div class="facility-content"><h2>Mọi thứ cho<br /><em>một ngày trọn vẹn.</em></h2><div class="facility-list">
                    <article><span>01</span><div><h3>Bốn sân trong nhà</h3><p>Không gian chơi pickleball được thiết kế để bạn tập trung vào trận đấu.</p></div></article>
                    <article><span>02</span><div><h3>Bistro & lounge</h3><p>Đồ ăn, thức uống và chỗ ngồi để thư giãn cùng bạn bè.</p></div></article>
                    <article><span>03</span><div><h3>Locker room</h3><p>Cất đồ gọn gàng trước khi bước vào sân.</p></div></article>
                    <article><span>04</span><div><h3>Phòng thay đồ</h3><p>Tiện lợi để chuẩn bị cho buổi chơi và tiếp tục lịch trình trong ngày.</p></div></article>
                </div></div>
            </section>

            <section class="about-faq">
                <p class="eyebrow">CÂU HỎI THƯỜNG GẶP</p><h2>Lần đầu đến BALLBURN?</h2>
                <details><summary>Pickleball là môn thể thao gì?</summary><p>Pickleball là môn thể thao dùng vợt và bóng, chơi đơn hoặc đôi trên sân có lưới. Người mới có thể tìm hiểu luật cơ bản trước khi tham gia; <a href="https://usapickleball.org/rules/" target="_blank" rel="noopener noreferrer">luật chính thức được cập nhật bởi USA Pickleball</a>.</p></details>
                <details><summary>Tôi có cần biết chơi trước khi đến không?</summary><p>Không cần. BALLBURN hướng tới cả người mới bắt đầu lẫn người đã chơi thường xuyên. Hãy liên hệ đội ngũ để hỏi về lịch sân và hoạt động phù hợp trước chuyến ghé thăm.</p></details>
                <details><summary>Tôi có thể đặt sân trực tuyến ở đâu?</summary><p>Bạn có thể xem trang đặt sân để biết thông tin đặt chỗ hiện có, hoặc liên hệ BALLBURN nếu cần hỗ trợ.</p></details>
            </section>
        </div>
    </main>
</template>
