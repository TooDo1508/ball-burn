<script setup>
defineProps({
    slots: { type: Array, default: () => [] },
    selectedTimes: { type: Array, default: () => [] },
    currency: { type: String, default: 'VND' },
    loading: { type: Boolean, default: false },
});
const emit = defineEmits(['select']);

function formatMoney(amount, currency) {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(amount));
}
</script>

<template>
    <div v-if="slots.length" class="booking-slot-grid" aria-label="Các khung giờ sân" :aria-busy="loading">
        <button
            v-for="slot in slots"
            :key="slot.starts_at"
            type="button"
            class="booking-slot"
            :class="[`is-${slot.status}`, { 'is-selected': selectedTimes.includes(slot.starts_at) }]"
            :disabled="loading || slot.status !== 'available'"
            :aria-pressed="selectedTimes.includes(slot.starts_at)"
            @click="emit('select', slot.starts_at)"
        >
            <span>{{ slot.starts_at }} – {{ slot.ends_at }}</span>
            <small v-if="slot.status === 'available'">{{ formatMoney(slot.amount, currency) }}</small>
            <small v-else-if="slot.status === 'booked'">Đã có người đặt</small>
            <small v-else-if="slot.status === 'closed'">Đã khóa</small>
            <small v-else>Đã qua</small>
        </button>
    </div>
    <p v-else class="booking-empty-slots">Sân đóng cửa trong ngày đã chọn.</p>
</template>
