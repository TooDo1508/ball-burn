<script setup>
import AppContainer from '../components/layout/AppContainer.vue';
import AppFooter from '../components/layout/AppFooter.vue';
import AppHeader from '../components/layout/AppHeader.vue';
</script>

<template>
    <AppContainer>
        <AppHeader />
        <RouterView />
        <AppFooter />
    </AppContainer>
</template>
