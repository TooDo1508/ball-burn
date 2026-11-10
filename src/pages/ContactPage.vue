<script setup>
import { reactive, ref } from 'vue';
import '../assets/styles/contact.css';

const contactDefaults = {
    email: '',
    phone: '0886 688 614',
    address: '614 Lạc Long Quân, Tây Hồ, Hà Nội 100000',
    map_coordinates: '21.075256962736187, 105.81640685092688',
    business_hours: 'Mỗi ngày · 05:00–24:00',
    venue_description: '8 sân pickleball và khu Bistro luôn sẵn sàng đón bạn.',
    map_url: 'https://maps.app.goo.gl/q4FVTh8toEBiqxgo9',
    facebook_url: 'https://www.facebook.com/ballburnvietnam',
    instagram_url: 'https://www.instagram.com/ballburnpickleclubbistro',
};

function readContactSettings() {
    try {
        const element = document.querySelector('#initial-contact-settings');
        return element?.textContent ? JSON.parse(element.textContent) : {};
    } catch {
        return {};
    }
}

const contact = reactive({ ...contactDefaults, ...readContactSettings() });
const form = reactive({ name: '', email: '', phone: '', subject: '', message: '', website: '' });
const submitting = ref(false);
const errors = ref({});
const successMessage = ref('');
const formError = ref('');

document.title = 'Liên hệ BALLBURN | PickleClub & Bistro';

async function submitMessage() {
    submitting.value = true;
    errors.value = {};
    formError.value = '';
    successMessage.value = '';

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
        const response = await fetch('/contact', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            },
            body: JSON.stringify(form),
        });
        const result = await response.json().catch(() => ({}));

        if (response.status === 422 && result.errors) {
            errors.value = result.errors;
            formError.value = 'Vui lòng kiểm tra lại các thông tin bên dưới.';
            return;
        }

        if (!response.ok) {
            throw new Error(result.message || 'Chưa gửi được lời nhắn. Bạn thử lại sau ít phút nhé.');
        }

        successMessage.value = result.message || 'Cảm ơn bạn đã liên hệ BALLBURN. Chúng tôi sẽ phản hồi sớm nhất có thể.';
        Object.assign(form, { name: '', email: '', phone: '', subject: '', message: '', website: '' });
    } catch (error) {
        formError.value = error instanceof Error ? error.message : 'Có lỗi xảy ra khi gửi lời nhắn.';
    } finally {
        submitting.value = false;
    }
}

function fieldError(name) {
    return errors.value[name]?.[0] ?? '';
}
</script>

<template>
    <main class="contact-page">
        <section class="contact-hero" aria-labelledby="contact-title">
            <div class="contact-hero-image" aria-hidden="true" />
            <div class="contact-hero-copy">
                <p class="contact-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1 id="contact-title">Kết nối<br /><em>cùng BALLBURN</em></h1>
                <p>Bạn cần hỗ trợ đặt sân, muốn tìm hiểu lớp học hay chỉ muốn rủ đội mình ghé chơi? Hãy nhắn cho chúng tôi.</p>
            </div>
        </section>

        <section class="contact-content" aria-label="Thông tin và biểu mẫu liên hệ">
            <div class="contact-details">
                <p class="contact-eyebrow">GHÉ THĂM CHÚNG TÔI</p>
                <h2>Một điểm hẹn,<br />nhiều cuộc vui.</h2>

                <div class="contact-detail-list">
                    <div class="contact-detail-item">
                        <span class="contact-detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z"/><circle cx="12" cy="10" r="2.5"/></svg></span>
                        <div><h3>Địa chỉ</h3><p class="contact-address">{{ contact.address }}</p><p class="contact-coordinates">Tọa độ: {{ contact.map_coordinates }}</p><a :href="contact.map_url" target="_blank" rel="noopener noreferrer">Chỉ đường đến BALLBURN <span aria-hidden="true">↗</span></a></div>
                    </div>
                    <div class="contact-detail-item">
                        <span class="contact-detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M21 16.5v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 1.1 3.8 2 2 0 0 1 3.1 1.6h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L7 9.5a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.8 2.1Z"/></svg></span>
                        <div><h3>Điện thoại</h3><a class="contact-detail-value" :href="`tel:${contact.phone.replace(/[^+\d]/g, '')}`">{{ contact.phone }}</a><p>Gọi cho chúng tôi để được hỗ trợ nhanh.</p></div>
                    </div>
                    <div v-if="contact.email" class="contact-detail-item">
                        <span class="contact-detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/></svg></span>
                        <div><h3>Email</h3><a class="contact-detail-value" :href="`mailto:${contact.email}`">{{ contact.email }}</a><p>Email chính thức để liên hệ với BALLBURN.</p></div>
                    </div>
                    <div class="contact-detail-item">
                        <span class="contact-detail-icon" aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                        <div><h3>Giờ hoạt động</h3><p>{{ contact.business_hours }}</p><p class="contact-detail-note">{{ contact.venue_description }}</p></div>
                    </div>
                </div>

                <a class="contact-map-card" :href="contact.map_url" target="_blank" rel="noopener noreferrer">
                    <img :src="'/images/pickleball/court-community.jpg'" alt="Không khí giao lưu tại sân pickleball BALLBURN" loading="lazy" />
                    <span><strong>Hẹn bạn ở Tây Hồ</strong><small>Mở bản đồ <span aria-hidden="true">↗</span></small></span>
                </a>
                <div class="contact-social-links"><a v-if="contact.facebook_url" :href="contact.facebook_url" target="_blank" rel="noopener noreferrer">Facebook <span aria-hidden="true">↗</span></a><a v-if="contact.instagram_url" :href="contact.instagram_url" target="_blank" rel="noopener noreferrer">Instagram <span aria-hidden="true">↗</span></a></div>
            </div>

            <div class="contact-form-panel">
                <p class="contact-eyebrow">CHÚNG TÔI ĐANG LẮNG NGHE</p>
                <h2>Gửi lời nhắn</h2>
                <p class="contact-form-intro">Để lại thông tin, đội ngũ BALLBURN sẽ liên hệ lại với bạn.</p>

                <div v-if="successMessage" class="contact-alert contact-alert-success" role="status">{{ successMessage }}</div>
                <div v-if="formError" class="contact-alert contact-alert-error" role="alert">{{ formError }}</div>

                <form class="contact-form" @submit.prevent="submitMessage" novalidate>
                    <div class="contact-honeypot" aria-hidden="true"><label for="contact-website">Website</label><input id="contact-website" v-model="form.website" name="website" tabindex="-1" autocomplete="off" /></div>

                    <div class="contact-field">
                        <label for="contact-name">Họ và tên <span>*</span></label>
                        <input id="contact-name" v-model.trim="form.name" name="name" autocomplete="name" maxlength="150" required placeholder="Tên của bạn" :aria-invalid="!!fieldError('name')" aria-describedby="contact-name-error" />
                        <small v-if="fieldError('name')" id="contact-name-error" class="contact-field-error">{{ fieldError('name') }}</small>
                    </div>

                    <div class="contact-field-row">
                        <div class="contact-field">
                            <label for="contact-email">Email <span>*</span></label>
                            <input id="contact-email" v-model.trim="form.email" name="email" type="email" autocomplete="email" maxlength="255" required placeholder="ban@email.com" :aria-invalid="!!fieldError('email')" aria-describedby="contact-email-error" />
                            <small v-if="fieldError('email')" id="contact-email-error" class="contact-field-error">{{ fieldError('email') }}</small>
                        </div>
                        <div class="contact-field">
                            <label for="contact-phone">Số điện thoại <span class="contact-optional">(không bắt buộc)</span></label>
                            <input id="contact-phone" v-model.trim="form.phone" name="phone" type="tel" autocomplete="tel" maxlength="30" placeholder="Số điện thoại của bạn" :aria-invalid="!!fieldError('phone')" aria-describedby="contact-phone-error" />
                            <small v-if="fieldError('phone')" id="contact-phone-error" class="contact-field-error">{{ fieldError('phone') }}</small>
                        </div>
                    </div>

                    <div class="contact-field">
                        <label for="contact-subject">Chủ đề <span>*</span></label>
                        <select id="contact-subject" v-model="form.subject" name="subject" required :aria-invalid="!!fieldError('subject')" aria-describedby="contact-subject-error">
                            <option disabled value="">Chọn nội dung bạn quan tâm</option>
                            <option>Đặt sân và lịch chơi</option>
                            <option>Giá sân và dịch vụ</option>
                            <option>Lớp học và sự kiện</option>
                            <option>Góp ý trải nghiệm</option>
                            <option>Chủ đề khác</option>
                        </select>
                        <small v-if="fieldError('subject')" id="contact-subject-error" class="contact-field-error">{{ fieldError('subject') }}</small>
                    </div>

                    <div class="contact-field">
                        <label for="contact-message">Lời nhắn <span>*</span></label>
                        <textarea id="contact-message" v-model.trim="form.message" name="message" rows="5" maxlength="5000" required placeholder="Bạn cần BALLBURN hỗ trợ điều gì?" :aria-invalid="!!fieldError('message')" aria-describedby="contact-message-error" />
                        <small v-if="fieldError('message')" id="contact-message-error" class="contact-field-error">{{ fieldError('message') }}</small>
                    </div>

                    <button class="contact-submit" type="submit" :disabled="submitting">
                        {{ submitting ? 'Đang gửi lời nhắn…' : 'Gửi lời nhắn' }} <span aria-hidden="true">{{ submitting ? '' : '↗' }}</span>
                    </button>
                    <p class="contact-privacy-note">Thông tin của bạn chỉ được dùng để phản hồi yêu cầu này.</p>
                </form>
            </div>
        </section>
    </main>
</template>
