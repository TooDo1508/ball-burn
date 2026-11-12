<script setup>
import { ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import { RouterLink } from 'vue-router';
import BaseButton from '../ui/BaseButton.vue';

const mobileMenuOpen = ref(false);
const route = useRoute();

watch(() => route.fullPath, () => { mobileMenuOpen.value = false; });
</script>

<template>
    <header class="site-header">
        <a class="brand" href="/" aria-label="Ballburn home">BALLBURN<span>®</span></a>

        <nav id="public-mobile-navigation" class="main-nav" :class="{ 'is-open': mobileMenuOpen }" aria-label="Điều hướng chính">
            <a href="/" @click="mobileMenuOpen = false">Trang chủ</a>
            <RouterLink to="/about" @click="mobileMenuOpen = false">Về chúng tôi</RouterLink>
            <RouterLink to="/booking" @click="mobileMenuOpen = false">Đặt sân</RouterLink>
            <RouterLink to="/blog" @click="mobileMenuOpen = false">Tin tức</RouterLink>
            <RouterLink to="/contact" @click="mobileMenuOpen = false">Liên hệ</RouterLink>
        </nav>

        <BaseButton to="/booking" size="small">Đặt sân</BaseButton>
        <button
            type="button"
            class="mobile-menu-toggle"
            :aria-expanded="mobileMenuOpen"
            aria-controls="public-mobile-navigation"
            :aria-label="mobileMenuOpen ? 'Đóng menu' : 'Mở menu'"
            @click="mobileMenuOpen = !mobileMenuOpen"
        >
            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                <path v-if="!mobileMenuOpen" d="M4 6h16M4 12h16M4 18h16" />
                <path v-else d="m6 6 12 12M18 6 6 18" />
            </svg>
        </button>
    </header>
</template>
