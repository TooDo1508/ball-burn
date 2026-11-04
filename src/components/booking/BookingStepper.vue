<script setup>
defineProps({ current: { type: Number, default: 1 }, complete: { type: Boolean, default: false } });

const steps = ['Chọn sân', 'Ngày & giờ', 'Thông tin', 'Xác nhận'];
</script>

<template>
    <ol class="booking-stepper" aria-label="Các bước đặt sân">
        <li v-for="(step, index) in steps" :key="step" :class="{ 'is-current': current === index + 1 && !complete, 'is-complete': complete || current > index + 1 }">
            <span class="booking-step-number">{{ complete || current > index + 1 ? '✓' : index + 1 }}</span>
            <span>{{ step }}</span>
        </li>
    </ol>
</template>
