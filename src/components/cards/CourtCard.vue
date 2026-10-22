<script setup>
import BaseButton from '../ui/BaseButton.vue';

defineProps({
    court: { type: Object, required: true },
});
</script>

<template>
    <article class="base-card court-card">
        <div class="court-card-art" aria-hidden="true">
            <span class="mini-court-lines"></span>
            <span class="mini-court-net"></span>
        </div>
        <div class="court-card-info">
            <div>
                <span class="card-number">SÂN {{ court.number }}</span>
                <h3>{{ court.name }}</h3>
                <p>{{ court.surface }}</p>
            </div>
            <BaseButton :to="court.to ?? '/booking'" variant="outline" size="small">
                Đặt sân <span aria-hidden="true">↗</span>
            </BaseButton>
        </div>
    </article>
</template>
