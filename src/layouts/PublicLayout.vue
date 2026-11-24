<script setup>
import AppContainer from '../components/layout/AppContainer.vue';
import AppFooter from '../components/layout/AppFooter.vue';
import AppHeader from '../components/layout/AppHeader.vue';
</script>

<template>
    <div class="public-layout">
        <AppHeader />
        <AppContainer>
            <RouterView />
        </AppContainer>
        <AppFooter />
    </div>
</template>
