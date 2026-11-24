<script setup>
import { computed, reactive, ref } from 'vue';
import { RouterLink } from 'vue-router';

document.title = 'Tra cứu đơn hàng | BALLBURN PickleClub & Bistro';
const form = reactive({ booking_code: '', customer_phone: '' });
const result = ref(null);
const loading = ref(false);
const error = ref('');

const statusMeta = computed(() => {
    if (!result.value) return null;
    if (result.value.needs_review && result.value.status === 'PENDING_PAYMENT') return { label: 'Cần hỗ trợ đối soát', tone: 'review' };
    return ({
        PAID: { label: 'Đã thanh toán', tone: 'paid' },
        PENDING_PAYMENT: { label: 'Chờ thanh toán', tone: 'pending' },
        EXPIRED: { label: 'Đã hết hạn', tone: 'expired' },
        CANCELLED: { label: 'Đã hủy', tone: 'cancelled' },
    })[result.value.status] ?? { label: 'Đang xử lý', tone: 'pending' };
});

async function lookup() {
    if (loading.value) return;
    loading.value = true; error.value = ''; result.value = null;
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const response = await fetch('/booking/lookup', {
            method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify(form),
        });
        const payload = await response.json();
        if (!response.ok) throw new Error(payload.message || Object.values(payload.errors ?? {})[0]?.[0] || 'Không thể tra cứu đơn hàng.');
        result.value = payload.data;
    } catch (exception) {
        error.value = exception instanceof Error ? exception.message : 'Không thể tra cứu đơn hàng.';
    } finally { loading.value = false; }
}

function money(value, currency = 'VND') { return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, maximumFractionDigits: 0 }).format(Number(value)); }
function date(value, withTime = false) { return value ? new Intl.DateTimeFormat('vi-VN', { dateStyle: 'medium', ...(withTime ? { timeStyle: 'short' } : {}) }).format(new Date(value)) : '—'; }
function localDate(value) { return new Intl.DateTimeFormat('vi-VN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(`${value}T12:00:00`)); }
async function copyCode() { if (result.value?.booking_code) await navigator.clipboard?.writeText(result.value.booking_code); }
</script>

<template>
    <main class="lookup-page">
        <section class="lookup-hero">
            <div class="lookup-hero-copy"><h1>Tra cứu trạng thái đơn hàng</h1><p>Nhập mã đơn hàng và số điện thoại đặt sân để xem trạng thái thanh toán.</p></div>
            <form class="lookup-form" @submit.prevent="lookup">
                <label><span>Mã đơn hàng</span><input v-model.trim="form.booking_code" required placeholder="VD: BB-20261125-5CRX4G" autocomplete="off" /></label>
                <label><span>Số điện thoại đặt sân</span><input v-model.trim="form.customer_phone" required inputmode="tel" autocomplete="tel" placeholder="VD: 0886 688 614" /></label>
                <button type="submit" :disabled="loading">{{ loading ? 'Đang tra cứu…' : 'Tra cứu' }}</button>
            </form>
        </section>

        <p v-if="error" class="lookup-error" role="alert">{{ error }}</p>
        <section v-if="result" class="lookup-result" aria-live="polite">
            <header class="lookup-status" :class="`is-${statusMeta.tone}`"><span class="lookup-check">{{ result.status === 'PAID' ? '✓' : '!' }}</span><h2>{{ statusMeta.label }}</h2><button type="button" title="Sao chép mã đơn" @click="copyCode">Mã đơn: <strong>{{ result.booking_code }}</strong> ⧉</button></header>
            <div class="lookup-timeline">
                <div class="is-done"><i>✓</i><strong>Đặt sân</strong><span>{{ date(result.created_at, true) }}</span></div>
                <div :class="{ 'is-done': result.status !== 'CANCELLED' }"><i>✓</i><strong>Chờ thanh toán</strong><span>{{ date(result.created_at, true) }}</span></div>
                <div :class="{ 'is-done': result.status === 'PAID' }"><i>{{ result.status === 'PAID' ? '✓' : '3' }}</i><strong>Đã thanh toán</strong><span>{{ date(result.paid_at, true) }}</span></div>
            </div>
            <div class="lookup-bookings">
                <article v-for="(booking, index) in result.bookings" :key="`${booking.court}-${booking.date}-${booking.starts_at}`">
                    <img v-if="booking.image_url" :src="booking.image_url" :alt="booking.court" />
                    <div><small>Sân</small><strong>{{ booking.court }}</strong></div>
                    <div><small>Ngày</small><strong>{{ localDate(booking.date) }}</strong></div>
                    <div><small>Khung giờ</small><strong>{{ booking.starts_at }} – {{ booking.ends_at }}</strong></div>
                    <div><small>Thành tiền</small><strong>{{ money(booking.total_amount, booking.currency) }}</strong></div>
                </article>
            </div>
            <div class="lookup-lower">
                <section><h3>Thông tin người đặt</h3><p>Họ và tên: <strong>{{ result.customer.name }}</strong></p><p>Số điện thoại: <strong>{{ result.customer.phone }}</strong></p><p v-if="result.customer.email">Email: <strong>{{ result.customer.email }}</strong></p></section>
                <aside><h3>ⓘ Lưu ý</h3><p v-if="result.status === 'PAID'"><b>Đã thanh toán:</b> Đơn hàng đã được xác nhận và giữ sân thành công.</p><p v-else-if="result.status === 'PENDING_PAYMENT'"><b>Chờ thanh toán:</b> Vui lòng hoàn tất chuyển khoản trước {{ date(result.payment_deadline_at, true) }}.</p><p v-else><b>{{ statusMeta.label }}:</b> Đơn không còn giữ lịch sân. Hãy liên hệ BALLBURN nếu bạn đã chuyển tiền.</p></aside>
            </div>
            <div class="lookup-actions"><RouterLink to="/booking" class="lookup-action primary">▣ Đặt sân mới</RouterLink><a href="/" class="lookup-action secondary">⌂ Quay về trang chủ</a></div>
        </section>
    </main>
</template>

<style scoped>
.lookup-page{margin-inline:calc(clamp(20px,5vw,76px)*-1);background:#fffefa;color:var(--color-ink)}.lookup-hero{min-height:320px;padding:48px clamp(20px,10vw,180px) 58px;background:linear-gradient(90deg,rgba(222,238,225,.94),rgba(246,248,239,.9)),url('/images/pickleball/court-rally-wide.jpg') center/cover}.lookup-hero-copy{text-align:center}.lookup-hero h1{margin:0;font-size:clamp(2rem,4vw,3.4rem)}.lookup-hero p{color:var(--color-ink-muted)}.lookup-form{display:grid;grid-template-columns:1.2fr 1fr auto;gap:14px;max-width:950px;margin:28px auto 0;padding:24px;border:1px solid var(--color-border);border-radius:18px;background:rgba(255,254,250,.94);box-shadow:0 12px 32px rgba(20,50,38,.09)}.lookup-form label{display:grid;gap:7px;font-size:13px;font-weight:700}.lookup-form input{min-height:46px;padding:0 14px;border:1px solid var(--color-border);border-radius:10px;background:#fff;font:inherit}.lookup-form button{align-self:end;min-height:46px;padding:0 34px;border:0;border-radius:11px;background:#ed5c16;color:#fff;font-weight:800;cursor:pointer}.lookup-form button:disabled{opacity:.6}.lookup-error{max-width:1076px;margin:24px auto;padding:14px 18px;border:1px solid #e9b5b0;border-radius:12px;background:#fff4f2;color:#9e2a20}.lookup-result{max-width:1076px;margin:-22px auto 38px;padding:20px;border:1px solid var(--color-border);border-radius:20px;background:#fffefa;box-shadow:0 15px 38px rgba(20,40,32,.08)}.lookup-status{display:flex;align-items:center;gap:14px;padding:15px;border-radius:12px;background:#eff8ef;color:#147735}.lookup-status.is-pending{background:#fff7df;color:#8a5b00}.lookup-status.is-expired,.lookup-status.is-cancelled{background:#fff0ed;color:#a33a2a}.lookup-status.is-review{background:#fff5df;color:#8a5500}.lookup-status h2{margin:0;font-size:1.35rem}.lookup-status button{margin-left:auto;border:0;background:transparent;color:inherit;cursor:pointer}.lookup-check{display:grid;width:36px;height:36px;place-items:center;border-radius:50%;background:currentColor;color:#fff;font-weight:900}.lookup-timeline{display:grid;grid-template-columns:repeat(3,1fr);gap:0;padding:24px 10%}.lookup-timeline div{position:relative;display:grid;justify-items:center;gap:4px;color:#999;text-align:center}.lookup-timeline div+div:before{position:absolute;right:50%;top:15px;width:100%;height:2px;background:#ddd;content:''}.lookup-timeline .is-done{color:#198c48}.lookup-timeline .is-done:before{background:#198c48}.lookup-timeline i{z-index:1;display:grid;width:31px;height:31px;place-items:center;border-radius:50%;background:#ddd;color:#fff;font-style:normal}.lookup-timeline .is-done i{background:#249c56}.lookup-timeline strong{color:var(--color-ink)}.lookup-timeline span{font-size:12px}.lookup-bookings{display:grid;gap:12px}.lookup-bookings article{display:grid;grid-template-columns:180px repeat(4,1fr);align-items:center;gap:24px;padding:12px;border:1px solid var(--color-border);border-radius:14px}.lookup-bookings img{width:180px;height:108px;border-radius:10px;object-fit:cover}.lookup-bookings article>div{display:grid;gap:5px}.lookup-bookings small{color:var(--color-ink-muted)}.lookup-lower{display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-top:14px}.lookup-lower section,.lookup-lower aside{padding:18px 24px;border-radius:14px;background:#f7f4ef}.lookup-lower aside{background:#eef6e8}.lookup-lower h3{margin:0 0 12px}.lookup-lower p{margin:7px 0;font-size:14px}.lookup-actions{display:flex;justify-content:center;gap:14px;margin-top:24px}.lookup-action{min-width:220px;padding:12px 20px;border:1px solid;border-radius:999px;text-align:center;font-weight:800}.lookup-action.primary{color:#08723a}.lookup-action.secondary{color:#e95816}@media(max-width:850px){.lookup-form{grid-template-columns:1fr}.lookup-bookings article{grid-template-columns:1fr 1fr}.lookup-bookings img{grid-column:1/-1;width:100%;height:180px}.lookup-lower{grid-template-columns:1fr}}@media(max-width:560px){.lookup-hero{padding-inline:20px}.lookup-result{margin:-20px 12px 28px}.lookup-status{align-items:flex-start;flex-wrap:wrap}.lookup-status button{width:100%;margin:0;text-align:left}.lookup-timeline{grid-template-columns:1fr;padding:20px}.lookup-timeline div{grid-template-columns:34px 1fr;justify-items:start;text-align:left}.lookup-timeline div+div:before{left:15px;right:auto;top:-18px;width:2px;height:36px}.lookup-timeline i{grid-row:1/3}.lookup-bookings article{grid-template-columns:1fr}.lookup-bookings img{grid-column:auto}.lookup-actions{flex-direction:column}.lookup-action{min-width:0}}
</style>
