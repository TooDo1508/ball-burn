<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Banknote, CalendarDays, CalendarX, CheckCircle2, ChevronDown, Clock3, CreditCard, Mail, Phone, RotateCcw, Search, Trash2, UserRound, X } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { confirmAction } from '@/lib/confirmDialog';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý đặt sân', href: '/admin/bookings' }] } });

type BookingStatus = 'PENDING_PAYMENT' | 'PAID' | 'CANCELLED';
type BookingPayment = { id: number; booking_id: number; provider: string; payment_reference: string | null; proof_url: string | null; amount: string; currency: string; payment_method: string | null };
type BookingDetail = { id: number; booking_code: string; status: BookingStatus; status_options: { value: string; label: string }[]; court: string; customer_name: string; customer_email: string; customer_phone: string | null; customer_note: string | null; payment_deadline_at: string | null; base_amount: string; discount_amount: string; total_amount: string; currency: string; slots: { court: string; starts_at: string; ends_at: string; amount: string }[]; payments: BookingPayment[]; history: { old_status: string | null; new_status: string; change_source: string; note: string | null; created_at: string | null }[] };
type BookingRow = { id: number; order_id: number; booking_code: string; court: string; slots: { court: string; starts_at: string; ends_at: string }[]; customer_name: string; customer_email: string; customer_phone: string | null; starts_at: string; ends_at: string; total_amount: string; currency: string; status: BookingStatus; payment_deadline_at: string | null; created_at: string | null; detail: BookingDetail };
type PageLink = { url: string | null; label: string; active: boolean };
type BookingPage = { data: BookingRow[]; links: PageLink[]; from: number | null; to: number | null; total: number };

const props = defineProps<{
    bookings: BookingPage;
    canDeleteBookings: boolean;
    stats: { total: number; pending: number; paid_bookings: number; cancelled: number; revenue_today: { currency: string; amount: string }[] };
    filters: { search: string; court_id: number | string; status: string; from: string; to: string; per_page: number };
    courts: { id: number; name: string }[];
}>();

const filters = reactive({ ...props.filters });
const selectedBookingId = ref<number | null>(null);
const selectedProof = ref<{ url: string; label: string } | null>(null);
const dateRangeOpen = ref(false);
const selectedOrderIds = ref<number[]>([]);
const bulkStatus = ref<BookingStatus>('PAID');
const bulkError = ref('');
const selectedBooking = computed(() => props.bookings.data.find((booking) => booking.id === selectedBookingId.value)?.detail ?? null);
const detailStatusForm = useForm({ status: '', note: '' });
const visibleOrderIds = computed(() => props.bookings.data.filter((booking) => booking.status !== 'CANCELLED').map((booking) => booking.order_id));
const allVisibleSelected = computed(() => visibleOrderIds.value.length > 0 && visibleOrderIds.value.every((id) => selectedOrderIds.value.includes(id)));
const someVisibleSelected = computed(() => visibleOrderIds.value.some((id) => selectedOrderIds.value.includes(id)));
const ineligibleForBulkDelete = computed(() => props.bookings.data.some((booking) => selectedOrderIds.value.includes(booking.order_id) && booking.status !== 'PENDING_PAYMENT'));
const dateRangeLabel = computed(() => {
    if (!filters.from && !filters.to) return 'Khoảng ngày sân';
    const format = (value: string) => value ? new Intl.DateTimeFormat('vi-VN').format(new Date(`${value}T00:00:00`)) : '';
    if (filters.from && filters.to) return `${format(filters.from)} – ${format(filters.to)}`;
    return filters.from ? `Từ ${format(filters.from)}` : `Đến ${format(filters.to)}`;
});

function openBooking(booking: BookingRow) {
    selectedBookingId.value = booking.id;
    detailStatusForm.status = booking.detail.status_options[0]?.value ?? '';
    detailStatusForm.note = '';
}

async function saveBookingStatus() {
    if (!selectedBooking.value) return;
    if (detailStatusForm.status === 'CANCELLED' && !await confirmAction({
        title: 'Hủy đơn đặt sân?',
        description: 'Lịch sân sẽ được giải phóng. Nếu đơn đã thanh toán, việc hoàn tiền cần xử lý riêng.',
        confirmLabel: 'Hủy sân',
        destructive: true,
    })) return;

    detailStatusForm.put(`/admin/bookings/${selectedBooking.value.id}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            detailStatusForm.note = '';
            detailStatusForm.status = selectedBooking.value?.status_options[0]?.value ?? '';
        },
    });
}

const bookingLabels: Record<BookingStatus, string> = { PENDING_PAYMENT: 'Ch\u1edd thanh to\u00e1n', PAID: '\u0110\u00e3 thanh to\u00e1n', CANCELLED: 'Đã hủy sân' };
const bookingClasses: Record<BookingStatus, string> = { PENDING_PAYMENT: 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300', PAID: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300', CANCELLED: 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-300' };

function money(value: string | number, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency }).format(Number(value));
}

function applyFilters() {
    selectedOrderIds.value = [];
    dateRangeOpen.value = false;
    router.get('/admin/bookings', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', court_id: '', status: '', from: '', to: '', per_page: 10 });
    applyFilters();
}

function toggleOrder(orderId: number, event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedOrderIds.value = checked
        ? [...new Set([...selectedOrderIds.value, orderId])]
        : selectedOrderIds.value.filter((id) => id !== orderId);
}

function toggleVisibleOrders(event: Event) {
    const checked = (event.target as HTMLInputElement).checked;
    selectedOrderIds.value = checked
        ? [...new Set([...selectedOrderIds.value, ...visibleOrderIds.value])]
        : selectedOrderIds.value.filter((id) => !visibleOrderIds.value.includes(id));
}

function clearSelection() {
    selectedOrderIds.value = [];
    bulkError.value = '';
}

function updateSelectedStatus() {
    if (!selectedOrderIds.value.length) return;
    bulkError.value = '';
    router.put('/admin/bookings/bulk/status', {
        booking_order_ids: selectedOrderIds.value,
        status: bulkStatus.value,
    }, {
        preserveScroll: true,
        onSuccess: () => { clearSelection(); },
        onError: (errors) => { bulkError.value = errors.booking_order_ids || errors.status || ''; },
    });
}

async function deleteSelectedOrders() {
    if (!selectedOrderIds.value.length || ineligibleForBulkDelete.value) return;
    if (!await confirmAction({
        title: 'Xóa đơn đặt sân?',
        description: `Xóa ${selectedOrderIds.value.length} đơn đang chờ thanh toán? Thao tác này không thể hoàn tác.`,
        confirmLabel: 'Xóa đơn',
        destructive: true,
    })) return;

    bulkError.value = '';
    router.delete('/admin/bookings/bulk', {
        data: { booking_order_ids: selectedOrderIds.value },
        preserveScroll: true,
        onSuccess: () => { clearSelection(); },
        onError: (errors) => { bulkError.value = errors.booking_order_ids || ''; },
    });
}

function revenueTodayLabel() {
    if (!props.stats.revenue_today.length) return money(0, 'VND');
    return props.stats.revenue_today.map((entry) => money(entry.amount, entry.currency)).join(' · ');
}

function pageLabel(label: string) {
    return label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}

</script>

<template>
    <Head :title="filters.status === 'CANCELLED' ? 'Danh sách hủy sân' : 'Quản lý đặt sân'" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1600px] space-y-6">
            <header class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN · VẬN HÀNH</p>
                    <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><component :is="filters.status === 'CANCELLED' ? CalendarX : CalendarDays" class="size-7 text-orange-700 dark:text-orange-400" />{{ filters.status === 'CANCELLED' ? 'Danh sách hủy sân' : 'Danh sách đặt sân' }}</h1>
                    <p class="mt-2 text-sm text-muted-foreground">{{ filters.status === 'CANCELLED' ? 'Tra cứu các đơn quản trị viên đã hủy và lý do lưu trong lịch sử.' : 'Theo dõi thông tin đơn, lịch sân và trạng thái thanh toán.' }}</p>
                </div>
            </header>


            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Booking totals">
                <article class="rounded-xl border border-border bg-card p-4"><div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">T&#7893;ng &#273;&#417;n</p><span class="grid size-9 place-items-center rounded-lg bg-orange-500/10 text-orange-700 dark:text-orange-400"><CalendarDays class="size-5" /></span></div><p class="mt-3 text-2xl font-bold">{{ stats.total }}</p></article>
                <article class="rounded-xl border border-border bg-card p-4"><div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">Ch&#7901; thanh to&#225;n</p><span class="grid size-9 place-items-center rounded-lg bg-amber-500/10 text-amber-700 dark:text-amber-300"><Clock3 class="size-5" /></span></div><p class="mt-3 text-2xl font-bold">{{ stats.pending }}</p></article>
                <article class="rounded-xl border border-border bg-card p-4"><div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">&#272;&#227; thanh to&#225;n</p><span class="grid size-9 place-items-center rounded-lg bg-emerald-500/10 text-emerald-700 dark:text-emerald-300"><CheckCircle2 class="size-5" /></span></div><p class="mt-3 text-2xl font-bold">{{ stats.paid_bookings }}</p></article>
                <article class="rounded-xl border border-border bg-card p-4"><div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">Đã hủy sân</p><span class="grid size-9 place-items-center rounded-lg bg-rose-500/10 text-rose-700 dark:text-rose-300"><CalendarX class="size-5" /></span></div><p class="mt-3 text-2xl font-bold">{{ stats.cancelled }}</p></article>
                <article class="rounded-xl border border-border bg-card p-4"><div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">Doanh thu h&#244;m nay</p><span class="grid size-9 place-items-center rounded-lg bg-orange-500/10 text-orange-700 dark:text-orange-400"><Banknote class="size-5" /></span></div><p class="mt-3 text-xl font-bold">{{ revenueTodayLabel() }}</p></article>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <form v-if="!selectedOrderIds.length" class="grid min-h-[78px] items-end gap-3 border-b border-border p-4 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.4fr)_170px_180px_minmax(250px,1fr)_auto_auto]" @submit.prevent="applyFilters">
                    <label class="relative block"><span class="sr-only">T&#236;m booking</span><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><input v-model="filters.search" type="search" placeholder="M&#227; &#273;&#417;n, t&#234;n, S&#272;T, email..." class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-3 text-sm outline-none focus:border-orange-500" /></label>
                    <label><span class="sr-only">L&#7885;c s&#226;n</span><select v-model="filters.court_id" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="">T&#7845;t c&#7843; s&#226;n</option><option v-for="court in courts" :key="court.id" :value="court.id">{{ court.name }}</option></select></label>
                    <label><span class="sr-only">Tr&#7841;ng th&#225;i &#273;&#417;n</span><select v-model="filters.status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="">T&#7845;t c&#7843; tr&#7841;ng th&#225;i</option><option value="PENDING_PAYMENT">Ch&#7901; thanh to&#225;n</option><option value="PAID">&#272;&#227; thanh to&#225;n</option><option value="CANCELLED">Đã hủy sân</option></select></label>
                    <div class="relative">
                        <span class="sr-only">Kho&#7843;ng ng&#224;y s&#226;n</span>
                        <button type="button" class="flex h-10 w-full items-center justify-between gap-2 rounded-lg border border-input bg-background px-3 text-left text-sm hover:border-orange-500" aria-haspopup="dialog" :aria-expanded="dateRangeOpen" @click="dateRangeOpen = !dateRangeOpen"><span class="flex min-w-0 items-center gap-2"><CalendarDays class="size-4 shrink-0 text-muted-foreground" /><span class="truncate">{{ dateRangeLabel }}</span></span><ChevronDown class="size-4 shrink-0 text-muted-foreground" /></button>
                        <div v-if="dateRangeOpen" role="dialog" aria-label="Kho&#7843;ng ng&#224;y s&#226;n" class="absolute left-0 top-12 z-30 w-[min(360px,calc(100vw-2rem))] rounded-xl border border-border bg-card p-4 shadow-xl" @click.stop>
                            <div class="grid gap-3 sm:grid-cols-2"><input v-model="filters.from" type="date" aria-label="T&#7915; ng&#224;y" class="h-10 min-w-0 rounded-lg border border-input bg-background px-2 text-sm" /><input v-model="filters.to" type="date" aria-label="&#272;&#7871;n ng&#224;y" class="h-10 min-w-0 rounded-lg border border-input bg-background px-2 text-sm" /></div>
                            <div class="mt-3 flex justify-end gap-2"><button type="button" class="h-9 rounded-lg px-3 text-sm font-medium text-muted-foreground hover:bg-muted" @click="filters.from = ''; filters.to = ''">X&#243;a</button><button type="button" class="h-9 rounded-lg bg-orange-600 px-3 text-sm font-semibold text-white hover:bg-orange-700" @click="dateRangeOpen = false">Xong</button></div>
                        </div>
                    </div>
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600"><Search class="size-4" />L&#7885;c</button>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold hover:border-orange-500" @click="resetFilters"><RotateCcw class="size-4" />X&#243;a l&#7885;c</button>
                </form>

                <div v-if="selectedOrderIds.length" class="flex min-h-[78px] items-center justify-end border-b border-border p-3">
                    <div class="flex max-w-full flex-wrap items-center justify-end gap-2 rounded-xl border border-border bg-muted/30 p-2">
                        <span class="px-2 text-sm font-semibold">{{ selectedOrderIds.length }} &#273;&#417;n &#273;&#227; ch&#7885;n</span>
                        <select v-model="bulkStatus" class="h-9 rounded-lg border border-input bg-background px-3 text-sm"><option value="PENDING_PAYMENT">Ch&#7901; thanh to&#225;n</option><option value="PAID">&#272;&#227; thanh to&#225;n</option></select>
                        <button type="button" :disabled="!selectedOrderIds.length" class="h-9 rounded-lg bg-orange-600 px-3 text-sm font-bold text-white hover:bg-orange-700 disabled:cursor-not-allowed disabled:opacity-40" @click="updateSelectedStatus">C&#7853;p nh&#7853;t tr&#7841;ng th&#225;i</button>
                        <button v-if="canDeleteBookings" type="button" class="inline-flex h-9 items-center gap-2 rounded-lg border border-destructive/40 px-3 text-sm font-semibold text-destructive hover:border-red-600 hover:bg-red-600 hover:text-white disabled:cursor-not-allowed disabled:opacity-40 disabled:hover:border-destructive/40 disabled:hover:bg-transparent disabled:hover:text-destructive" :disabled="!selectedOrderIds.length || ineligibleForBulkDelete" @click="deleteSelectedOrders"><Trash2 class="size-4" />X&#243;a &#273;&#417;n</button>
                        <button type="button" class="h-9 rounded-lg px-3 text-sm font-medium text-muted-foreground hover:bg-muted" @click="clearSelection">B&#7887; ch&#7885;n</button>
                        <span v-if="bulkError" class="basis-full text-right text-sm text-destructive">{{ bulkError }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1180px] text-left text-sm">
                        <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="w-12 px-4 py-3"><input type="checkbox" aria-label="Ch&#7885;n t&#7845;t &#273;&#417;n tr&#234;n trang" :checked="allVisibleSelected" :indeterminate.prop="someVisibleSelected && !allVisibleSelected" :disabled="!visibleOrderIds.length" class="size-4 accent-orange-600 disabled:opacity-30" @change="toggleVisibleOrders" /></th><th class="px-4 py-3">Mã đơn</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Sân</th><th class="px-4 py-3">Người đặt</th><th class="px-4 py-3">Liên hệ</th><th class="px-4 py-3">Thành tiền</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Chi tiết</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="booking in bookings.data" :key="booking.order_id" class="transition hover:bg-accent">
                                <td class="px-4 py-4"><input type="checkbox" :aria-label="`Ch&#7885;n &#273;&#417;n ${booking.booking_code}`" :checked="selectedOrderIds.includes(booking.order_id)" :disabled="booking.status === 'CANCELLED'" class="size-4 accent-orange-600 disabled:opacity-30" @change="toggleOrder(booking.order_id, $event)" /></td>
                                <td class="whitespace-nowrap px-4 py-4 font-bold">{{ booking.booking_code }}</td>
                                <td class="px-4 py-4"><p v-for="(slot, index) in booking.slots" :key="`${slot.starts_at}-${index}`" :class="index ? 'mt-2 border-t border-border pt-2' : ''">{{ slot.starts_at }}–{{ slot.ends_at }}</p></td>
                                <td class="px-4 py-4"><p v-for="(slot, index) in booking.slots" :key="`${slot.court}-${index}`" :class="index ? 'mt-2 border-t border-border pt-2' : ''">{{ slot.court }}</p></td>
                                <td class="px-4 py-4 font-medium">{{ booking.customer_name }}</td>
                                <td class="px-4 py-4"><p>{{ booking.customer_phone || '—' }}</p><p class="mt-1 max-w-[180px] truncate text-xs text-muted-foreground">{{ booking.customer_email }}</p></td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold">{{ money(booking.total_amount, booking.currency) }}</td>
                                <td class="px-4 py-4"><span :class="['inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold', bookingClasses[booking.status]]">{{ bookingLabels[booking.status] }}</span></td>
                                <td class="px-4 py-4 text-right"><button type="button" class="inline-flex min-h-9 items-center rounded-lg border border-input px-3 text-xs font-bold hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300" @click="openBooking(booking)">Xem đơn</button></td>
                            </tr>
                            <tr v-if="!bookings.data.length"><td colspan="9" class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground"><Search class="size-5" /></div><p class="mt-3 text-sm font-semibold">Chưa có booking phù hợp</p></td></tr>
                        </tbody>
                    </table>
                </div>
                <footer class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">Hiển thị <span class="font-semibold text-foreground">{{ bookings.from ?? 0 }}–{{ bookings.to ?? 0 }}</span> trong tổng số <span class="font-semibold text-foreground">{{ bookings.total }}</span> booking</p>
                    <nav class="flex items-center justify-end gap-1" aria-label="Phân trang booking"><Link v-for="(link, index) in bookings.links" :key="`${index}-${link.label}`" :href="link.url || '#'" :preserve-scroll="true" @click="selectedOrderIds = []" :class="['grid min-h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm transition', link.active ? 'border-orange-500 bg-orange-500 font-bold text-white' : 'border-input text-foreground/80 hover:border-orange-500', !link.url && 'pointer-events-none opacity-40']">{{ pageLabel(link.label) }}</Link></nav>
                </footer>
            </section>


        </div>

        <div v-if="selectedBooking" class="fixed inset-0 z-40 flex justify-end bg-black/60" role="dialog" aria-modal="true" :aria-label="`Đơn ${selectedBooking.booking_code}`" @click.self="selectedBookingId = null" @keydown.esc.window="selectedBookingId = null; selectedProof = null">
            <section class="relative flex h-full max-h-screen w-full max-w-2xl flex-col overflow-hidden border-l border-border bg-background shadow-2xl">
                <header class="flex items-start justify-between gap-4 border-b border-border bg-card p-5 sm:p-6">
                    <div><p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-700 dark:text-orange-400">CHI TIẾT ĐƠN ĐẶT SÂN</p><h2 class="mt-1 text-xl font-bold sm:text-2xl">{{ selectedBooking.booking_code }}</h2><p class="mt-1 text-sm text-muted-foreground">{{ selectedBooking.court }} · {{ selectedBooking.slots.length }} khung giờ</p></div>
                    <div class="flex items-center gap-2"><span :class="['inline-flex rounded-full border px-3 py-1 text-xs font-bold', bookingClasses[selectedBooking.status]]">{{ bookingLabels[selectedBooking.status] }}</span><button type="button" class="inline-grid size-10 place-items-center rounded-lg border border-input hover:border-orange-500" aria-label="Đóng chi tiết" @click="selectedBookingId = null"><X class="size-5" /></button></div>
                </header>
                <div class="overflow-y-auto p-4 sm:p-6">
                    <div class="grid gap-4 lg:grid-cols-2">
                        <section class="rounded-xl border border-border bg-card p-4 sm:p-5">
                            <h3 class="flex items-center gap-2 font-bold"><UserRound class="size-4 text-orange-600" />Thông tin người đặt</h3>
                            <dl class="mt-3 grid gap-2 text-sm"><div><dt class="text-muted-foreground">Họ tên</dt><dd class="font-semibold">{{ selectedBooking.customer_name }}</dd></div><div><dt class="text-muted-foreground">Email</dt><dd class="flex items-center gap-2"><Mail class="size-4" />{{ selectedBooking.customer_email }}</dd></div><div><dt class="text-muted-foreground">Điện thoại</dt><dd class="flex items-center gap-2"><Phone class="size-4" />{{ selectedBooking.customer_phone || 'Không cung cấp' }}</dd></div><div v-if="selectedBooking.customer_note"><dt class="text-muted-foreground">Ghi chú</dt><dd class="whitespace-pre-wrap">{{ selectedBooking.customer_note }}</dd></div></dl>
                        </section>
                        <section class="rounded-xl border border-border bg-card p-4 sm:p-5">
                            <h3 class="flex items-center gap-2 font-bold"><CalendarDays class="size-4 text-orange-600" />Lịch đặt sân</h3>
                            <div class="mt-3 divide-y divide-border text-sm"><div v-for="(slot, index) in selectedBooking.slots" :key="`${slot.starts_at}-${index}`" class="flex justify-between gap-3 py-2 first:pt-0"><div><p class="font-semibold">{{ slot.court }}</p><p class="text-muted-foreground">{{ slot.starts_at }} – {{ slot.ends_at }}</p></div><span class="whitespace-nowrap">{{ money(slot.amount, selectedBooking.currency) }}</span></div></div>
                            <div class="mt-3 border-t border-border pt-3 text-sm"><div class="flex justify-between gap-3"><span class="text-muted-foreground">Tạm tính / giảm giá</span><span>{{ money(selectedBooking.base_amount, selectedBooking.currency) }} / {{ money(selectedBooking.discount_amount, selectedBooking.currency) }}</span></div><div class="mt-2 flex justify-between gap-3 font-bold"><span>Thành tiền</span><span>{{ money(selectedBooking.total_amount, selectedBooking.currency) }}</span></div><p v-if="selectedBooking.payment_deadline_at" class="mt-2 text-muted-foreground">Hạn thanh toán: {{ selectedBooking.payment_deadline_at }}</p></div>
                        </section>
                    </div>

                    <section class="mt-4 rounded-xl border border-border bg-card p-4 sm:p-5">
                        <div class="flex flex-wrap items-center justify-between gap-2"><h3 class="font-bold">Trạng thái đơn</h3><p class="text-xs text-muted-foreground">Hủy đơn sẽ giải phóng lịch sân; việc hoàn tiền cần xử lý riêng.</p></div>
                        <form v-if="selectedBooking.status_options.length" class="mt-3 grid gap-2 sm:grid-cols-[190px_1fr_auto] sm:items-end" @submit.prevent="saveBookingStatus">
                            <label class="grid gap-1 text-sm font-medium">Trạng thái mới<select v-model="detailStatusForm.status" class="h-10 rounded-lg border border-input bg-background px-3"><option v-for="option in selectedBooking.status_options" :key="option.value" :value="option.value">{{ option.label }}</option></select></label>
                            <label class="grid gap-1 text-sm font-medium">{{ detailStatusForm.status === 'CANCELLED' ? 'Lý do hủy (bắt buộc)' : 'Ghi chú nội bộ' }}<input v-model="detailStatusForm.note" maxlength="500" :required="detailStatusForm.status === 'CANCELLED'" class="h-10 rounded-lg border border-input bg-background px-3" :placeholder="detailStatusForm.status === 'CANCELLED' ? 'Nhập lý do hủy sân' : 'Không bắt buộc'" /></label>
                            <button type="submit" :disabled="detailStatusForm.processing" class="h-10 rounded-lg bg-orange-600 px-4 text-sm font-bold text-white hover:bg-orange-700 disabled:opacity-50">Lưu trạng thái</button>
                            <p v-if="detailStatusForm.errors.status || detailStatusForm.errors.note" class="text-sm text-destructive sm:col-span-3">{{ detailStatusForm.errors.status || detailStatusForm.errors.note }}</p>
                        </form>
                        <p v-else class="mt-2 text-sm text-muted-foreground">Đơn đã kết thúc, không còn trạng thái chuyển tiếp.</p>
                    </section>

                    <section class="mt-4 rounded-xl border border-border bg-card p-4 sm:p-5"><h3 class="flex items-center gap-2 font-bold"><CreditCard class="size-4 text-orange-600" />Thanh to&#225;n</h3><div v-if="!selectedBooking.payments.length" class="mt-3 rounded-lg bg-muted/50 p-4 text-sm text-muted-foreground">Ch&#432;a c&#243; th&#244;ng tin thanh to&#225;n.</div><article v-for="payment in selectedBooking.payments" :key="payment.id" class="mt-3 flex flex-wrap items-center justify-between gap-3 rounded-lg border border-border p-3"><div><p class="font-semibold">{{ payment.payment_method || payment.provider }} &#183; {{ money(payment.amount, payment.currency) }}</p><p v-if="payment.payment_reference" class="mt-1 text-xs text-muted-foreground">M&#227; tham chi&#7871;u: {{ payment.payment_reference }}</p></div><button v-if="payment.proof_url" type="button" class="flex items-center gap-2 text-sm font-semibold text-orange-700 hover:underline dark:text-orange-300" @click="selectedProof = { url: payment.proof_url, label: `Payment ${payment.id}` }"><img :src="payment.proof_url" :alt="`Payment proof ${selectedBooking.booking_code}`" class="size-12 rounded-md border border-border object-cover" />Xem &#7843;nh</button></article></section>

                    <section class="mt-4 rounded-xl border border-border bg-card p-4 sm:p-5"><h3 class="font-bold">Lịch sử trạng thái đơn</h3><ol class="mt-3 space-y-3"><li v-for="(item, index) in selectedBooking.history" :key="`${item.created_at}-${index}`" class="border-l-2 border-orange-500/50 pl-3"><p class="text-sm font-semibold">{{ item.old_status || 'Mới tạo' }} → {{ item.new_status }}</p><p class="text-xs text-muted-foreground">{{ item.created_at }} · {{ item.change_source }}</p><p v-if="item.note" class="text-sm text-muted-foreground">{{ item.note }}</p></li><li v-if="!selectedBooking.history.length" class="text-sm text-muted-foreground">Chưa có lịch sử.</li></ol></section>
                </div>
            </section>
        </div>

        <div v-if="selectedProof" class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4" role="dialog" aria-modal="true" aria-label="Ảnh chuyển khoản" @click.self="selectedProof = null">
            <div class="relative flex max-h-full max-w-5xl flex-col items-center"><button type="button" class="absolute -right-3 -top-3 z-10 grid size-10 place-items-center rounded-full bg-card text-foreground shadow-lg" aria-label="Đóng ảnh" @click="selectedProof = null"><X class="size-5" /></button><img :src="selectedProof.url" :alt="selectedProof.label" class="max-h-[85vh] max-w-full rounded-lg object-contain" /><p class="mt-3 rounded-full bg-black/60 px-3 py-1 text-sm text-white">{{ selectedProof.label }}</p></div>
        </div>
    </main>
</template>
