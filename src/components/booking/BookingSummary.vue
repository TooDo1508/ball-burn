<script setup>
defineProps({
    court: { type: Object, default: null },
    date: { type: String, default: '' },
    startsAt: { type: String, default: '' },
    endsAt: { type: String, default: '' },
    total: { type: [Number, String], default: 0 },
    items: { type: Array, default: () => [] },
    submitting: { type: Boolean, default: false },
    complete: { type: Boolean, default: false },
    buttonLabel: { type: String, default: 'Tiếp tục' },
    buttonDisabled: { type: Boolean, default: false },
    note: { type: String, default: 'Chọn sân, ngày và khung giờ bạn muốn đặt.' },
    completeLabel: { type: String, default: 'Đã gửi yêu cầu booking' },
});
const emit = defineEmits(['submit']);

function formatMoney(amount, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2 }).format(Number(amount));
}

function formatDate(value) {
    if (!value) return 'Chưa chọn ngày';
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('vi-VN', { weekday: 'short', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day, 12));
}
</script>

<template>
    <aside class="booking-summary" aria-label="Tóm tắt booking">
        <p class="booking-summary-kicker">TÓM TẮT ĐẶT SÂN</p>
        <h2>Booking của bạn</h2>
        <div v-if="court" class="booking-summary-court">
            <img v-if="court.image_url" :src="court.image_url" :alt="court.name" />
            <div><strong>{{ court.name }}</strong><span>{{ court.surface_type || 'Sân pickleball' }}</span></div>
        </div>
        <div v-if="items.length" class="booking-summary-items">
            <div v-for="(item, index) in items" :key="`${item.court_name}-${item.starts_at}-${index}`">
                <strong>{{ item.court_name }} · {{ item.starts_at }}–{{ item.ends_at }}</strong>
                <span>{{ item.slot_count }} slot · {{ formatMoney(item.total, item.currency) }}</span>
            </div>
        </div>
        <dl class="booking-summary-details">
            <div><dt>Ngày</dt><dd>{{ formatDate(date) }}</dd></div>
            <div><dt>Thời gian</dt><dd>{{ items.length ? `${items.reduce((count, item) => count + item.slot_count, 0)} khung giờ · ${items.length} lượt` : startsAt && endsAt ? `${startsAt} – ${endsAt}` : 'Chưa chọn giờ' }}</dd></div>
        </dl>
        <div class="booking-summary-total"><span>Tổng tiền</span><strong>{{ court ? formatMoney(total, court.currency) : items.length ? formatMoney(total, items[0].currency) : '—' }}</strong></div>
        <p class="booking-summary-note">{{ note }}</p>
        <button v-if="!complete" type="button" class="button button--primary booking-submit" :disabled="submitting || buttonDisabled" @click="emit('submit')">
            {{ submitting ? 'Đang xử lý…' : buttonLabel }} <span aria-hidden="true">→</span>
        </button>
        <div v-else class="booking-confirmed-note"><span aria-hidden="true">✓</span><strong>{{ completeLabel }}</strong></div>
    </aside>
</template>
