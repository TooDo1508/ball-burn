<script setup>
import { RouterLink } from 'vue-router';
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
                    <RouterLink to="/booking">Sân chơi</RouterLink>
                    <RouterLink to="/blog">Tin tức</RouterLink>
                    <RouterLink to="/contact">Liên hệ</RouterLink>
                </nav>
            </div>

            <address class="footer-contact">
                <p class="footer-contact-title">Ghé chơi với BALLBURN</p>
                <a class="footer-contact-row" href="tel:0886688614"><span aria-hidden="true">☎</span><span>0886 688 614</span></a>
                <p class="footer-contact-row"><span aria-hidden="true">◷</span><span>Mỗi ngày · 05:00–24:00</span></p>
                <a class="footer-contact-row" href="https://maps.app.goo.gl/cLh8vZ8LKLJBdDAo9" target="_blank" rel="noopener noreferrer"><span aria-hidden="true">⌖</span><span>614 Lạc Long Quân, Nhật Tân,<br />Tây Hồ, Hà Nội</span></a>
                <div class="footer-socials" aria-label="Mạng xã hội BALLBURN">
                    <a href="https://www.facebook.com/ballburnvietnam" aria-label="Facebook BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M14 21v-8h3l.5-3H14V8c0-.9.3-1.5 1.6-1.5h2V3.8c-.4-.1-1.5-.2-2.8-.2-2.8 0-4.7 1.7-4.7 4.8v1.6H7v3h3.1v8H14Z"/></svg></a>
                    <a href="https://www.instagram.com/ballburnpickleclubbistro" aria-label="Instagram BALLBURN" target="_blank" rel="noopener noreferrer"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="18" cy="6" r=".8" class="social-dot"/></svg></a>
                </div>
            </address>
        </div>
    </footer>
</template>
