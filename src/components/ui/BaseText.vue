<script setup>
defineProps({
    as: { type: String, default: 'p' },
    variant: { type: String, default: 'body' },
});
</script>

<template>
    <component :is="as" class="base-text" :class="`base-text--${variant}`">
        <slot />
    </component>
</template>
