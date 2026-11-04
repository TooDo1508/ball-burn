<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import {
    CalendarDays, CheckCircle2, ChevronLeft, ChevronRight, CircleDollarSign,
    Clock3, ImagePlus, LockKeyhole, MapPin, Pencil, Plus, Save, UnlockKeyhole, UserPlus, X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý sân', href: '/admin/courts' }] } });

type OperatingHour = { day_of_week: number; is_closed: boolean; opens_at: string; closes_at: string };
type PriceRule = { day_type: 'WEEKDAY' | 'WEEKEND'; starts_at: string; ends_at: string; price_per_hour: string | number; effective_from: string };
type DayHour = { is_closed: boolean; opens_at: string; closes_at: string } | null;
type Court = {
    id: number; name: string; slug: string; description: string | null; surface_type: string | null;
    court_type: 'REGULAR' | 'CENTER';
    capacity: number | null; price_per_hour: string; price_per_slot: string; currency: string;
    is_active: boolean; sort_order: number; image_url: string | null; day_hours: DayHour;
    operating_hours: OperatingHour[]; price_rules: PriceRule[];
};
type Booking = { id: number; court_id: number; customer_name: string; booking_code: string; status: string; starts_at: string; ends_at: string; is_in_use: boolean };
type Closure = { id: number; court_id: number; reason: string | null; starts_at: string; ends_at: string };
type TimeSlot = { starts_at: string; ends_at: string };

const props = defineProps<{
    date: string;
    today: string;
    now_time: string;
    date_label: string;
    timezone: string;
    range: { starts_at: string; ends_at: string };
    time_slots: TimeSlot[];
    courts: Court[];
    bookings: Booking[];
    closures: Closure[];
    stats: { booked: number; in_use: number; available_slots: number; total_slots: number; blocked_slots: number };
}>();

const dayNames = ['Thứ hai', 'Thứ ba', 'Thứ tư', 'Thứ năm', 'Thứ sáu', 'Thứ bảy', 'Chủ nhật'];
const priceDayTypes = ['WEEKDAY', 'WEEKEND'] as const;
const selectedCourtId = ref<number | null>(props.courts[0]?.id ?? null);
const courtDialogOpen = ref(false);
const lockDialogOpen = ref(false);
const bookingDialogOpen = ref(false);
const bookingCourtId = ref<number | null>(null);
const editingCourtId = ref<number | null>(null);
const imagePreview = ref<string | null>(null);

watch(() => props.courts, (courts) => {
    if (!courts.some((court) => court.id === selectedCourtId.value)) selectedCourtId.value = courts[0]?.id ?? null;
});

const selectedCourt = computed(() => props.courts.find((court) => court.id === selectedCourtId.value) ?? null);
const selectedClosures = computed(() => props.closures.filter((closure) => closure.court_id === selectedCourtId.value));
const lockCourt = computed(() => props.courts.find((court) => court.id === lockForm.court_id) ?? null);
const lockDayHours = computed(() => {
    const court = lockCourt.value;
    if (!court || !lockForm.date) return null;
    const weekday = new Date(`${lockForm.date}T12:00:00`).getDay();
    const isoWeekday = weekday === 0 ? 7 : weekday;
    return court.operating_hours.find((hour) => hour.day_of_week === isoWeekday) ?? null;
});
const lockStartOptions = computed(() => {
    const hours = lockDayHours.value;
    return !hours || hours.is_closed ? [] : timesBetween(hours.opens_at, hours.closes_at).slice(0, -1);
});
const lockEndOptions = computed(() => {
    const hours = lockDayHours.value;
    return !hours || hours.is_closed || !lockForm.starts_at ? [] : timesBetween(addMinutes(lockForm.starts_at, 30), hours.closes_at);
});

function blankHours(): OperatingHour[] {
    return dayNames.map((_, index) => ({ day_of_week: index + 1, is_closed: false, opens_at: '05:00', closes_at: '23:00' }));
}

function defaultPriceRules(courtType: 'REGULAR' | 'CENTER' = 'REGULAR'): PriceRule[] {
    const slots = [['05:00', '08:00'], ['08:00', '12:00'], ['12:00', '14:00'], ['14:00', '16:00'], ['16:00', '23:00']];
    const prices = courtType === 'CENTER'
        ? { WEEKDAY: [250000, 250000, 250000, 300000, 400000], WEEKEND: [300000, 350000, 300000, 350000, 400000] }
        : { WEEKDAY: [140000, 190000, 140000, 220000, 299000], WEEKEND: [220000, 250000, 250000, 270000, 299000] };
    return (['WEEKDAY', 'WEEKEND'] as const).flatMap((dayType) => slots.map(([starts_at, ends_at], index) => ({
        day_type: dayType, starts_at, ends_at, price_per_hour: prices[dayType][index], effective_from: '2026-04-01',
    })));
}

const courtForm = useForm({
    _method: '', name: '', slug: '', description: '', surface_type: '', court_type: 'REGULAR' as 'REGULAR' | 'CENTER', capacity: null as number | null,
    price_per_hour: 0, currency: 'VND', is_active: true, sort_order: 0,
    image: null as File | null, operating_hours: blankHours(), price_rules: defaultPriceRules(),
});

const lockForm = useForm({ court_id: props.courts[0]?.id ?? null as number | null, date: props.date, starts_at: props.range.starts_at, ends_at: addMinutes(props.range.starts_at, 30), reason: '' });
const bookingForm = useForm({
    court_id: null as number | null, date: props.date, starts_at: '', ends_at: '',
    customer_name: '', customer_email: '', customer_phone: '', customer_note: '',
});
const bookingCourt = computed(() => props.courts.find((court) => court.id === bookingCourtId.value) ?? null);
const bookingEndOptions = computed(() => {
    const court = bookingCourt.value;
    if (!court?.day_hours || court.day_hours.is_closed || !bookingForm.starts_at) return [];
    const options: string[] = [];
    let cursor = addMinutes(bookingForm.starts_at, 30);
    while (timeMinutes(cursor) <= timeMinutes(court.day_hours.closes_at)) {
        if (!rangeIsAvailable(court, bookingForm.starts_at, cursor)) break;
        options.push(cursor);
        cursor = addMinutes(cursor, 30);
    }
    return options;
});
const bookingEstimate = computed(() => {
    const court = bookingCourt.value;
    if (!court || !bookingForm.starts_at || !bookingForm.ends_at) return 0;
    let total = 0;
    for (let cursor = bookingForm.starts_at; timeMinutes(cursor) < timeMinutes(bookingForm.ends_at); cursor = addMinutes(cursor, 30)) {
        total += slotPrice(court, cursor);
    }
    return total;
});

function openCreateCourt() {
    editingCourtId.value = null;
    Object.assign(courtForm, {
        _method: '', name: '', slug: '', description: '', surface_type: '', court_type: 'REGULAR', capacity: null,
        price_per_hour: 0, currency: 'VND', is_active: true, sort_order: props.courts.length,
        image: null, operating_hours: blankHours(), price_rules: defaultPriceRules(),
    });
    courtForm.clearErrors();
    imagePreview.value = null;
    courtDialogOpen.value = true;
}

function openEditCourt(court: Court | null = selectedCourt.value) {
    if (!court) return;
    editingCourtId.value = court.id;
    const hours = blankHours().map((fallback) => court.operating_hours.find((hour) => hour.day_of_week === fallback.day_of_week) ?? fallback);
    Object.assign(courtForm, {
        _method: 'put', name: court.name, slug: court.slug, description: court.description ?? '',
        surface_type: court.surface_type ?? '', court_type: court.court_type, capacity: court.capacity, price_per_hour: Number(court.price_per_hour),
        currency: court.currency, is_active: court.is_active, sort_order: court.sort_order, image: null,
        operating_hours: hours.map((hour) => ({ ...hour })), price_rules: court.price_rules.map((rule) => ({ ...rule })),
    });
    courtForm.clearErrors();
    imagePreview.value = court.image_url;
    courtDialogOpen.value = true;
}

function saveCourt() {
    const url = editingCourtId.value ? `/admin/courts/${editingCourtId.value}` : '/admin/courts';
    courtForm.post(url, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => { courtDialogOpen.value = false; },
    });
}

function applyCourtTypePrices() {
    courtForm.price_rules = defaultPriceRules(courtForm.court_type);
    courtForm.price_per_hour = courtForm.court_type === 'CENTER' ? 250000 : 140000;
}

function chooseImage(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    courtForm.image = file;
    if (file) imagePreview.value = URL.createObjectURL(file);
}

function openLockDialog() {
    const court = selectedCourt.value ?? props.courts[0];
    if (!court) return;
    const startsAt = court.day_hours && !court.day_hours.is_closed ? court.day_hours.opens_at : props.range.starts_at;
    Object.assign(lockForm, { court_id: court.id, date: props.date, starts_at: startsAt, ends_at: addMinutes(startsAt, 30), reason: '' });
    syncLockTimes();
    lockForm.clearErrors();
    lockDialogOpen.value = true;
}

function saveClosure() {
    lockForm.post('/admin/court-closures', { preserveScroll: true, onSuccess: () => { lockDialogOpen.value = false; } });
}

function openBookingDialog(court: Court, slot: TimeSlot) {
    if (!isBookable(court, slot)) return;
    selectedCourtId.value = court.id;
    bookingCourtId.value = court.id;
    Object.assign(bookingForm, {
        court_id: court.id, date: props.date, starts_at: slot.starts_at, ends_at: slot.ends_at,
        customer_name: '', customer_email: '', customer_phone: '', customer_note: '',
    });
    bookingForm.clearErrors();
    bookingDialogOpen.value = true;
}

function saveBooking() {
    bookingForm.post('/admin/bookings', { preserveScroll: true, onSuccess: () => { bookingDialogOpen.value = false; } });
}

function removeClosure(closure: Closure) {
    if (window.confirm(`Mở lại lịch ${closure.starts_at}–${closure.ends_at}?`)) {
        router.delete(`/admin/court-closures/${closure.id}`, { preserveScroll: true });
    }
}

function goToDate(date: string) {
    router.get('/admin/courts', { date }, { preserveScroll: true, replace: true });
}

function shiftDate(days: number) {
    const value = new Date(`${props.date}T12:00:00`);
    value.setDate(value.getDate() + days);
    goToDate([value.getFullYear(), String(value.getMonth() + 1).padStart(2, '0'), String(value.getDate()).padStart(2, '0')].join('-'));
}

function timeMinutes(time: string): number {
    const [hour, minute] = time.split(':').map(Number);
    return hour * 60 + minute;
}

function addMinutes(time: string, minutes: number): string {
    const total = timeMinutes(time) + minutes;
    return `${String(Math.floor(total / 60)).padStart(2, '0')}:${String(total % 60).padStart(2, '0')}`;
}

function timesBetween(startsAt: string, endsAt: string): string[] {
    const times: string[] = [];
    for (let cursor = startsAt; timeMinutes(cursor) <= timeMinutes(endsAt); cursor = addMinutes(cursor, 30)) {
        times.push(cursor);
    }
    return times;
}

function syncLockTimes() {
    if (!lockStartOptions.value.includes(lockForm.starts_at)) {
        lockForm.starts_at = lockStartOptions.value[0] ?? '';
    }
    if (!lockEndOptions.value.includes(lockForm.ends_at)) {
        lockForm.ends_at = lockEndOptions.value[0] ?? '';
    }
}

function covers(slot: TimeSlot, event: { starts_at: string; ends_at: string }): boolean {
    return timeMinutes(slot.starts_at) >= timeMinutes(event.starts_at) && timeMinutes(slot.starts_at) < timeMinutes(event.ends_at);
}

function bookingAt(courtId: number, slot: TimeSlot): Booking | undefined {
    return props.bookings.find((booking) => booking.court_id === courtId && covers(slot, booking));
}

function closureAt(courtId: number, slot: TimeSlot): Closure | undefined {
    return props.closures.find((closure) => closure.court_id === courtId && covers(slot, closure));
}

function isPastSlot(slot: TimeSlot): boolean {
    return props.date < props.today || (props.date === props.today && slot.ends_at <= props.now_time);
}

function isCourtOpen(court: Court, slot: TimeSlot): boolean {
    const hours = court.day_hours;
    return court.is_active && !!hours && !hours.is_closed && timeMinutes(slot.starts_at) >= timeMinutes(hours.opens_at) && timeMinutes(slot.ends_at) <= timeMinutes(hours.closes_at);
}

function isAvailable(court: Court, slot: TimeSlot): boolean {
    return isCourtOpen(court, slot) && !bookingAt(court.id, slot) && !closureAt(court.id, slot);
}

function isBookable(court: Court, slot: TimeSlot): boolean {
    if (!isAvailable(court, slot) || props.date < props.today) return false;

    return props.date > props.today || slot.starts_at >= props.now_time;
}

function rangeIsAvailable(court: Court, startsAt: string, endsAt: string): boolean {
    for (let cursor = startsAt; timeMinutes(cursor) < timeMinutes(endsAt); cursor = addMinutes(cursor, 30)) {
        const slot = { starts_at: cursor, ends_at: addMinutes(cursor, 30) };
        if (!isAvailable(court, slot)) return false;
    }
    return true;
}

function scheduleDayType(): 'WEEKDAY' | 'WEEKEND' {
    const day = new Date(`${props.date}T12:00:00`).getDay();
    return day === 0 || day === 6 ? 'WEEKEND' : 'WEEKDAY';
}

function slotPrice(court: Court, startsAt: string): number {
    const rule = court.price_rules.find((item) => item.day_type === scheduleDayType() && startsAt >= item.starts_at && startsAt < item.ends_at);
    return Number(rule?.price_per_hour ?? court.price_per_hour) / 2;
}

function formatMoney(value: string | number, currency = 'VND'): string {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2 }).format(Number(value));
}
</script>

<template>
    <Head title="Quản lý sân" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1800px] space-y-5">
            <header class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · QUẢN LÝ SÂN</p>
                    <h1 class="mt-2 flex items-center gap-3 text-2xl font-bold sm:text-3xl"><CalendarDays class="size-7 text-orange-600" />Lịch đặt sân</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Theo dõi lịch theo slot 30 phút · múi giờ {{ timezone }}.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button class="court-secondary-button" type="button" @click="openCreateCourt"><Plus class="size-4" />Tạo sân</button>
                    <button class="court-secondary-button" type="button" :disabled="!selectedCourt" @click="openEditCourt()"><CircleDollarSign class="size-4" />Sân & giá</button>
                    <button class="court-primary-button" type="button" :disabled="!selectedCourt" @click="openLockDialog"><LockKeyhole class="size-4" />Khóa lịch</button>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <article class="court-stat"><span class="court-stat-icon bg-orange-500/15 text-orange-700 dark:text-orange-400"><CalendarDays class="size-5" /></span><div><p>Ngày đang xem</p><strong>{{ date_label }}</strong></div></article>
                <article class="court-stat"><span class="court-stat-icon bg-emerald-500/15 text-emerald-700 dark:text-emerald-400"><CheckCircle2 class="size-5" /></span><div><p>Đã đặt</p><strong>{{ stats.booked }}</strong></div></article>
                <article class="court-stat"><span class="court-stat-icon bg-amber-500/15 text-amber-700 dark:text-amber-400"><Clock3 class="size-5" /></span><div><p>Đang sử dụng</p><strong>{{ stats.in_use }}</strong></div></article>
                <article class="court-stat"><span class="court-stat-icon bg-muted text-muted-foreground"><MapPin class="size-5" /></span><div><p>Còn trống</p><strong>{{ stats.available_slots }} / {{ stats.total_slots }}</strong></div></article>
            </section>

            <section class="grid gap-5 2xl:grid-cols-[minmax(0,1fr)_320px]">
                <div class="min-w-0 space-y-4">
                    <div class="flex flex-col gap-3 rounded-xl border border-border bg-card p-3 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex items-center gap-2">
                            <button class="court-icon-button" type="button" aria-label="Ngày trước" @click="shiftDate(-1)"><ChevronLeft class="size-4" /></button>
                            <input :value="date" type="date" class="h-10 rounded-lg border border-input bg-background px-3 text-sm font-semibold" @change="goToDate(($event.target as HTMLInputElement).value)" />
                            <button class="court-icon-button" type="button" aria-label="Ngày sau" @click="shiftDate(1)"><ChevronRight class="size-4" /></button>
                            <button class="court-primary-button h-10" type="button" @click="goToDate(today)">Hôm nay</button>
                        </div>
                        <p class="text-sm text-muted-foreground">Khung giờ: <strong class="text-foreground">{{ range.starts_at }}–{{ range.ends_at }}</strong> · {{ stats.blocked_slots }} slot đã khóa</p>
                    </div>

                    <div class="court-schedule-scroll rounded-xl border border-border bg-card">
                        <div v-if="courts.length" class="court-grid" :style="{ '--court-count': courts.length }">
                            <div class="court-grid-head court-time-head">Giờ</div>
                            <button v-for="court in courts" :key="`head-${court.id}`" type="button" class="court-grid-head text-left" :class="selectedCourtId === court.id && 'court-grid-head--active'" @click="selectedCourtId = court.id">
                                <span class="block truncate font-bold">{{ court.name }}</span>
                                <span class="mt-1 block text-xs font-semibold text-amber-700 dark:text-amber-300">{{ formatMoney(court.price_per_hour, court.currency) }} /h</span>
                            </button>

                            <template v-for="slot in time_slots" :key="slot.starts_at">
                                <div class="court-time-cell">{{ slot.starts_at }}–{{ slot.ends_at }}</div>
                                <button v-for="court in courts" :key="`${slot.starts_at}-${court.id}`" type="button" class="court-slot" :class="{
                                    'court-slot--closed': !isCourtOpen(court, slot),
                                    'court-slot--past': isPastSlot(slot) && isCourtOpen(court, slot) && !bookingAt(court.id, slot) && !closureAt(court.id, slot),
                                    'court-slot--past-booked': isPastSlot(slot) && !!bookingAt(court.id, slot),
                                    'court-slot--booked': bookingAt(court.id, slot) && !bookingAt(court.id, slot)?.is_in_use,
                                    'court-slot--active': bookingAt(court.id, slot)?.is_in_use,
                                    'court-slot--locked': closureAt(court.id, slot),
                                    'court-slot--available': isBookable(court, slot),
                                }" :title="isBookable(court, slot) ? `${formatMoney(slotPrice(court, slot.starts_at), court.currency)} · Click để đặt hộ khách` : ''" @click="isBookable(court, slot) ? openBookingDialog(court, slot) : selectedCourtId = court.id">
                                    <span v-if="bookingAt(court.id, slot)?.starts_at === slot.starts_at" class="court-slot-label">{{ bookingAt(court.id, slot)?.customer_name }}</span>
                                    <span v-else-if="closureAt(court.id, slot)?.starts_at === slot.starts_at" class="court-slot-label"><LockKeyhole class="size-3.5" />{{ closureAt(court.id, slot)?.reason || 'Đã khóa' }}</span>
                                    <span v-else-if="!isCourtOpen(court, slot)" class="sr-only">Ngoài giờ hoạt động</span>
                                    <span v-else-if="isBookable(court, slot)" class="court-slot-add"><UserPlus class="size-3.5" />Đặt hộ</span>
                                </button>
                            </template>
                        </div>
                        <div v-else class="grid min-h-[460px] place-items-center p-8 text-center">
                            <div><div class="mx-auto grid size-14 place-items-center rounded-full bg-muted text-muted-foreground"><MapPin class="size-6" /></div><h2 class="mt-4 text-lg font-bold">Chưa có sân nào</h2><p class="mt-2 text-sm text-muted-foreground">Tạo sân đầu tiên để bắt đầu cấu hình lịch và giá.</p><button type="button" class="court-primary-button mx-auto mt-5" @click="openCreateCourt"><Plus class="size-4" />Tạo sân</button></div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted-foreground">
                        <span class="court-legend"><i class="bg-emerald-600" />Đã đặt</span><span class="court-legend"><i class="bg-amber-500" />Đang sử dụng</span><span class="court-legend"><i class="court-past-legend" />Đã qua</span><span class="court-legend"><i class="court-past-booked-legend" />Đã đặt, giờ đã qua</span><span class="court-legend"><i class="court-locked-pattern" />Đã khóa</span><span class="court-legend"><i class="border border-border bg-card" />Trống</span>
                    </div>
                </div>

                <aside class="space-y-4">
                    <section v-if="selectedCourt" class="rounded-xl border border-border bg-card p-4">
                        <div class="flex items-start gap-3">
                            <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-xl bg-muted text-muted-foreground"><img v-if="selectedCourt.image_url" :src="selectedCourt.image_url" :alt="selectedCourt.name" class="size-full object-cover" /><MapPin v-else class="size-6" /></div>
                            <div class="min-w-0 flex-1"><h2 class="truncate text-xl font-bold">{{ selectedCourt.name }}</h2><span :class="['mt-2 inline-flex rounded-full px-2.5 py-1 text-xs font-semibold', selectedCourt.is_active ? 'bg-emerald-500/15 text-emerald-800 dark:text-emerald-300' : 'bg-muted text-muted-foreground']">{{ selectedCourt.is_active ? 'Hoạt động' : 'Tạm ngưng' }}</span></div>
                            <button class="court-icon-button" type="button" aria-label="Chỉnh sửa sân" @click="openEditCourt(selectedCourt)"><Pencil class="size-4" /></button>
                        </div>
                        <dl class="mt-5 divide-y divide-border border-y border-border text-sm">
                            <div class="court-detail"><dt>Hạng sân</dt><dd>{{ selectedCourt.court_type === 'CENTER' ? 'Sân trung tâm' : 'Sân thường' }}</dd></div>
                            <div class="court-detail"><dt>Mặt sân</dt><dd>{{ selectedCourt.surface_type || 'Chưa cập nhật' }}</dd></div>
                            <div class="court-detail"><dt>Giá thấp nhất</dt><dd>{{ formatMoney(selectedCourt.price_per_slot, selectedCourt.currency) }} / 30 phút</dd></div>
                            <div class="court-detail"><dt>Giờ hôm nay</dt><dd>{{ selectedCourt.day_hours && !selectedCourt.day_hours.is_closed ? `${selectedCourt.day_hours.opens_at}–${selectedCourt.day_hours.closes_at}` : 'Đóng cửa' }}</dd></div>
                        </dl>
                        <button type="button" class="court-secondary-button mt-4 w-full" @click="openEditCourt(selectedCourt)"><CircleDollarSign class="size-4" />Cập nhật thông tin & giá</button>
                    </section>

                    <section class="rounded-xl border border-border bg-card p-4">
                        <div class="flex items-start gap-3"><span class="grid size-10 place-items-center rounded-full bg-orange-500/15 text-orange-700 dark:text-orange-400"><LockKeyhole class="size-4" /></span><div><h2 class="font-bold">Khóa lịch nhanh</h2><p class="mt-1 text-xs leading-5 text-muted-foreground">Chặn đặt sân trong một khoảng thời gian chưa có booking.</p></div></div>
                        <button type="button" class="court-primary-button mt-4 w-full" :disabled="!selectedCourt" @click="openLockDialog"><LockKeyhole class="size-4" />Khóa lịch</button>
                        <div v-if="selectedClosures.length" class="mt-4 space-y-2 border-t border-border pt-4">
                            <div v-for="closure in selectedClosures" :key="closure.id" class="flex items-center justify-between gap-3 rounded-lg bg-muted p-3 text-xs"><div><strong>{{ closure.starts_at }}–{{ closure.ends_at }}</strong><p class="mt-1 text-muted-foreground">{{ closure.reason || 'Không có lý do' }}</p></div><button type="button" class="court-icon-button shrink-0" title="Mở lịch" @click="removeClosure(closure)"><UnlockKeyhole class="size-4" /></button></div>
                        </div>
                    </section>
                </aside>
            </section>
        </div>
    </main>

    <Teleport to="body">
        <div v-if="courtDialogOpen" class="court-modal-backdrop" @click.self="courtDialogOpen = false">
            <form class="court-modal max-w-5xl" @submit.prevent="saveCourt">
                <header class="court-modal-header"><div><p class="court-eyebrow">QUẢN LÝ SÂN</p><h2 class="text-xl font-bold">{{ editingCourtId ? 'Cập nhật sân & giá' : 'Tạo sân mới' }}</h2></div><button type="button" class="court-icon-button" aria-label="Đóng" @click="courtDialogOpen = false"><X class="size-5" /></button></header>
                <div class="grid max-h-[72vh] gap-6 overflow-y-auto p-5 lg:grid-cols-[minmax(0,1fr)_380px] sm:p-6">
                    <div class="space-y-4">
                        <div class="grid gap-4 sm:grid-cols-2"><label class="court-field sm:col-span-2">Tên sân *<input v-model="courtForm.name" required maxlength="150" /></label><label class="court-field">Đường dẫn<input v-model="courtForm.slug" maxlength="180" placeholder="Tự tạo theo tên sân" /></label><label class="court-field">Hạng sân *<select v-model="courtForm.court_type" required @change="applyCourtTypePrices"><option value="REGULAR">Sân thường</option><option value="CENTER">Sân trung tâm</option></select></label><label class="court-field sm:col-span-2">Loại mặt sân<input v-model="courtForm.surface_type" maxlength="80" placeholder="Mái che · Mặt acrylic" /></label></div>
                        <label class="court-field">Mô tả<textarea v-model="courtForm.description" rows="4" maxlength="5000" /></label>
                        <div class="grid gap-4 sm:grid-cols-2"><label class="court-field">Giá mặc định/giờ *<input v-model.number="courtForm.price_per_hour" required type="number" min="0" step="1000" /></label><label class="court-field">Tiền tệ *<input v-model="courtForm.currency" required maxlength="3" /></label><label class="court-field">Sức chứa<input v-model.number="courtForm.capacity" type="number" min="1" max="100" /></label><label class="court-field">Thứ tự<input v-model.number="courtForm.sort_order" required type="number" min="0" /></label></div>
                        <label class="flex items-center justify-between rounded-xl border border-border p-4 text-sm font-semibold"><span><strong class="block">Mở nhận đặt sân</strong><small class="mt-1 block font-normal text-muted-foreground">Sân tạm ngưng vẫn được lưu nhưng không nhận booking mới.</small></span><input v-model="courtForm.is_active" type="checkbox" class="size-5 accent-orange-500" /></label>
                        <label class="court-field">Ảnh đại diện<div class="mt-1 flex items-center gap-3"><div class="grid size-20 place-items-center overflow-hidden rounded-lg bg-muted"><img v-if="imagePreview" :src="imagePreview" alt="Xem trước ảnh sân" class="size-full object-cover" /><ImagePlus v-else class="size-5 text-muted-foreground" /></div><span class="court-secondary-button cursor-pointer"><ImagePlus class="size-4" />Chọn ảnh<input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="chooseImage" /></span></div></label>
                    </div>
                    <div><h3 class="font-bold">Giờ hoạt động hàng tuần</h3><p class="mt-1 text-xs leading-5 text-muted-foreground">Một khung giờ mỗi ngày, cùng ngày và theo bước 30 phút.</p><div class="mt-4 space-y-2"><div v-for="(hour, index) in courtForm.operating_hours" :key="hour.day_of_week" class="grid grid-cols-[88px_1fr] gap-3 rounded-lg border border-border p-3"><label class="flex items-center gap-2 text-sm font-semibold"><input v-model="hour.is_closed" type="checkbox" class="accent-orange-500" />{{ dayNames[index] }}</label><div v-if="!hour.is_closed" class="grid grid-cols-[1fr_auto_1fr] items-center gap-2"><input v-model="hour.opens_at" type="time" step="1800" class="court-time-input" /><span>–</span><input v-model="hour.closes_at" type="time" step="1800" class="court-time-input" /></div><span v-else class="text-sm text-muted-foreground">Đóng cửa</span></div></div></div>
                    <section class="rounded-xl border border-border bg-muted/30 p-4 lg:col-span-2"><div class="flex flex-wrap items-end justify-between gap-2"><div><h3 class="font-bold">Bảng giá theo khung giờ</h3><p class="mt-1 text-xs text-muted-foreground">Áp dụng từ 01/04/2026, theo bảng giá BALLBURN đã cung cấp.</p></div><span class="rounded-full bg-orange-500/10 px-3 py-1 text-xs font-bold text-orange-700 dark:text-orange-300">{{ courtForm.court_type === 'CENTER' ? 'Sân trung tâm' : 'Sân thường' }}</span></div><div class="mt-4 grid gap-4 md:grid-cols-2"><div v-for="dayType in priceDayTypes" :key="dayType" class="rounded-lg border border-border bg-card p-3"><h4 class="text-sm font-bold">{{ dayType === 'WEEKDAY' ? 'Thứ 2 – Thứ 6' : 'Thứ 7 & Chủ nhật' }}</h4><div class="mt-3 space-y-2"><label v-for="rule in courtForm.price_rules.filter((item) => item.day_type === dayType)" :key="`${dayType}-${rule.starts_at}`" class="grid grid-cols-[95px_1fr] items-center gap-3 text-xs font-semibold"><span>{{ rule.starts_at }}–{{ rule.ends_at }}</span><span class="relative"><input v-model.number="rule.price_per_hour" type="number" min="0" step="1000" class="pr-10" /><small class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-muted-foreground">đ/h</small></span></label></div></div></div></section>
                    <div v-if="Object.keys(courtForm.errors).length" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive lg:col-span-2">{{ Object.values(courtForm.errors)[0] }}</div>
                </div>
                <footer class="court-modal-footer"><button type="button" class="court-secondary-button" :disabled="courtForm.processing" @click="courtDialogOpen = false">Hủy</button><button type="submit" class="court-primary-button" :disabled="courtForm.processing"><Save class="size-4" />{{ courtForm.processing ? 'Đang lưu…' : 'Lưu sân' }}</button></footer>
            </form>
        </div>

        <div v-if="bookingDialogOpen" class="court-modal-backdrop" @click.self="bookingDialogOpen = false">
            <form class="court-modal max-w-xl" @submit.prevent="saveBooking">
                <header class="court-modal-header"><div><p class="court-eyebrow">ADMIN BOOK HỘ KHÁCH</p><h2 class="text-xl font-bold">Tạo booking · {{ bookingCourt?.name }}</h2></div><button type="button" class="court-icon-button" aria-label="Đóng" @click="bookingDialogOpen = false"><X class="size-5" /></button></header>
                <div class="space-y-4 p-5 sm:p-6">
                    <div class="grid grid-cols-3 gap-3 rounded-xl border border-orange-500/20 bg-orange-500/5 p-4 text-sm"><div><span class="block text-xs text-muted-foreground">Ngày</span><strong>{{ date_label }}</strong></div><div><span class="block text-xs text-muted-foreground">Bắt đầu</span><strong>{{ bookingForm.starts_at }}</strong></div><label class="court-field">Kết thúc<select v-model="bookingForm.ends_at" required><option v-for="endTime in bookingEndOptions" :key="endTime" :value="endTime">{{ endTime }}</option></select></label></div>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="court-field sm:col-span-2">Tên khách hàng *<input v-model="bookingForm.customer_name" required maxlength="150" autofocus /></label><label class="court-field">Email *<input v-model="bookingForm.customer_email" required type="email" maxlength="255" /></label><label class="court-field">Số điện thoại<input v-model="bookingForm.customer_phone" type="tel" maxlength="30" /></label></div>
                    <label class="court-field">Ghi chú khách hàng<textarea v-model="bookingForm.customer_note" rows="3" maxlength="1000" placeholder="Nhu cầu thuê vợt, số người chơi..." /></label>
                    <div class="flex items-center justify-between gap-4 rounded-xl bg-muted p-4"><div><p class="text-xs text-muted-foreground">Tạm tính theo bảng giá</p><strong class="mt-1 block text-xl text-orange-700 dark:text-orange-300">{{ formatMoney(bookingEstimate, bookingCourt?.currency) }}</strong></div><div class="text-right text-xs leading-5 text-muted-foreground">Booking được xác nhận ngay<br />Không cần chờ thanh toán</div></div>
                    <div v-if="Object.keys(bookingForm.errors).length" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{{ Object.values(bookingForm.errors)[0] }}</div>
                </div>
                <footer class="court-modal-footer"><button type="button" class="court-secondary-button" :disabled="bookingForm.processing" @click="bookingDialogOpen = false">Hủy</button><button type="submit" class="court-primary-button" :disabled="bookingForm.processing || !bookingEndOptions.length"><UserPlus class="size-4" />{{ bookingForm.processing ? 'Đang tạo…' : 'Xác nhận đặt hộ' }}</button></footer>
            </form>
        </div>

        <div v-if="lockDialogOpen" class="court-modal-backdrop" @click.self="lockDialogOpen = false">
            <form class="court-modal max-w-lg" @submit.prevent="saveClosure">
                <header class="court-modal-header"><div><p class="court-eyebrow">LỊCH SÂN</p><h2 class="text-xl font-bold">Khóa lịch nhanh</h2></div><button type="button" class="court-icon-button" aria-label="Đóng" @click="lockDialogOpen = false"><X class="size-5" /></button></header>
                <div class="space-y-4 p-5 sm:p-6">
                    <label class="court-field">Sân *<select v-model="lockForm.court_id" required @change="syncLockTimes"><option v-for="court in courts" :key="court.id" :value="court.id">{{ court.name }}</option></select></label>
                    <label class="court-field">Ngày *<input v-model="lockForm.date" required type="date" @change="syncLockTimes" /></label>
                    <div class="grid grid-cols-2 gap-3"><label class="court-field">Bắt đầu *<select v-model="lockForm.starts_at" required :disabled="!lockStartOptions.length" @change="syncLockTimes"><option v-for="time in lockStartOptions" :key="time" :value="time">{{ time }}</option></select></label><label class="court-field">Kết thúc *<select v-model="lockForm.ends_at" required :disabled="!lockEndOptions.length"><option v-for="time in lockEndOptions" :key="time" :value="time">{{ time }}</option></select></label></div>
                    <label class="court-field">Lý do<input v-model="lockForm.reason" maxlength="255" placeholder="Bảo trì, sự kiện riêng..." /></label>
                    <div v-if="Object.keys(lockForm.errors).length" class="rounded-lg bg-destructive/10 px-4 py-3 text-sm text-destructive">{{ Object.values(lockForm.errors)[0] }}</div>
                </div>
                <footer class="court-modal-footer"><button type="button" class="court-secondary-button" :disabled="lockForm.processing" @click="lockDialogOpen = false">Hủy</button><button type="submit" class="court-primary-button" :disabled="lockForm.processing || !lockEndOptions.length"><LockKeyhole class="size-4" />{{ lockForm.processing ? 'Đang khóa…' : 'Khóa lịch' }}</button></footer>
            </form>
        </div>
    </Teleport>
</template>

<style scoped>
.court-secondary-button,.court-primary-button,.court-icon-button{display:inline-flex;align-items:center;justify-content:center;gap:.5rem;border-radius:.65rem;font-size:.875rem;font-weight:700;transition:.2s}.court-secondary-button{min-height:2.75rem;border:1px solid var(--border);padding:.65rem 1rem;background:var(--card)}.court-secondary-button:hover,.court-icon-button:hover{border-color:#f97316;color:#c2410c}.dark .court-secondary-button:hover,.dark .court-icon-button:hover{color:#fb923c}.court-primary-button{min-height:2.75rem;padding:.65rem 1.1rem;background:#f97316;color:#fff}.court-primary-button:hover{background:#ea580c}.court-primary-button:disabled,.court-secondary-button:disabled{cursor:not-allowed;opacity:.45}.court-icon-button{width:2.5rem;height:2.5rem;border:1px solid var(--border);background:var(--card)}.court-stat{display:flex;min-height:5.5rem;align-items:center;gap:1rem;border:1px solid var(--border);border-radius:.8rem;background:var(--card);padding:1rem}.court-stat-icon{display:grid;width:2.75rem;height:2.75rem;flex:none;place-items:center;border-radius:999px}.court-stat p{font-size:.8rem;color:var(--muted-foreground)}.court-stat strong{display:block;margin-top:.3rem;font-size:1.05rem}.court-schedule-scroll{max-height:min(70vh,760px);overflow:auto}.court-grid{display:grid;grid-template-columns:110px repeat(var(--court-count),minmax(150px,1fr));min-width:calc(110px + var(--court-count) * 150px)}.court-grid-head{position:sticky;top:0;z-index:2;min-height:4.2rem;border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--card);padding:.8rem}.court-grid-head--active{background:color-mix(in srgb,#f97316 10%,var(--card));box-shadow:inset 0 3px #f97316}.court-time-head{left:0;z-index:3;display:grid;place-items:center}.court-time-cell{position:sticky;left:0;z-index:1;display:grid;min-height:3.25rem;place-items:center;border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--card);padding:.5rem;font-size:.75rem;font-weight:700}.court-slot{min-height:3.25rem;border-right:1px solid var(--border);border-bottom:1px solid var(--border);background:var(--card);padding:.35rem;text-align:center}.court-slot:hover{box-shadow:inset 0 0 0 1px #f97316}.court-slot--closed{background:var(--muted);opacity:.6}.court-slot--past{background:#f1f2f4;color:#667085}.dark .court-slot--past{background:#34373d;color:#c3c7ce}.court-slot--booked{background:#15803d;color:#fff}.court-slot--active{background:#d97706;color:#fff}.court-slot--past-booked{background:#6b7280;color:#fff}.dark .court-slot--past-booked{background:#555a63;color:#fff}.court-slot--locked,.court-locked-pattern{background-color:color-mix(in srgb,var(--muted) 90%,#000);background-image:repeating-linear-gradient(135deg,transparent 0,transparent 7px,color-mix(in srgb,var(--foreground) 16%,transparent) 7px,color-mix(in srgb,var(--foreground) 16%,transparent) 9px)}.court-slot-label{display:flex;align-items:center;justify-content:center;gap:.35rem;font-size:.75rem;font-weight:800}.court-legend{display:inline-flex;align-items:center;gap:.5rem}.court-legend i{width:1.25rem;height:1.25rem;border-radius:.3rem}.court-detail{display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.9rem 0}.court-detail dt{color:var(--muted-foreground)}.court-detail dd{text-align:right;font-weight:700}.court-modal-backdrop{position:fixed;inset:0;z-index:100;display:flex;align-items:center;justify-content:center;overflow-y:auto;background:rgb(0 0 0/.7);padding:1rem;backdrop-filter:blur(4px)}.court-modal{width:100%;overflow:hidden;border:1px solid var(--border);border-radius:1rem;background:var(--card);color:var(--card-foreground);box-shadow:0 24px 80px rgb(0 0 0/.35)}.court-modal-header,.court-modal-footer{display:flex;align-items:center;justify-content:space-between;gap:1rem;border-bottom:1px solid var(--border);padding:1rem 1.25rem}.court-modal-footer{justify-content:flex-end;border-top:1px solid var(--border);border-bottom:0}.court-eyebrow{font-size:.7rem;font-weight:800;letter-spacing:.16em;color:#c2410c}.dark .court-eyebrow{color:#fb923c}.court-field{display:grid;gap:.45rem;font-size:.875rem;font-weight:700}.court-field input,.court-field textarea,.court-field select,.court-time-input{width:100%;border:1px solid var(--input);border-radius:.55rem;background:var(--background);padding:.65rem .75rem;color:var(--foreground);font-size:.875rem;font-weight:400;outline:none}.court-field input:focus,.court-field textarea:focus,.court-field select:focus,.court-time-input:focus{border-color:#f97316;box-shadow:0 0 0 2px rgb(249 115 22/.14)}
.court-slot--available{cursor:pointer}.court-slot-add{display:inline-flex;align-items:center;justify-content:center;gap:.3rem;color:#c2410c;font-size:.7rem;font-weight:800;opacity:0;transition:opacity .15s}.dark .court-slot-add{color:#fb923c}.court-slot--available:hover .court-slot-add,.court-slot--available:focus-visible .court-slot-add{opacity:1}.court-slot--closed,.court-slot--booked,.court-slot--active,.court-slot--locked{cursor:default}
.court-past-legend{background:#f1f2f4}.dark .court-past-legend{background:#34373d}.court-past-booked-legend{background:#6b7280}.dark .court-past-booked-legend{background:#555a63}
</style>
