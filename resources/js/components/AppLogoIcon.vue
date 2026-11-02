<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
defineProps<{ className?: HTMLAttributes['class'] }>();
</script>

<template>
    <span :class="className" class="inline-flex items-center justify-center font-black" aria-label="BALLBURN">B</span>
</template>
