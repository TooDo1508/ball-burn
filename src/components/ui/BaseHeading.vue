<script setup>
defineProps({
    as: { type: String, default: 'h2' },
    size: { type: String, default: 'section' },
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    id: { type: String, default: null },
});
</script>

<template>
    <div class="heading-block" :class="`heading-block--${size}`">
        <p v-if="eyebrow" class="eyebrow">{{ eyebrow }}</p>
        <component :is="as" :id="id" class="heading-title">{{ title }}</component>
        <p v-if="description" class="heading-description">{{ description }}</p>
    </div>
</template>
