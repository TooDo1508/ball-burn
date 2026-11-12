<script setup>
import { onMounted, reactive } from 'vue';
import { RouterLink } from 'vue-router';

const contact = reactive({
    email: '',
    phone: '0886 688 614',
    address: '614 Lạc Long Quân, Tây Hồ, Hà Nội 100000',
    map_coordinates: '21.075256962736187, 105.81640685092688',
    business_hours: 'Mỗi ngày · 05:00–24:00',
    map_url: 'https://maps.app.goo.gl/q4FVTh8toEBiqxgo9',
    facebook_url: 'https://www.facebook.com/ballburnvietnam',
    instagram_url: 'https://www.instagram.com/ballburnpickleclubbistro',
});

onMounted(async () => {
    try {
        const response = await fetch('/api/site/contact-info', { headers: { Accept: 'application/json' } });
        if (response.ok) Object.assign(contact, (await response.json()).data ?? {});
    } catch {
        // Keep the verified venue details as a fallback when the public settings endpoint is unavailable.
    }
});
</script>

<template>
    <footer class="site-footer">
        <div class="site-footer-inner">
            <div class="footer-brand-block">
                <a class="brand brand--footer" href="/" aria-label="BALLBURN - Trang chủ">BALLBURN<span>®</span></a>
                <p>Chơi hết mình. Vui hết cỡ.</p>
                <small>© {{ new Date().getFullYear() }} BALLBURN. All rights reserved.</small>
            </div>

            <div class="footer-center">
                <nav class="footer-nav" aria-label="Liên kết chân trang">
                    <a href="/">Trang chủ</a>
                    <RouterLink to="/about">Về chúng tôi</RouterLink>
                    <RouterLink to="/booking">Đặt sân</RouterLink>
                    <RouterLink to="/blog">Tin tức</RouterLink>
                    <RouterLink to="/contact">Liên hệ</RouterLink>
                </nav>
            </div>

            <address class="footer-contact">
                <p class="footer-contact-title">Ghé chơi với BALLBURN</p>
                <a class="footer-contact-row" :href="`tel:${contact.phone.replace(/[^+\d]/g, '')}`"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.8 19.8 0 0 1-8.63-3.07 19.4 19.4 0 0 1-6-6A19.8 19.8 0 0 1 2.12 4.2 2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.96.35 1.9.7 2.8a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.9.35 1.84.58 2.8.7A2 2 0 0 1 22 16.92Z" /></svg></span><span>{{ contact.phone }}</span></a>
                <p class="footer-contact-row"><span aria-hidden="true"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" /><path d="M12 7v5l3 2" /></svg></span><span>{{ contact.business_hours }}</span></p>
                <a v-if="contact.email" class="footer-contact-row" :href="`mailto:${contact.email}`"><span aria-hidden="true"><svg viewBox="0 0 24 24"><rect x="3" y="5" width="18" height="14" rx="2" /><path d="m3 7 9 6 9-6" /></svg></span><span>{{ contact.email }}</span></a>
                <a class="footer-contact-row" :href="contact.map_url" target="_blank" rel="noopener noreferrer"><span aria-hidden="true"><svg viewBox="0 0 24 24"><path d="M20 10c0 5-8 12-8 12S4 15 4 10a8 8 0 1 1 16 0Z" /><circle cx="12" cy="10" r="2.5" /></svg></span><span>{{ contact.address }}</span></a>
                <div class="footer-socials" aria-label="Mạng xã hội BALLBURN">
                    <a v-if="contact.facebook_url" :href="contact.facebook_url" aria-label="Facebook BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.6-1.5h2V3.8c-.4-.1-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v1.6H7v3h3.1v8H14Z"/></svg></a>
                    <a v-if="contact.instagram_url" :href="contact.instagram_url" aria-label="Instagram BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r=".8" class="social-dot"/></svg></a>
                </div>
            </address>
        </div>
    </footer>
</template>
