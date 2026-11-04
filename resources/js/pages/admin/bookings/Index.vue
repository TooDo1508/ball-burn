<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, CheckCircle2, Clock3, Coins, RotateCcw, Search, Upload, WalletCards } from '@lucide/vue';
import { reactive } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý đặt sân', href: '/admin/bookings' }] } });

type BookingStatus = 'PENDING_PAYMENT' | 'CONFIRMED' | 'EXPIRED' | 'CANCELLED' | 'COMPLETED';
type BookingRow = { id: number; booking_code: string; court: string; customer_name: string; customer_email: string; customer_phone: string | null; starts_at: string; ends_at: string; total_amount: string; currency: string; status: BookingStatus; payment_status: string; payment_deadline_at: string | null; created_at: string | null };
type PageLink = { url: string | null; label: string; active: boolean };
type BookingPage = { data: BookingRow[]; links: PageLink[]; from: number | null; to: number | null; total: number };
type TransferRow = { id: number; bank_transaction_id: string; amount: string; currency: string; transaction_at: string; transfer_description: string | null; status: string; booking_code: string | null; review_note: string | null };

const props = defineProps<{
    bookings: BookingPage;
    stats: { total: number; confirmed: number; pending: number; cancelled: number; expired: number; paid_bookings: number; paid_amount: string };
    filters: { search: string; court_id: number | string; status: string; payment_status: string; from: string; to: string; per_page: number };
    courts: { id: number; name: string }[];
    transfers: TransferRow[];
    matchableBookings: { id: number; label: string }[];
}>();

const filters = reactive({ ...props.filters });
const transferForm = useForm({ bank_transaction_id: '', amount: '', currency: 'VND', transaction_at: '', transfer_description: '' });
const matchForms = reactive<Record<number, ReturnType<typeof useForm<{ booking_id: string; review_note: string }>>>>({});
for (const transfer of props.transfers) matchForms[transfer.id] = useForm({ booking_id: '', review_note: '' });

const bookingLabels: Record<BookingStatus, string> = { PENDING_PAYMENT: 'Chờ thanh toán', CONFIRMED: 'Đã xác nhận', EXPIRED: 'Hết hạn', CANCELLED: 'Đã hủy', COMPLETED: 'Đã sử dụng' };
const bookingClasses: Record<BookingStatus, string> = {
    PENDING_PAYMENT: 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300',
    CONFIRMED: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
    EXPIRED: 'border-input bg-muted text-muted-foreground',
    CANCELLED: 'border-rose-500/30 bg-rose-500/10 text-rose-800 dark:text-rose-300',
    COMPLETED: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
};

function money(value: string | number, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency }).format(Number(value));
}

function applyFilters() {
    router.get('/admin/bookings', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', court_id: '', status: '', payment_status: '', from: '', to: '', per_page: 10 });
    applyFilters();
}

function recordTransfer() {
    transferForm.post('/admin/bank-transfers', { preserveScroll: true, onSuccess: () => transferForm.reset('bank_transaction_id', 'amount', 'transaction_at', 'transfer_description') });
}

function matchTransfer(transferId: number) {
    matchForms[transferId]?.post(`/admin/bank-transfers/${transferId}/match`, { preserveScroll: true });
}

function pageLabel(label: string) {
    return label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}
</script>

<template>
    <Head title="Quản lý đặt sân" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1600px] space-y-6">
            <header class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN · VẬN HÀNH</p>
                    <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><CalendarDays class="size-7 text-orange-700 dark:text-orange-400" />Danh sách đặt sân</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Theo dõi booking, trạng thái thanh toán và giao dịch cần đối soát.</p>
                </div>
            </header>

            <p v-if="$page.props.flash?.success" class="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-800 dark:text-emerald-300">{{ $page.props.flash.success }}</p>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5" aria-label="Thống kê booking">
                <article v-for="item in [
                    { label: 'Tổng booking', value: stats.total, icon: CalendarDays, color: 'text-foreground' },
                    { label: 'Đã thanh toán', value: stats.paid_bookings, icon: CheckCircle2, color: 'text-emerald-700 dark:text-emerald-300' },
                    { label: 'Chờ thanh toán', value: stats.pending, icon: Clock3, color: 'text-amber-700 dark:text-amber-300' },
                    { label: 'Đã hủy / hết hạn', value: stats.cancelled + stats.expired, icon: RotateCcw, color: 'text-muted-foreground' },
                    { label: 'Tiền đã xác minh', value: money(stats.paid_amount), icon: Coins, color: 'text-orange-700 dark:text-orange-300' },
                ]" :key="item.label" class="rounded-xl border border-border bg-card p-4">
                    <div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">{{ item.label }}</p><component :is="item.icon" :class="['size-4', item.color]" /></div>
                    <p class="mt-3 text-2xl font-bold">{{ item.value }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <form class="grid gap-3 border-b border-border p-4 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.4fr)_170px_180px_180px_155px_155px_auto_auto]" @submit.prevent="applyFilters">
                    <label class="relative block"><span class="sr-only">Tìm booking</span><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><input v-model="filters.search" type="search" placeholder="Mã đơn, tên, SĐT, email..." class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-3 text-sm outline-none focus:border-orange-500" /></label>
                    <label><span class="sr-only">Lọc sân</span><select v-model="filters.court_id" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="">Tất cả sân</option><option v-for="court in courts" :key="court.id" :value="court.id">{{ court.name }}</option></select></label>
                    <label><span class="sr-only">Trạng thái booking</span><select v-model="filters.status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="">Tất cả trạng thái</option><option value="PENDING_PAYMENT">Chờ thanh toán</option><option value="CONFIRMED">Đã xác nhận</option><option value="EXPIRED">Hết hạn</option><option value="CANCELLED">Đã hủy</option><option value="COMPLETED">Đã sử dụng</option></select></label>
                    <label><span class="sr-only">Trạng thái thanh toán</span><select v-model="filters.payment_status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm"><option value="">Tất cả thanh toán</option><option value="PAID">Đã thanh toán</option><option value="PENDING">Đang xử lý</option><option value="FAILED">Thất bại</option><option value="EXPIRED">Hết hạn</option><option value="UNPAID">Chưa ghi nhận</option></select></label>
                    <label class="grid gap-1 text-xs font-semibold text-muted-foreground"><span>Ngày sân từ</span><input v-model="filters.from" type="date" class="h-10 w-full rounded-lg border border-input bg-background px-2 text-sm font-normal text-foreground" /></label>
                    <label class="grid gap-1 text-xs font-semibold text-muted-foreground"><span>Đến ngày sân</span><input v-model="filters.to" type="date" class="h-10 w-full rounded-lg border border-input bg-background px-2 text-sm font-normal text-foreground" /></label>
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600"><Search class="size-4" />Lọc</button>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold hover:border-orange-500" @click="resetFilters"><RotateCcw class="size-4" />Xóa lọc</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1120px] text-left text-sm">
                        <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-4 py-3">Mã đơn</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Sân</th><th class="px-4 py-3">Người đặt</th><th class="px-4 py-3">Liên hệ</th><th class="px-4 py-3">Thành tiền</th><th class="px-4 py-3">Thanh toán</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3 text-right">Chi tiết</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="booking in bookings.data" :key="booking.id" class="transition hover:bg-accent">
                                <td class="whitespace-nowrap px-4 py-4 font-bold">{{ booking.booking_code }}</td>
                                <td class="whitespace-nowrap px-4 py-4"><p>{{ booking.starts_at }}</p><p class="mt-1 text-xs text-muted-foreground">đến {{ booking.ends_at }}</p></td>
                                <td class="px-4 py-4">{{ booking.court }}</td>
                                <td class="px-4 py-4 font-medium">{{ booking.customer_name }}</td>
                                <td class="px-4 py-4"><p>{{ booking.customer_phone || '—' }}</p><p class="mt-1 max-w-[180px] truncate text-xs text-muted-foreground">{{ booking.customer_email }}</p></td>
                                <td class="whitespace-nowrap px-4 py-4 font-semibold">{{ money(booking.total_amount, booking.currency) }}</td>
                                <td class="px-4 py-4"><span :class="['inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold', booking.payment_status === 'PAID' ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300' : 'border-amber-500/30 bg-amber-500/10 text-amber-800 dark:text-amber-300']">{{ booking.payment_status === 'PAID' ? 'Đã thanh toán' : booking.payment_status === 'UNPAID' ? 'Chưa ghi nhận' : booking.payment_status }}</span></td>
                                <td class="px-4 py-4"><span :class="['inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold', bookingClasses[booking.status]]">{{ bookingLabels[booking.status] }}</span></td>
                                <td class="px-4 py-4 text-right"><Link :href="`/admin/bookings/${booking.id}`" class="inline-flex min-h-9 items-center rounded-lg border border-input px-3 text-xs font-bold hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300">Xem đơn</Link></td>
                            </tr>
                            <tr v-if="!bookings.data.length"><td colspan="9" class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground"><Search class="size-5" /></div><p class="mt-3 text-sm font-semibold">Chưa có booking phù hợp</p></td></tr>
                        </tbody>
                    </table>
                </div>
                <footer class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">Hiển thị <span class="font-semibold text-foreground">{{ bookings.from ?? 0 }}–{{ bookings.to ?? 0 }}</span> trong tổng số <span class="font-semibold text-foreground">{{ bookings.total }}</span> booking</p>
                    <nav class="flex items-center justify-end gap-1" aria-label="Phân trang booking"><Link v-for="(link, index) in bookings.links" :key="`${index}-${link.label}`" :href="link.url || '#'" :preserve-scroll="true" :class="['grid min-h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm transition', link.active ? 'border-orange-500 bg-orange-500 font-bold text-white' : 'border-input text-foreground/80 hover:border-orange-500', !link.url && 'pointer-events-none opacity-40']">{{ pageLabel(link.label) }}</Link></nav>
                </footer>
            </section>

            <section class="grid gap-5 xl:grid-cols-[minmax(320px,.8fr)_minmax(0,1.2fr)]">
                <form class="grid content-start gap-4 rounded-xl border border-border bg-card p-5" @submit.prevent="recordTransfer">
                    <div><h2 class="flex items-center gap-2 text-lg font-bold"><Upload class="size-5 text-orange-600" />Ghi nhận giao dịch ngân hàng</h2><p class="mt-1 text-sm leading-6 text-muted-foreground">Chỉ nhập sau khi đã kiểm tra giao dịch thực nhận trên sao kê/app ngân hàng.</p></div>
                    <label class="grid gap-2 text-sm font-semibold">Mã giao dịch ngân hàng<input v-model="transferForm.bank_transaction_id" required maxlength="191" class="h-10 rounded-lg border border-input bg-background px-3 font-normal" placeholder="Mã tham chiếu duy nhất" /><span v-if="transferForm.errors.bank_transaction_id" class="text-xs text-destructive">{{ transferForm.errors.bank_transaction_id }}</span></label>
                    <div class="grid grid-cols-[1fr_110px] gap-3"><label class="grid gap-2 text-sm font-semibold">Số tiền<input v-model="transferForm.amount" required type="number" min="0.01" step="0.01" class="h-10 rounded-lg border border-input bg-background px-3 font-normal" /><span v-if="transferForm.errors.amount" class="text-xs text-destructive">{{ transferForm.errors.amount }}</span></label><label class="grid gap-2 text-sm font-semibold">Tiền tệ<input v-model="transferForm.currency" required maxlength="3" class="h-10 rounded-lg border border-input bg-background px-3 uppercase font-normal" /></label></div>
                    <label class="grid gap-2 text-sm font-semibold">Thời điểm giao dịch<input v-model="transferForm.transaction_at" required type="datetime-local" class="h-10 rounded-lg border border-input bg-background px-3 font-normal" /><span v-if="transferForm.errors.transaction_at" class="text-xs text-destructive">{{ transferForm.errors.transaction_at }}</span></label>
                    <label class="grid gap-2 text-sm font-semibold">Nội dung chuyển khoản<textarea v-model="transferForm.transfer_description" maxlength="500" rows="2" class="rounded-lg border border-input bg-background px-3 py-2 text-sm font-normal" placeholder="Có thể để trống nếu khách không ghi nội dung" /></label>
                    <p v-if="transferForm.errors.amount || transferForm.errors.currency || transferForm.errors.bank_transaction_id || transferForm.errors.transaction_at" class="text-sm text-destructive">{{ transferForm.errors.amount || transferForm.errors.currency || transferForm.errors.bank_transaction_id || transferForm.errors.transaction_at }}</p>
                    <button type="submit" :disabled="transferForm.processing" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600 disabled:opacity-50"><WalletCards class="size-4" />{{ transferForm.processing ? 'Đang lưu…' : 'Đưa vào đối soát' }}</button>
                </form>

                <section class="overflow-hidden rounded-xl border border-border bg-card">
                    <header class="border-b border-border p-5"><h2 class="text-lg font-bold">Giao dịch cần đối soát</h2><p class="mt-1 text-sm text-muted-foreground">Không tự ghép giao dịch chỉ dựa trên số tiền hoặc thời điểm.</p></header>
                    <div v-if="!transfers.length" class="px-5 py-12 text-center text-sm text-muted-foreground">Chưa có giao dịch chờ đối soát.</div>
                    <div v-else class="divide-y divide-border">
                        <article v-for="transfer in transfers" :key="transfer.id" class="grid gap-4 p-5 lg:grid-cols-[minmax(180px,.8fr)_minmax(240px,1.2fr)]">
                            <div class="min-w-0"><p class="break-all font-semibold">{{ transfer.bank_transaction_id }}</p><p class="mt-1 text-sm font-bold text-orange-700 dark:text-orange-300">{{ money(transfer.amount, transfer.currency) }}</p><p class="mt-1 text-xs text-muted-foreground">{{ transfer.transaction_at }}</p><p v-if="transfer.transfer_description" class="mt-2 break-words text-sm text-muted-foreground">{{ transfer.transfer_description }}</p><p class="mt-2 text-xs font-semibold text-amber-800 dark:text-amber-300">{{ transfer.status === 'UNMATCHED' ? 'Chưa ghép đơn' : transfer.status === 'NEEDS_REVIEW' ? 'Lệch thông tin · cần xử lý' : 'Tiền về muộn · cần xử lý' }}</p><p v-if="transfer.review_note" class="mt-1 text-xs text-muted-foreground">{{ transfer.review_note }}</p></div>
                            <form v-if="['UNMATCHED', 'NEEDS_REVIEW'].includes(transfer.status)" class="grid content-start gap-2" @submit.prevent="matchTransfer(transfer.id)">
                                <label class="grid gap-1 text-xs font-semibold">Chọn booking sau khi đã xác minh giao dịch<select v-model="matchForms[transfer.id].booking_id" required class="h-10 rounded-lg border border-input bg-background px-2 text-sm font-normal"><option value="">Chọn booking…</option><option v-for="booking in matchableBookings" :key="booking.id" :value="String(booking.id)">{{ booking.label }}</option></select></label>
                                <label class="grid gap-1 text-xs font-semibold">Ghi chú đối soát (nếu cần)<input v-model="matchForms[transfer.id].review_note" maxlength="500" class="h-10 rounded-lg border border-input bg-background px-3 text-sm font-normal" placeholder="Ví dụ: đã kiểm tra sao kê" /></label>
                                <p v-if="matchForms[transfer.id].errors.booking_id || matchForms[transfer.id].errors.review_note" class="text-xs text-destructive">{{ matchForms[transfer.id].errors.booking_id || matchForms[transfer.id].errors.review_note }}</p>
                                <button type="submit" :disabled="matchForms[transfer.id].processing" class="mt-1 inline-flex min-h-9 items-center justify-center rounded-lg border border-orange-500/40 px-3 text-sm font-bold text-orange-800 hover:bg-orange-500/10 dark:text-orange-300">Ghép và xác minh</button>
                            </form>
                            <Link v-else-if="transfer.booking_code" :href="`/admin/bookings?search=${encodeURIComponent(transfer.booking_code)}`" class="inline-flex h-fit items-center text-sm font-semibold text-orange-700 hover:underline dark:text-orange-300">Mở booking {{ transfer.booking_code }}</Link>
                        </article>
                    </div>
                </section>
            </section>
        </div>
    </main>
</template>
