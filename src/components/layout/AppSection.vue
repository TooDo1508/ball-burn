<script setup>
defineProps({
    as: { type: String, default: 'section' },
    id: { type: String, default: null },
});
</script>

<template>
    <component :is="as" :id="id" class="app-section">
        <slot />
    </component>
</template>
