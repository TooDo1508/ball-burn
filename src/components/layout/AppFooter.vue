<template>
    <footer class="site-footer">
        <div class="footer-brand-block">
            <RouterLink class="brand brand--footer" to="/">BALLBURN<span>®</span></RouterLink>
            <p>Chơi hết mình. Vui hết cỡ.</p>
        </div>
        <nav class="footer-nav" aria-label="Liên kết chân trang">
            <RouterLink to="/">Trang chủ</RouterLink>
            <RouterLink to="/about">Về chúng tôi</RouterLink>
            <RouterLink to="/booking">Đặt sân</RouterLink>
            <RouterLink to="/blog">Tin tức</RouterLink>
            <RouterLink to="/contact">Liên hệ</RouterLink>
        </nav>
        <span>© {{ new Date().getFullYear() }} BALLBURN PickleClub & Bistro</span>
    </footer>
</template>

<script setup>
import { RouterLink } from 'vue-router';
</script>
