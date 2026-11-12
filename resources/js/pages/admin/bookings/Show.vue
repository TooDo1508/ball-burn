<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Clock3, CreditCard, Mail, Phone, UserRound, X } from '@lucide/vue';
import { ref } from 'vue';
import { confirmAction } from '@/lib/confirmDialog';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý đặt sân', href: '/admin/bookings' }, { title: 'Chi tiết booking' }] } });

type BookingDetail = {
    id: number;
    booking_code: string;
    status: 'PENDING_PAYMENT' | 'PAID' | 'CANCELLED';
    status_options: { value: string; label: string }[];
    court: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    customer_note: string | null;
    starts_at: string;
    ends_at: string;
    payment_deadline_at: string | null;
    base_amount: string;
    discount_amount: string;
    total_amount: string;
    currency: string;
    slots: { court: string; starts_at: string; ends_at: string; amount: string }[];
    payments: { id: number; provider: string; provider_transaction_id: string | null; payment_reference: string | null; proof_url: string | null; amount: string; currency: string; payment_method: string | null; paid_at: string | null }[];
    history: { old_status: string | null; new_status: string; change_source: string; note: string | null; created_at: string | null }[];
};

const props = defineProps<{ booking: BookingDetail }>();
const statusForm = useForm({ status: props.booking.status_options[0]?.value ?? '', note: '' });
const statusLabels = { PENDING_PAYMENT: 'Ch\u1edd thanh to\u00e1n', PAID: '\u0110\u00e3 thanh to\u00e1n', CANCELLED: 'Đã hủy sân' } as const;
const selectedProof = ref<{ url: string; label: string } | null>(null);

function formatMoney(value: string | number, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value));
}

async function updateStatus() {
    if (statusForm.status === 'CANCELLED' && !await confirmAction({
        title: 'Hủy đơn đặt sân?',
        description: 'Lịch sân sẽ được giải phóng. Nếu đơn đã thanh toán, việc hoàn tiền cần xử lý riêng.',
        confirmLabel: 'Hủy sân',
        destructive: true,
    })) return;

    statusForm.put(`/admin/bookings/${props.booking.id}/status`, {
        preserveScroll: true,
        onSuccess: () => { statusForm.note = ''; },
    });
}
</script>

<template>
    <Head :title="`Booking ${booking.booking_code}`" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8" @keydown.esc="selectedProof = null">
        <div class="mx-auto max-w-5xl space-y-6">
            <Link href="/admin/bookings" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold hover:border-orange-500"><ArrowLeft class="size-4" />Quay lại danh sách</Link>

            <header class="rounded-xl border border-border bg-card p-5 sm:p-7">
                <p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-400">CHI TIẾT ĐƠN ĐẶT SÂN</p>
                <div class="mt-2 flex flex-wrap items-start justify-between gap-4">
                    <div><h1 class="text-2xl font-bold sm:text-3xl">{{ booking.booking_code }}</h1><p class="mt-2 text-sm text-muted-foreground">{{ booking.court }} · {{ booking.slots.length }} khung giờ</p></div>
                    <span :class="['inline-flex rounded-full border px-3 py-1.5 text-sm font-semibold', booking.status === 'CANCELLED' ? 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-300' : 'border-border bg-muted']">{{ statusLabels[booking.status] }}</span>
                </div>
            </header>

            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl border border-border bg-card p-5 sm:p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold"><UserRound class="size-5 text-orange-600" />Thông tin người đặt</h2>
                    <dl class="mt-4 grid gap-3 text-sm">
                        <div><dt class="text-muted-foreground">Họ tên</dt><dd class="mt-1 font-semibold">{{ booking.customer_name }}</dd></div>
                        <div><dt class="text-muted-foreground">Email</dt><dd class="mt-1 flex items-center gap-2"><Mail class="size-4 text-muted-foreground" />{{ booking.customer_email }}</dd></div>
                        <div><dt class="text-muted-foreground">Điện thoại</dt><dd class="mt-1 flex items-center gap-2"><Phone class="size-4 text-muted-foreground" />{{ booking.customer_phone || 'Không cung cấp' }}</dd></div>
                        <div v-if="booking.customer_note"><dt class="text-muted-foreground">Ghi chú</dt><dd class="mt-1 whitespace-pre-wrap">{{ booking.customer_note }}</dd></div>
                    </dl>
                </section>

                <section class="rounded-xl border border-border bg-card p-5 sm:p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold"><CalendarDays class="size-5 text-orange-600" />Thông tin đặt sân</h2>
                    <div class="mt-4 divide-y divide-border text-sm">
                        <div v-for="(slot, index) in booking.slots" :key="`${slot.starts_at}-${index}`" class="flex justify-between gap-3 py-3 first:pt-0">
                            <div><p class="font-semibold">{{ slot.court }}</p><p class="mt-1 text-muted-foreground">{{ slot.starts_at }} – {{ slot.ends_at }}</p></div>
                            <span class="whitespace-nowrap font-medium">{{ formatMoney(slot.amount, booking.currency) }}</span>
                        </div>
                    </div>
                    <dl class="mt-2 grid gap-3 text-sm">
                        <div v-if="booking.payment_deadline_at"><dt class="text-muted-foreground">Hạn thanh toán</dt><dd class="mt-1 flex items-center gap-2"><Clock3 class="size-4" />{{ booking.payment_deadline_at }}</dd></div>
                        <div class="border-t border-border pt-3"><dt class="text-muted-foreground">Tạm tính · giảm giá</dt><dd class="mt-1">{{ formatMoney(booking.base_amount, booking.currency) }} · {{ formatMoney(booking.discount_amount, booking.currency) }}</dd></div>
                        <div><dt class="text-muted-foreground">Thành tiền toàn đơn</dt><dd class="mt-1 text-xl font-bold text-orange-700 dark:text-orange-300">{{ formatMoney(booking.total_amount, booking.currency) }}</dd></div>
                    </dl>
                </section>
            </div>

            <section class="rounded-xl border border-border bg-card p-5 sm:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 class="text-lg font-bold">Cập nhật trạng thái</h2>
                    <p class="text-sm text-muted-foreground">Hủy đơn sẽ giải phóng lịch sân; việc hoàn tiền cần xử lý riêng.</p>
                </div>
                <form v-if="booking.status_options.length" class="mt-4 grid gap-3 sm:grid-cols-[minmax(180px,240px)_1fr_auto] sm:items-start" @submit.prevent="updateStatus">
                    <label class="grid gap-1.5 text-sm font-medium">Trạng thái mới
                        <select v-model="statusForm.status" class="min-h-10 rounded-lg border border-input bg-background px-3 text-foreground" required>
                            <option v-for="option in booking.status_options" :key="option.value" :value="option.value">{{ option.label }}</option>
                        </select>
                    </label>
                    <label class="grid gap-1.5 text-sm font-medium">{{ statusForm.status === 'CANCELLED' ? 'Lý do hủy (bắt buộc)' : 'Ghi chú nội bộ (không bắt buộc)' }}
                        <input v-model="statusForm.note" maxlength="500" :required="statusForm.status === 'CANCELLED'" class="min-h-10 rounded-lg border border-input bg-background px-3 text-foreground" :placeholder="statusForm.status === 'CANCELLED' ? 'Nhập lý do hủy sân' : 'Lý do cập nhật trạng thái'" />
                    </label>
                    <button type="submit" :disabled="statusForm.processing" class="mt-6 inline-flex min-h-10 items-center justify-center rounded-lg bg-orange-600 px-4 text-sm font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-60">{{ statusForm.processing ? 'Đang lưu…' : 'Lưu trạng thái' }}</button>
                    <p v-if="statusForm.errors.status" class="text-sm text-red-600 sm:col-span-3">{{ statusForm.errors.status }}</p>
                    <p v-if="statusForm.errors.note" class="text-sm text-red-600 sm:col-span-3">{{ statusForm.errors.note }}</p>
                </form>
                <p v-else class="mt-3 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">Không có trạng thái nào khác để chuyển.</p>
            </section>

            <section class="rounded-xl border border-border bg-card p-5 sm:p-6">
                <h2 class="flex items-center gap-2 text-lg font-bold"><CreditCard class="size-5 text-orange-600" />Thanh toán</h2>
                <div v-if="!booking.payments.length" class="mt-4 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">Chưa có giao dịch thanh toán được ghi nhận.</div>
                <div v-else class="mt-4 overflow-x-auto">
                    <table class="w-full min-w-[650px] text-left text-sm">
                        <thead class="text-xs uppercase text-muted-foreground"><tr><th class="py-2">Nguồn</th><th class="py-2">Mã tham chiếu</th><th class="py-2">Mã giao dịch</th><th class="py-2">Số tiền</th><th class="py-2">Ảnh chuyển khoản</th><th class="py-2">Thời điểm</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="payment in booking.payments" :key="payment.id">
                                <td class="py-3">{{ payment.payment_method || payment.provider }}</td><td class="py-3 break-all">{{ payment.payment_reference || '—' }}</td><td class="py-3 break-all">{{ payment.provider_transaction_id || '—' }}</td><td class="py-3">{{ formatMoney(payment.amount, payment.currency) }}</td>
                                <td class="py-3">
                                    <button v-if="payment.proof_url" type="button" class="group flex items-center gap-2 text-left" @click="selectedProof = { url: payment.proof_url, label: `Ảnh thanh toán ${payment.id}` }">
                                        <img :src="payment.proof_url" :alt="`Ảnh chuyển khoản booking ${booking.booking_code}`" class="size-14 rounded-md border border-border object-cover transition group-hover:opacity-80" loading="lazy" />
                                        <span class="font-semibold text-orange-700 underline dark:text-orange-400">Xem ảnh</span>
                                    </button>
                                    <span v-else class="text-muted-foreground">Chưa gửi</span>
                                </td>
                                <td class="py-3">{{ payment.paid_at || '—' }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="rounded-xl border border-border bg-card p-5 sm:p-6">
                <h2 class="text-lg font-bold">Lịch sử trạng thái</h2>
                <ol class="mt-4 space-y-4">
                    <li v-for="(item, index) in booking.history" :key="`${item.created_at}-${index}`" class="border-l-2 border-orange-500/50 pl-4">
                        <p class="font-semibold">{{ item.old_status || 'Mới tạo' }} →︎ {{ item.new_status }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.created_at }} · {{ item.change_source }}</p><p v-if="item.note" class="mt-1 text-sm text-muted-foreground">{{ item.note }}</p>
                    </li>
                    <li v-if="!booking.history.length" class="text-sm text-muted-foreground">Chưa có lịch sử chuyển trạng thái.</li>
                </ol>
            </section>
        </div>

        <div v-if="selectedProof" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" :aria-label="selectedProof.label" @click.self="selectedProof = null">
            <div class="relative flex max-h-full max-w-5xl flex-col items-center">
                <button type="button" class="absolute -right-3 -top-3 z-10 inline-flex size-10 items-center justify-center rounded-full bg-card text-foreground shadow-lg" aria-label="Đóng ảnh" @click="selectedProof = null"><X class="size-5" /></button>
                <img :src="selectedProof.url" :alt="selectedProof.label" class="max-h-[85vh] max-w-full rounded-lg object-contain" />
                <p class="mt-3 rounded-full bg-black/60 px-3 py-1 text-sm text-white">{{ selectedProof.label }}</p>
            </div>
        </div>
    </main>
</template>
