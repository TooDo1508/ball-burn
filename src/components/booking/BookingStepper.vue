<script setup>
defineProps({ current: { type: Number, default: 1 }, complete: { type: Boolean, default: false } });
const emit = defineEmits(['navigate']);

const steps = ['Chọn sân', 'Thông tin', 'Thanh toán', 'Xác nhận'];
</script>

<template>
    <ol class="booking-stepper" aria-label="Các bước đặt sân">
        <li v-for="(step, index) in steps" :key="step" :class="{ 'is-current': current === index + 1 && !complete, 'is-complete': complete || current > index + 1 }">
            <button v-if="!complete && index + 1 < current" type="button" class="booking-step-number booking-step-jump" :aria-label="`Quay lại bước ${index + 1}: ${step}`" @click="emit('navigate', index + 1)">{{ index + 1 }}</button>
            <span v-else class="booking-step-number">{{ complete || current > index + 1 ? '✓' : index + 1 }}</span>
            <span>{{ step }}</span>
        </li>
    </ol>
</template>
