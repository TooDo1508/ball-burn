<script setup>
import BaseButton from '../components/ui/BaseButton.vue';
import BaseHeading from '../components/ui/BaseHeading.vue';
import CourtCard from '../components/cards/CourtCard.vue';
import AppSection from '../components/layout/AppSection.vue';

const courts = [
    { number: '01', name: 'Sân số 1', surface: 'Trong nhà · Mặt sân cao cấp' },
    { number: '02', name: 'Sân số 2', surface: 'Trong nhà · Mặt sân cao cấp' },
    { number: '03', name: 'Sân số 3', surface: 'Trong nhà · Mặt sân cao cấp' },
];
</script>

<template>
    <main>
        <AppSection class="hero" aria-labelledby="hero-title">
            <div class="hero-copy">
                <p class="eyebrow hero-eyebrow">PICKLECLUB & BISTRO · TP. HỒ CHÍ MINH</p>
                <BaseHeading
                    id="hero-title"
                    as="h1"
                    size="display"
                    title="Chơi hết mình."
                    description="Kết nối trên sân. Thư giãn bên bàn ăn. Một điểm hẹn mới dành cho những người yêu thể thao và cuộc vui."
                />
                <div class="hero-actions">
                    <BaseButton to="/booking">Khám phá sân</BaseButton>
                    <BaseButton to="/about" variant="light">Tìm hiểu thêm <span aria-hidden="true">↗</span></BaseButton>
                </div>
                <div class="hero-note">
                    <span class="status-dot" aria-hidden="true"></span>
                    Sẵn sàng cho trận đấu tiếp theo?
                </div>
            </div>

            <div class="court-visual" aria-label="Minh họa sân pickleball">
                <span class="court-label">PLAY · CONNECT · REPEAT</span>
                <div class="court-lines">
                    <span class="court-net"></span>
                </div>
                <span class="court-ball" aria-hidden="true"></span>
                <span class="court-visual-index">BALLBURN / 01</span>
            </div>

            <div class="hero-index"><span>01</span><span class="index-rule"></span><span>03</span></div>
        </AppSection>

        <AppSection id="courts" class="section courts-section">
            <div class="section-heading-row">
                <BaseHeading
                    eyebrow="TÌM TRẬN ĐẤU CỦA BẠN"
                    title="Sẵn sàng vào sân?"
                    description="Không gian trong nhà thoáng mát, mặt sân chuẩn và mọi thứ đã sẵn sàng cho bạn."
                />
                <BaseButton to="/booking" variant="outline">Xem tất cả sân <span aria-hidden="true">↗</span></BaseButton>
            </div>
            <div class="court-grid">
                <CourtCard v-for="court in courts" :key="court.number" :court="court" />
            </div>
        </AppSection>

        <AppSection id="about" class="statement-section">
            <span class="statement-mark" aria-hidden="true">“</span>
            <BaseHeading
                as="h2"
                size="statement"
                eyebrow="HƠN CẢ MỘT TRẬN ĐẤU"
                title="Nơi cuộc vui không dừng lại sau tiếng còi."
                description="Chơi một trận thật hay, thưởng thức món ngon và gặp gỡ những người bạn mới. Ballburn là điểm hẹn cho tất cả."
            />
            <BaseButton to="/about" variant="light">Khám phá Ballburn <span aria-hidden="true">↗</span></BaseButton>
        </AppSection>

        <AppSection id="booking" class="booking-strip">
            <div>
                <p class="eyebrow">YOUR COURT IS WAITING</p>
                <h2>Trận đấu tiếp theo bắt đầu từ đây.</h2>
            </div>
            <BaseButton to="/contact">Liên hệ đặt sân <span aria-hidden="true">↗</span></BaseButton>
        </AppSection>
    </main>
</template>
