<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    Activity, ArrowDownRight, ArrowRight, ArrowUpRight, Banknote, BarChart3,
    CalendarDays, CalendarRange, CalendarX, CheckCircle2, Clock3, Mail, Newspaper,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }] } });

type PeriodKey = 'today' | '7' | '30' | 'custom';
type CurrencyAmount = { currency: string; amount: string };
type BookingStatus = 'PAID' | 'PENDING_PAYMENT' | 'CANCELLED';

const props = defineProps<{
    period: { range: PeriodKey; from: string; to: string; label: string; previous_label: string };
    overview: {
        metrics: {
            total: number;
            paid: number;
            pending: number;
            cancelled: number;
            previous: { total: number; paid: number; pending: number; cancelled: number };
        };
        revenue: {
            totals: CurrencyAmount[];
            previous_totals: CurrencyAmount[];
            chart_currency: string;
            chart_total: string;
            chart_previous_total: string;
            series: { date: string; label: string; amount: string }[];
        };
        popular_hours: { hour: string; count: number }[];
        upcoming_bookings: {
            id: number;
            booking_code: string;
            court: string;
            customer_name: string;
            starts_at: string;
            status: BookingStatus;
        }[];
    };
}>();

const fromDate = ref(props.period.from);
const toDate = ref(props.period.to);
watch(() => [props.period.from, props.period.to], ([from, to]) => {
    fromDate.value = from;
    toDate.value = to;
});

const metrics = computed(() => [
    { key: 'total', title: 'Tổng đơn đặt sân', value: props.overview.metrics.total, previous: props.overview.metrics.previous.total, icon: CalendarDays, decorationClass: 'bg-orange-500/10', iconClass: 'bg-orange-500/10 text-orange-700 dark:text-orange-300' },
    { key: 'paid', title: 'Đã thanh toán', value: props.overview.metrics.paid, previous: props.overview.metrics.previous.paid, icon: CheckCircle2, decorationClass: 'bg-emerald-500/10', iconClass: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300' },
    { key: 'pending', title: 'Chờ thanh toán', value: props.overview.metrics.pending, previous: props.overview.metrics.previous.pending, icon: Clock3, decorationClass: 'bg-amber-500/10', iconClass: 'bg-amber-500/10 text-amber-700 dark:text-amber-300' },
    { key: 'cancelled', title: 'Đã hủy', value: props.overview.metrics.cancelled, previous: props.overview.metrics.previous.cancelled, icon: CalendarX, decorationClass: 'bg-rose-500/10', iconClass: 'bg-rose-500/10 text-rose-700 dark:text-rose-300' },
]);

const statusSegments = computed(() => [
    { key: 'paid', title: 'Đã thanh toán', value: props.overview.metrics.paid, color: '#16a34a', class: 'bg-emerald-500' },
    { key: 'pending', title: 'Chờ thanh toán', value: props.overview.metrics.pending, color: '#f59e0b', class: 'bg-amber-500' },
    { key: 'cancelled', title: 'Đã hủy', value: props.overview.metrics.cancelled, color: '#ef4444', class: 'bg-rose-500' },
]);
const statusTotal = computed(() => statusSegments.value.reduce((sum, item) => sum + item.value, 0));
const donutStyle = computed(() => {
    if (!statusTotal.value) return { background: 'conic-gradient(var(--muted) 0deg 360deg)' };
    const paidEnd = props.overview.metrics.paid / statusTotal.value * 360;
    const pendingEnd = paidEnd + props.overview.metrics.pending / statusTotal.value * 360;
    return {
        background: `conic-gradient(#16a34a 0deg ${paidEnd}deg, #f59e0b ${paidEnd}deg ${pendingEnd}deg, #ef4444 ${pendingEnd}deg 360deg)`,
    };
});

const chartPoints = computed(() => {
    const series = props.overview.revenue.series;
    const values = series.map((point) => Number(point.amount));
    const max = Math.max(...values, 1) * 1.12;
    const left = 54;
    const right = 720;
    const top = 18;
    const bottom = 174;
    const width = Math.max(1, right - left);
    const height = bottom - top;

    return series.map((point, index) => ({
        ...point,
        x: series.length === 1 ? (left + right) / 2 : left + (index / (series.length - 1)) * width,
        y: bottom - (Number(point.amount) / max) * height,
    }));
});
const chartLine = computed(() => chartPoints.value.map((point) => `${point.x},${point.y}`).join(' '));
const chartArea = computed(() => {
    if (!chartPoints.value.length) return '';
    const first = chartPoints.value[0];
    const last = chartPoints.value[chartPoints.value.length - 1];
    return `M ${first.x},174 L ${chartPoints.value.map((point) => `${point.x},${point.y}`).join(' L ')} L ${last.x},174 Z`;
});
const chartMaximum = computed(() => Math.max(...props.overview.revenue.series.map((point) => Number(point.amount)), 1) * 1.12);
const chartTicks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((fraction) => ({
    y: 174 - fraction * 156,
    label: compactMoney(chartMaximum.value * fraction, props.overview.revenue.chart_currency),
})));
const maxPopularCount = computed(() => Math.max(...props.overview.popular_hours.map((slot) => slot.count), 1));

const rangeOptions: { key: PeriodKey; label: string }[] = [
    { key: 'today', label: 'Hôm nay' },
    { key: '7', label: '7 ngày' },
    { key: '30', label: '30 ngày' },
];

function selectRange(range: PeriodKey) {
    router.get('/admin', { range }, { preserveScroll: true, preserveState: false, replace: true });
}

function applyCustomRange() {
    if (!fromDate.value || !toDate.value) return;
    router.get('/admin', { range: 'custom', from: fromDate.value, to: toDate.value }, {
        preserveScroll: true,
        preserveState: false,
        replace: true,
    });
}

function formatMoney(amount: string | number, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency,
        maximumFractionDigits: currency === 'VND' ? 0 : 2,
    }).format(Number(amount));
}

function compactMoney(amount: number, currency: string) {
    const suffix = currency === 'VND' ? ' ₫' : ` ${currency}`;
    if (amount >= 1_000_000) return `${(amount / 1_000_000).toFixed(amount >= 10_000_000 ? 0 : 1)} tr${suffix}`;
    if (amount >= 1_000) return `${Math.round(amount / 1_000)}k${suffix}`;
    return `${new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(amount)}${suffix}`;
}

function moneyBreakdown(items: CurrencyAmount[]) {
    return items.length ? items.map((item) => formatMoney(item.amount, item.currency)).join(' · ') : formatMoney(0);
}

function compareLabel(current: number, previous: number) {
    if (!previous) return current ? 'Mới phát sinh trong kỳ này' : 'Chưa phát sinh';
    const change = Math.round(((current - previous) / previous) * 100);
    if (!change) return 'Không đổi so với kỳ trước';
    return `${change > 0 ? '+' : ''}${change}% so với kỳ trước`;
}

function compareTone(key: string, current: number, previous: number) {
    if (current === previous) return 'text-muted-foreground';
    const improved = key === 'total' || key === 'paid' ? current > previous : current < previous;
    return improved ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400';
}

function percent(value: number) {
    return statusTotal.value ? `${((value / statusTotal.value) * 100).toFixed(1)}%` : '0%';
}

function statusLabel(status: BookingStatus) {
    return status === 'PAID' ? 'Đã thanh toán' : status === 'CANCELLED' ? 'Đã hủy' : 'Chờ thanh toán';
}

function statusClass(status: BookingStatus) {
    return status === 'PAID'
        ? 'bg-emerald-500/10 text-emerald-800 dark:text-emerald-300'
        : status === 'CANCELLED'
            ? 'bg-rose-500/10 text-rose-800 dark:text-rose-300'
            : 'bg-amber-500/10 text-amber-800 dark:text-amber-300';
}
</script>

<template>
    <Head title="Dashboard BALLBURN" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1600px] space-y-6">
            <header class="flex flex-col gap-5 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN</p>
                    <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">Dashboard</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Tổng quan hoạt động đặt sân · {{ period.label }}</p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="inline-flex rounded-xl border border-border bg-card p-1 shadow-sm" aria-label="Khoảng thời gian">
                        <button
                            v-for="option in rangeOptions"
                            :key="option.key"
                            type="button"
                            :aria-pressed="period.range === option.key"
                            :class="['rounded-lg px-3 py-2 text-sm font-semibold transition-colors', period.range === option.key ? 'bg-orange-500 text-white shadow-sm' : 'text-muted-foreground hover:bg-orange-500/10 hover:text-orange-700 dark:hover:text-orange-300']"
                            @click="selectRange(option.key)"
                        >{{ option.label }}</button>
                    </div>
                    <form class="flex items-center gap-2 rounded-xl border border-border bg-card p-2 shadow-sm" @submit.prevent="applyCustomRange">
                        <CalendarRange class="ml-1 size-4 shrink-0 text-orange-600" />
                        <label class="sr-only" for="dashboard-from">Từ ngày</label>
                        <input id="dashboard-from" v-model="fromDate" type="date" class="min-w-0 border-0 bg-transparent px-1 py-1 text-xs text-foreground outline-none" />
                        <span class="text-muted-foreground">–</span>
                        <label class="sr-only" for="dashboard-to">Đến ngày</label>
                        <input id="dashboard-to" v-model="toDate" type="date" class="min-w-0 border-0 bg-transparent px-1 py-1 text-xs text-foreground outline-none" />
                        <button type="submit" class="rounded-lg bg-muted px-3 py-2 text-xs font-bold text-foreground transition hover:bg-orange-500/10 hover:text-orange-700 dark:hover:text-orange-300">Lọc</button>
                    </form>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 2xl:grid-cols-4" aria-label="Tổng quan đơn đặt sân">
                <article v-for="metric in metrics" :key="metric.key" class="relative overflow-hidden rounded-2xl border border-border bg-card p-5 shadow-sm">
                    <div class="absolute -bottom-10 -right-7 size-28 rounded-full opacity-50" :class="metric.decorationClass" />
                    <div class="relative flex items-start justify-between gap-3">
                        <div>
                            <p class="text-sm font-medium text-muted-foreground">{{ metric.title }}</p>
                            <p class="mt-3 text-3xl font-bold tracking-tight">{{ metric.value.toLocaleString('vi-VN') }}</p>
                        </div>
                        <span :class="['grid size-12 shrink-0 place-items-center rounded-2xl', metric.iconClass]">
                            <component :is="metric.icon" class="size-6" />
                        </span>
                    </div>
                    <p :class="['relative mt-3 flex items-center gap-1 text-xs font-semibold', compareTone(metric.key, metric.value, metric.previous)]">
                        <component :is="metric.value >= metric.previous ? ArrowUpRight : ArrowDownRight" class="size-4" />
                        {{ compareLabel(metric.value, metric.previous) }}
                    </p>
                </article>
            </section>

            <section class="grid gap-4 2xl:grid-cols-12">
                <article class="rounded-2xl border border-border bg-card p-5 shadow-sm 2xl:col-span-6 sm:p-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="flex items-center gap-2 text-sm font-semibold"><span class="grid size-9 place-items-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"><Banknote class="size-5" /></span>Doanh thu</p>
                            <p class="mt-4 text-3xl font-bold tracking-tight">{{ moneyBreakdown(overview.revenue.totals) }}</p>
                            <p :class="['mt-1 flex items-center gap-1 text-xs font-semibold', compareTone('paid', Number(overview.revenue.chart_total), Number(overview.revenue.chart_previous_total))]">
                                <component :is="Number(overview.revenue.chart_total) >= Number(overview.revenue.chart_previous_total) ? ArrowUpRight : ArrowDownRight" class="size-4" />
                                {{ compareLabel(Number(overview.revenue.chart_total), Number(overview.revenue.chart_previous_total)) }}
                            </p>
                        </div>
                        <span class="rounded-full bg-muted px-3 py-1.5 text-xs font-semibold text-muted-foreground">{{ period.label }}</span>
                    </div>

                    <div class="mt-5">
                        <div v-if="Number(overview.revenue.chart_total) > 0" class="overflow-x-auto">
                            <svg viewBox="0 0 760 220" role="img" :aria-label="`Biểu đồ doanh thu ${period.label}`" class="h-56 min-w-[560px] w-full">
                                <defs>
                                    <linearGradient id="revenue-fill" x1="0" x2="0" y1="0" y2="1">
                                        <stop offset="0%" stop-color="#f97316" stop-opacity="0.22" />
                                        <stop offset="100%" stop-color="#f97316" stop-opacity="0.01" />
                                    </linearGradient>
                                </defs>
                                <g v-for="tick in chartTicks" :key="tick.y">
                                    <line x1="50" :y1="tick.y" x2="730" :y2="tick.y" stroke="currentColor" class="text-border" stroke-opacity="0.65" stroke-dasharray="4 5" />
                                    <text x="44" :y="tick.y - 4" text-anchor="end" class="fill-muted-foreground" font-size="10">{{ tick.label }}</text>
                                </g>
                                <path :d="chartArea" fill="url(#revenue-fill)" />
                                <polyline :points="chartLine" fill="none" stroke="#f97316" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
                                <g v-for="(point, index) in chartPoints" :key="point.date">
                                    <circle :cx="point.x" :cy="point.y" r="4" fill="#f97316" stroke="var(--card)" stroke-width="2">
                                        <title>{{ point.label }} · {{ formatMoney(point.amount, overview.revenue.chart_currency) }}</title>
                                    </circle>
                                    <text v-if="chartPoints.length <= 8 || index === 0 || index === chartPoints.length - 1 || index % Math.ceil(chartPoints.length / 7) === 0" :x="point.x" y="202" text-anchor="middle" class="fill-muted-foreground" font-size="10">{{ point.label }}</text>
                                </g>
                            </svg>
                        </div>
                        <div v-else class="grid h-56 place-items-center rounded-xl bg-muted/30 text-center">
                            <div><Banknote class="mx-auto size-8 text-muted-foreground/50" /><p class="mt-2 text-sm font-medium">Chưa có khoản thanh toán trong kỳ này</p><p class="mt-1 text-xs text-muted-foreground">Biểu đồ sẽ cập nhật khi có đơn được thanh toán.</p></div>
                        </div>
                    </div>
                    <p class="mt-2 text-xs text-muted-foreground">Doanh thu được xếp theo ngày tạo đơn; đơn đã hủy nhưng chưa hoàn tiền vẫn được ghi nhận.</p>
                </article>

                <article class="rounded-2xl border border-border bg-card p-5 shadow-sm 2xl:col-span-3 sm:p-6">
                    <div class="flex items-center gap-2 text-sm font-semibold"><span class="grid size-9 place-items-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"><BarChart3 class="size-5" /></span>Khung giờ được đặt nhiều</div>
                    <div v-if="overview.popular_hours.length" class="mt-7 flex h-52 items-end justify-between gap-2 border-b border-border px-1">
                        <div v-for="slot in overview.popular_hours" :key="slot.hour" class="flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-2">
                            <span class="text-xs font-bold tabular-nums">{{ slot.count }}</span>
                            <div class="w-full max-w-10 rounded-t-lg bg-emerald-500/30 transition-colors hover:bg-emerald-500/60" :style="{ height: `${Math.max(8, (slot.count / maxPopularCount) * 72)}%` }" :title="`${slot.hour}: ${slot.count} lượt đặt`" />
                            <span class="pb-2 text-[10px] font-medium text-muted-foreground sm:text-xs">{{ slot.hour.slice(0, 2) }}h</span>
                        </div>
                    </div>
                    <div v-else class="mt-5 grid h-52 place-items-center rounded-xl bg-muted/30 text-center text-sm text-muted-foreground">Chưa có lịch sân đang hiệu lực trong kỳ này.</div>
                    <p class="mt-3 text-xs text-muted-foreground">Lượt đặt theo giờ bắt đầu của các đơn đã thanh toán hoặc còn hạn thanh toán.</p>
                </article>

                <article class="rounded-2xl border border-border bg-card p-5 shadow-sm 2xl:col-span-3 sm:p-6">
                    <div class="flex items-center gap-2 text-sm font-semibold"><span class="grid size-9 place-items-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"><Activity class="size-5" /></span>Tỷ lệ trạng thái đơn</div>
                    <div class="mt-6 flex flex-col items-center gap-6 sm:flex-row 2xl:flex-col 2xl:gap-5">
                        <div class="relative grid size-40 shrink-0 place-items-center rounded-full" :style="donutStyle" role="img" :aria-label="`Tổng ${statusTotal} đơn`">
                            <div class="grid size-24 place-items-center rounded-full bg-card text-center shadow-inner"><div><p class="text-2xl font-bold leading-none">{{ statusTotal }}</p><p class="mt-1 text-xs text-muted-foreground">đơn</p></div></div>
                        </div>
                        <ul class="w-full space-y-3">
                            <li v-for="item in statusSegments" :key="item.key" class="flex items-center justify-between gap-2 text-xs sm:text-sm">
                                <span class="flex min-w-0 items-center gap-2"><i :class="['size-2.5 shrink-0 rounded-full', item.class]" /><span class="truncate text-muted-foreground">{{ item.title }}</span></span>
                                <span class="shrink-0 font-semibold tabular-nums">{{ item.value }} <span class="font-normal text-muted-foreground">({{ percent(item.value) }})</span></span>
                            </li>
                        </ul>
                    </div>
                    <p class="mt-5 text-xs text-muted-foreground">Phân loại theo trạng thái hiện tại của đơn tạo trong kỳ.</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-2xl border border-border bg-card shadow-sm">
                <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border px-5 py-4 sm:px-6">
                    <div>
                        <h2 class="flex items-center gap-2 font-bold"><span class="grid size-9 place-items-center rounded-xl bg-orange-500/10 text-orange-700 dark:text-orange-300"><CalendarDays class="size-5" /></span>Lịch đặt sân sắp tới</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Các lịch đã thanh toán hoặc đang còn hạn thanh toán.</p>
                    </div>
                    <Link href="/admin/bookings" class="inline-flex items-center gap-1 text-sm font-semibold text-orange-700 hover:text-orange-800 dark:text-orange-300 dark:hover:text-orange-200">Xem tất cả <ArrowRight class="size-4" /></Link>
                </header>

                <div v-if="overview.upcoming_bookings.length" class="overflow-x-auto">
                    <table class="w-full min-w-[680px] text-left text-sm">
                        <thead class="bg-muted/40 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-5 py-3">Thời gian</th><th class="px-5 py-3">Mã đơn / Sân</th><th class="px-5 py-3">Người đặt</th><th class="px-5 py-3">Trạng thái</th><th class="px-5 py-3" /></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="booking in overview.upcoming_bookings" :key="booking.id" class="transition-colors hover:bg-muted/30">
                                <td class="whitespace-nowrap px-5 py-4 font-semibold">{{ booking.starts_at }}</td>
                                <td class="px-5 py-4"><p class="font-semibold">{{ booking.booking_code }}</p><p class="mt-1 text-xs text-muted-foreground">{{ booking.court }}</p></td>
                                <td class="px-5 py-4">{{ booking.customer_name }}</td>
                                <td class="px-5 py-4"><span :class="['inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', statusClass(booking.status)]">{{ statusLabel(booking.status) }}</span></td>
                                <td class="px-5 py-4 text-right"><Link :href="`/admin/bookings/${booking.id}`" class="font-semibold text-orange-700 hover:underline dark:text-orange-300">Chi tiết</Link></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-else class="grid min-h-40 place-items-center px-6 text-center">
                    <div><CalendarDays class="mx-auto size-8 text-muted-foreground/50" /><p class="mt-2 text-sm font-semibold">Chưa có lịch đặt sắp tới</p><p class="mt-1 text-xs text-muted-foreground">Các lịch mới sẽ xuất hiện ở đây.</p></div>
                </div>
            </section>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Lối tắt quản trị">
                <Link href="/admin/bookings" class="group flex items-center justify-between rounded-xl border border-border bg-card p-4 transition hover:border-orange-400 hover:bg-orange-500/5">
                    <span class="flex items-center gap-3"><CalendarDays class="size-5 text-orange-600" /><span><span class="block text-sm font-semibold">Quản lý đặt sân</span><span class="text-xs text-muted-foreground">Tìm đơn, cập nhật trạng thái</span></span></span><ArrowRight class="size-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-orange-600" />
                </Link>
                <Link href="/admin/courts" class="group flex items-center justify-between rounded-xl border border-border bg-card p-4 transition hover:border-orange-400 hover:bg-orange-500/5">
                    <span class="flex items-center gap-3"><CalendarRange class="size-5 text-orange-600" /><span><span class="block text-sm font-semibold">Quản lý sân</span><span class="text-xs text-muted-foreground">Giá, giờ hoạt động, lịch khóa</span></span></span><ArrowRight class="size-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-orange-600" />
                </Link>
                <Link href="/admin/news" class="group flex items-center justify-between rounded-xl border border-border bg-card p-4 transition hover:border-orange-400 hover:bg-orange-500/5">
                    <span class="flex items-center gap-3"><Newspaper class="size-5 text-orange-600" /><span><span class="block text-sm font-semibold">Tin tức & Blog</span><span class="text-xs text-muted-foreground">Bài viết và danh mục</span></span></span><ArrowRight class="size-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-orange-600" />
                </Link>
                <Link href="/admin/contact-messages" class="group flex items-center justify-between rounded-xl border border-border bg-card p-4 transition hover:border-orange-400 hover:bg-orange-500/5">
                    <span class="flex items-center gap-3"><Mail class="size-5 text-orange-600" /><span><span class="block text-sm font-semibold">Tin nhắn liên hệ</span><span class="text-xs text-muted-foreground">Hộp thư yêu cầu từ khách</span></span></span><ArrowRight class="size-4 text-muted-foreground transition group-hover:translate-x-1 group-hover:text-orange-600" />
                </Link>
            </section>
        </div>
    </main>
</template>
