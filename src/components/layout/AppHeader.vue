<script setup>
import { RouterLink } from 'vue-router';
import BaseButton from '../ui/BaseButton.vue';
</script>

<template>
    <header class="site-header">
        <a class="brand" href="/" aria-label="Ballburn home">BALLBURN<span>®</span></a>

        <nav class="main-nav" aria-label="Điều hướng chính">
            <RouterLink to="/">Trang chủ</RouterLink>
            <RouterLink to="/about">Về chúng tôi</RouterLink>
            <RouterLink to="/booking">Sân chơi</RouterLink>
            <RouterLink to="/blog">Tin tức</RouterLink>
            <RouterLink to="/contact">Liên hệ</RouterLink>
        </nav>

        <BaseButton to="/booking" size="small">Đặt sân</BaseButton>
    </header>
</template>
