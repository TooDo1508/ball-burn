<script setup>
import { RouterLink } from 'vue-router';

defineProps({
    to: { type: [String, Object], default: null },
    href: { type: String, default: null },
    variant: { type: String, default: 'primary' },
    size: { type: String, default: 'default' },
    type: { type: String, default: 'button' },
});
</script>

<template>
    <RouterLink v-if="to" :to="to" class="button" :class="[`button--${variant}`, `button--${size}`]">
        <slot />
    </RouterLink>
    <a v-else-if="href" :href="href" class="button" :class="[`button--${variant}`, `button--${size}`]">
        <slot />
    </a>
    <button v-else :type="type" class="button" :class="[`button--${variant}`, `button--${size}`]">
        <slot />
    </button>
</template>
