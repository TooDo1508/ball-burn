<script setup>
import { RouterLink } from 'vue-router';
import BaseButton from '../ui/BaseButton.vue';
</script>

<template>
    <header class="site-header">
        <RouterLink class="brand" to="/" aria-label="Ballburn home">BALLBURN<span>®</span></RouterLink>

        <nav class="main-nav" aria-label="Điều hướng chính">
            <RouterLink to="/booking">Đặt sân</RouterLink>
            <RouterLink to="/about">Về chúng tôi</RouterLink>
            <RouterLink to="/blog">Tin tức</RouterLink>
            <RouterLink to="/contact">Liên hệ</RouterLink>
        </nav>

        <BaseButton to="/booking" size="small">Đặt sân</BaseButton>
    </header>
</template>
