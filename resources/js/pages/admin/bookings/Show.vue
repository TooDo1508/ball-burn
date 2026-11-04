<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, CalendarDays, Clock3, CreditCard, Mail, Phone, UserRound } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý đặt sân', href: '/admin/bookings' }, { title: 'Chi tiết booking' }] } });

type BookingDetail = {
    id: number; booking_code: string; status: string; court: string; customer_name: string; customer_email: string; customer_phone: string | null; customer_note: string | null;
    starts_at: string; ends_at: string; payment_deadline_at: string | null; base_amount: string; discount_amount: string; total_amount: string; currency: string;
    payments: { id: number; provider: string; provider_transaction_id: string | null; amount: string; currency: string; status: string; payment_method: string | null; paid_at: string | null }[];
    history: { old_status: string | null; new_status: string; change_source: string; note: string | null; created_at: string | null }[];
};
defineProps<{ booking: BookingDetail }>();
</script>

<template>
    <Head :title="`Booking ${booking.booking_code}`" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <Link href="/admin/bookings" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold hover:border-orange-500"><ArrowLeft class="size-4" />Quay lại danh sách</Link>
            <header class="rounded-xl border border-border bg-card p-5 sm:p-7"><p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-400">CHI TIẾT ĐẶT SÂN</p><h1 class="mt-2 text-2xl font-bold sm:text-3xl">{{ booking.booking_code }}</h1><p class="mt-2 text-sm text-muted-foreground">{{ booking.court }} · {{ booking.starts_at }} – {{ booking.ends_at }}</p><span class="mt-4 inline-flex rounded-full border border-border bg-muted px-3 py-1 text-sm font-semibold">{{ booking.status }}</span></header>
            <div class="grid gap-5 lg:grid-cols-2">
                <section class="rounded-xl border border-border bg-card p-5 sm:p-6"><h2 class="flex items-center gap-2 text-lg font-bold"><UserRound class="size-5 text-orange-600" />Thông tin người đặt</h2><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-muted-foreground">Họ tên</dt><dd class="mt-1 font-semibold">{{ booking.customer_name }}</dd></div><div><dt class="text-muted-foreground">Email</dt><dd class="mt-1 flex items-center gap-2"><Mail class="size-4 text-muted-foreground" />{{ booking.customer_email }}</dd></div><div><dt class="text-muted-foreground">Điện thoại</dt><dd class="mt-1 flex items-center gap-2"><Phone class="size-4 text-muted-foreground" />{{ booking.customer_phone || 'Không cung cấp' }}</dd></div><div v-if="booking.customer_note"><dt class="text-muted-foreground">Ghi chú</dt><dd class="mt-1 whitespace-pre-wrap">{{ booking.customer_note }}</dd></div></dl></section>
                <section class="rounded-xl border border-border bg-card p-5 sm:p-6"><h2 class="flex items-center gap-2 text-lg font-bold"><CalendarDays class="size-5 text-orange-600" />Thông tin đặt sân</h2><dl class="mt-4 grid gap-3 text-sm"><div><dt class="text-muted-foreground">Sân</dt><dd class="mt-1 font-semibold">{{ booking.court }}</dd></div><div><dt class="text-muted-foreground">Thời gian</dt><dd class="mt-1">{{ booking.starts_at }} – {{ booking.ends_at }}</dd></div><div v-if="booking.payment_deadline_at"><dt class="text-muted-foreground">Hạn thanh toán</dt><dd class="mt-1 flex items-center gap-2"><Clock3 class="size-4" />{{ booking.payment_deadline_at }}</dd></div><div class="border-t border-border pt-3"><dt class="text-muted-foreground">Tạm tính · giảm giá</dt><dd class="mt-1">{{ booking.base_amount }} · {{ booking.discount_amount }} {{ booking.currency }}</dd></div><div><dt class="text-muted-foreground">Thành tiền</dt><dd class="mt-1 text-xl font-bold text-orange-700 dark:text-orange-300">{{ booking.total_amount }} {{ booking.currency }}</dd></div></dl></section>
            </div>
            <section class="rounded-xl border border-border bg-card p-5 sm:p-6"><h2 class="flex items-center gap-2 text-lg font-bold"><CreditCard class="size-5 text-orange-600" />Thanh toán</h2><div v-if="!booking.payments.length" class="mt-4 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">Chưa có giao dịch thanh toán được ghi nhận.</div><div v-else class="mt-4 overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead class="text-xs uppercase text-muted-foreground"><tr><th class="py-2">Nguồn</th><th class="py-2">Mã giao dịch</th><th class="py-2">Số tiền</th><th class="py-2">Trạng thái</th><th class="py-2">Thời điểm</th></tr></thead><tbody class="divide-y divide-border"><tr v-for="payment in booking.payments" :key="payment.id"><td class="py-3">{{ payment.payment_method || payment.provider }}</td><td class="py-3 break-all">{{ payment.provider_transaction_id || '—' }}</td><td class="py-3">{{ payment.amount }} {{ payment.currency }}</td><td class="py-3">{{ payment.status }}</td><td class="py-3">{{ payment.paid_at || '—' }}</td></tr></tbody></table></div></section>
            <section class="rounded-xl border border-border bg-card p-5 sm:p-6"><h2 class="text-lg font-bold">Lịch sử trạng thái</h2><ol class="mt-4 space-y-4"> <li v-for="(item, index) in booking.history" :key="`${item.created_at}-${index}`" class="border-l-2 border-orange-500/50 pl-4"><p class="font-semibold">{{ item.old_status || 'Mới tạo' }} → {{ item.new_status }}</p><p class="mt-1 text-xs text-muted-foreground">{{ item.created_at }} · {{ item.change_source }}</p><p v-if="item.note" class="mt-1 text-sm text-muted-foreground">{{ item.note }}</p></li><li v-if="!booking.history.length" class="text-sm text-muted-foreground">Chưa có lịch sử chuyển trạng thái.</li></ol></section>
        </div>
    </main>
</template>
