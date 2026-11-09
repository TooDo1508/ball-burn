<script setup>
import { computed, onUnmounted, reactive, ref, watch } from 'vue';
import { useRoute } from 'vue-router';
import QRCode from 'qrcode';
import BookingCalendar from '../components/booking/BookingCalendar.vue';
import BookingStepper from '../components/booking/BookingStepper.vue';
import BookingSummary from '../components/booking/BookingSummary.vue';
import TimeSlotGrid from '../components/booking/TimeSlotGrid.vue';

const route = useRoute();
const venueTimezone = document.querySelector('meta[name="venue-timezone"]')?.getAttribute('content') ?? 'Asia/Ho_Chi_Minh';
const data = ref(null);
const selectedCourtId = ref(null);
const selectedDate = ref(venueDate());
const bookingMode = ref('single');
const selectedMultiKeys = ref([]);
const showCalendar = ref(false);
const loading = ref(false);
const submitting = ref(false);
const pageError = ref('');
const bookingStatus = ref(null);
const currentStep = ref(1);
const paymentSession = ref(null);
const statusToken = ref('');
const qrCodeDataUrl = ref('');
const paymentQrUrl = ref('');
const paymentSecondsLeft = ref(300);
const holdToken = ref('');
const holdExpiresAt = ref(null);
const receiptFile = ref(null);
const receiptPreviewUrl = ref('');
const fieldErrors = ref({});
const form = reactive({ customer_name: '', customer_email: '', customer_phone: '', customer_note: '' });
let availabilityRequest = 0;
let paymentCountdownTimer = null;
let holdSyncQueue = Promise.resolve();
let holdSelectionVersion = 0;

const selectedCourt = computed(() => data.value?.courts?.find((court) => court.id === selectedCourtId.value) ?? null);
const slots = computed(() => data.value?.slots ?? []);
const matrixTimeSlots = computed(() => data.value?.grid_time_slots ?? []);
const selectedCourtTimes = computed(() => selectedMultiKeys.value
    .filter((key) => Number(key.split('|')[0]) === selectedCourtId.value)
    .map((key) => key.split('|')[1]));
const selectedMultiSlots = computed(() => selectedMultiKeys.value.map((key) => {
    const [courtIdValue, startsAt] = key.split('|');
    const courtId = Number(courtIdValue);
    const court = data.value?.courts?.find((item) => item.id === courtId);
    const slot = matrixSlot(courtId, startsAt);
    return court && slot ? { key, court_id: courtId, court, ...slot } : null;
}).filter(Boolean).sort((left, right) => left.court.sort_order - right.court.sort_order || left.starts_at.localeCompare(right.starts_at)));
const selectedMultiSegments = computed(() => {
    const segments = [];
    for (const item of selectedMultiSlots.value) {
        const previous = segments[segments.length - 1];
        if (previous && previous.court_id === item.court_id && previous.ends_at === item.starts_at) {
            previous.ends_at = item.ends_at;
            previous.total += Number(item.amount);
            previous.slot_count++;
        } else {
            segments.push({ court_id: item.court_id, court: item.court, starts_at: item.starts_at, ends_at: item.ends_at, total: Number(item.amount), slot_count: 1 });
        }
    }
    return segments;
});
const selectedBookingSegments = computed(() => bookingMode.value === 'single'
    ? selectedMultiSegments.value.filter((segment) => segment.court_id === selectedCourtId.value)
    : selectedMultiSegments.value);
const selectedBookingTotal = computed(() => selectedBookingSegments.value.reduce((sum, segment) => sum + segment.total, 0));
const summaryItems = computed(() => selectedBookingSegments.value.map((segment) => ({
    court_name: segment.court.name,
    starts_at: segment.starts_at,
    ends_at: segment.ends_at,
    total: segment.total,
    currency: segment.court.currency,
    slot_count: segment.slot_count,
})));
const activeStep = computed(() => bookingStatus.value ? 4 : currentStep.value);
const paymentExpired = computed(() => Boolean(paymentSession.value) && paymentSecondsLeft.value <= 0);
const paymentCountdownLabel = computed(() => `${String(Math.floor(paymentSecondsLeft.value / 60)).padStart(2, '0')}:${String(paymentSecondsLeft.value % 60).padStart(2, '0')}`);
const summaryActionLabel = computed(() => ({ 1: 'Tiếp tục: thông tin', 2: 'Tiếp tục: thanh toán', 3: 'Gửi ảnh chuyển khoản' })[currentStep.value] ?? 'Hoàn tất');
const summaryActionDisabled = computed(() => currentStep.value === 1
    ? selectedBookingSegments.value.length === 0
    : currentStep.value === 2
        ? !form.customer_name.trim() || !form.customer_phone.trim() || (Boolean(form.customer_email.trim()) && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email.trim()))
        : currentStep.value === 3
            ? !receiptFile.value || paymentExpired.value
            : true);
const summaryNote = computed(() => currentStep.value === 1
    ? 'Chọn sân, ngày và các khung giờ bạn muốn đặt.'
    : currentStep.value === 2
        ? 'Nhập thông tin liên hệ để xem mã thanh toán. Đơn chưa được tạo ở bước này.'
        : 'Gửi ảnh chuyển khoản trước khi mã thanh toán hết hạn. Booking chỉ được xác nhận sau khi nhân viên đối soát.');

watch(() => [paymentSession.value?.payment_reference, paymentSession.value?.total_amount, paymentSession.value?.currency, paymentQrUrl.value], async ([reference, total, currency, configuredQrUrl]) => {
    qrCodeDataUrl.value = '';
    if (!reference || configuredQrUrl) return;
    const payload = `BALLBURN|${reference}|${total}|${currency}`;
    try {
        qrCodeDataUrl.value = await QRCode.toDataURL(payload, {
            width: 260,
            margin: 2,
            color: { dark: '#123b2d', light: '#ffffff' },
        });
    } catch {
        pageError.value = 'Không thể tạo mã QR. Vui lòng tải lại trang và thử lại.';
    }
});

fetch('/api/site/contact-info', { headers: { Accept: 'application/json' } })
    .then(async (response) => {
        if (!response.ok) return;
        const result = await response.json();
        paymentQrUrl.value = result.data?.payment_qr_url ?? '';
    })
    .catch(() => {
        // Keep the booking reference QR fallback if public settings cannot be loaded.
    });

function venueDate() {
    const parts = new Intl.DateTimeFormat('en', { timeZone: venueTimezone, year: 'numeric', month: '2-digit', day: '2-digit' }).formatToParts(new Date());
    const values = Object.fromEntries(parts.map((part) => [part.type, part.value]));
    return `${values.year}-${values.month}-${values.day}`;
}

function startPaymentCountdown(deadline) {
    if (paymentCountdownTimer) window.clearInterval(paymentCountdownTimer);
    const updateRemaining = () => {
        paymentSecondsLeft.value = Math.max(0, Math.ceil((Date.parse(deadline) - Date.now()) / 1000));
        if (paymentSecondsLeft.value === 0 && paymentCountdownTimer) {
            window.clearInterval(paymentCountdownTimer);
            paymentCountdownTimer = null;
        }
    };
    updateRemaining();
    paymentCountdownTimer = window.setInterval(updateRemaining, 1000);
}

function chooseReceipt(event) {
    const file = event.target.files?.[0] ?? null;
    receiptFile.value = file;
    fieldErrors.value.proof_image = undefined;
    if (receiptPreviewUrl.value) URL.revokeObjectURL(receiptPreviewUrl.value);
    receiptPreviewUrl.value = file ? URL.createObjectURL(file) : '';
}

function createStatusToken() {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let token = '';
    while (token.length < 64) {
        const bytes = crypto.getRandomValues(new Uint8Array(80));
        for (const byte of bytes) {
            if (byte >= 248) continue;
            token += alphabet[byte % alphabet.length];
            if (token.length === 64) break;
        }
    }
    return token;
}

function clearPaymentSelection() {
    paymentSession.value = null;
    statusToken.value = '';
    qrCodeDataUrl.value = '';
    paymentSecondsLeft.value = 300;
    if (paymentCountdownTimer) window.clearInterval(paymentCountdownTimer);
    paymentCountdownTimer = null;
    receiptFile.value = null;
    if (receiptPreviewUrl.value) URL.revokeObjectURL(receiptPreviewUrl.value);
    receiptPreviewUrl.value = '';
}

onUnmounted(() => {
    if (paymentCountdownTimer) window.clearInterval(paymentCountdownTimer);
    if (receiptPreviewUrl.value) URL.revokeObjectURL(receiptPreviewUrl.value);
    if (holdToken.value && !bookingStatus.value) {
        fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: holdToken.value }) });
    }
});

function formatMoney(amount, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, maximumFractionDigits: currency === 'VND' ? 0 : 2 }).format(Number(amount));
}

function formatDate(value) {
    if (!value) return '';
    const [year, month, day] = value.split('-').map(Number);
    return new Intl.DateTimeFormat('vi-VN', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }).format(new Date(year, month - 1, day, 12));
}

async function loadAvailability() {
    if (bookingStatus.value) return;
    const requestId = ++availabilityRequest;
    loading.value = true;
    pageError.value = '';
    try {
        const query = new URLSearchParams({ date: selectedDate.value });
        if (selectedCourtId.value) query.set('court_id', String(selectedCourtId.value));
        if (holdToken.value) query.set('hold_token', holdToken.value);
        const response = await fetch(`/booking/data?${query}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (requestId !== availabilityRequest) return;
        if (!response.ok) throw new Error(result.message || 'Không thể tải lịch sân.');
        data.value = result.data;
        if (!selectedCourtId.value || !result.data.courts.some((court) => court.id === selectedCourtId.value)) {
            selectedCourtId.value = result.data.selected_court_id;
        }
        selectedMultiKeys.value = selectedMultiKeys.value.filter((key) => {
            const [courtId, startsAt] = key.split('|');
            return matrixSlot(Number(courtId), startsAt)?.status === 'available';
        });
    } catch (error) {
        if (requestId !== availabilityRequest) return;
        pageError.value = error instanceof Error ? error.message : 'Không thể tải lịch sân.';
    } finally {
        if (requestId === availabilityRequest) loading.value = false;
    }
}

function chooseCourt(courtId) {
    if (courtId === selectedCourtId.value) return;
    holdSelectionVersion++;
    if (holdToken.value) fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: holdToken.value }) });
    holdToken.value = ''; holdExpiresAt.value = null;
    clearPaymentSelection();
    selectedCourtId.value = courtId;
    selectedMultiKeys.value = selectedMultiKeys.value.filter((key) => Number(key.split('|')[0]) === courtId);
    loadAvailability();
}

function chooseDate(date) {
    showCalendar.value = false;
    if (date === selectedDate.value) return;
    holdSelectionVersion++;
    if (holdToken.value) fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: holdToken.value }) });
    holdToken.value = ''; holdExpiresAt.value = null;
    clearPaymentSelection();
    selectedDate.value = date;
    selectedMultiKeys.value = [];
    loadAvailability();
}

function setBookingMode(mode) {
    if (mode === bookingMode.value) return;
    clearPaymentSelection();
    bookingMode.value = mode;
    pageError.value = '';
    if (mode === 'single') {
        const filteredKeys = selectedMultiKeys.value.filter((key) => Number(key.split('|')[0]) === selectedCourtId.value);
        if (filteredKeys.length !== selectedMultiKeys.value.length) {
            selectedMultiKeys.value = filteredKeys;
            queueHoldSync();
        }
    }
}

function matrixSlot(courtId, startsAt) {
    return data.value?.court_slots?.[courtId]?.find((slot) => slot.starts_at === startsAt) ?? null;
}

function isMultiSlotSelected(courtId, startsAt) {
    return selectedMultiKeys.value.includes(`${courtId}|${startsAt}`);
}

async function toggleMultiSlot(courtId, startsAt) {
    const slot = matrixSlot(courtId, startsAt);
    if (!slot || slot.status !== 'available') return;
    const key = `${courtId}|${startsAt}`;
    if (!isMultiSlotSelected(courtId, startsAt) && selectedMultiSlots.value.length >= 64) {
        pageError.value = 'Có thể chọn tối đa 64 khung giờ 30 phút trong một lần đặt.';
        return;
    }
    pageError.value = '';
    clearPaymentSelection();
    selectedMultiKeys.value = isMultiSlotSelected(courtId, startsAt)
        ? selectedMultiKeys.value.filter((item) => item !== key)
        : [...selectedMultiKeys.value, key];
    await queueHoldSync();
}

function queueHoldSync() {
    const keys = [...selectedMultiKeys.value];
    const date = selectedDate.value;
    const version = holdSelectionVersion;
    const slots = keys.map((key) => {
        const [courtId, startsAt] = key.split('|');
        const slot = matrixSlot(Number(courtId), startsAt);
        return slot ? { court_id: Number(courtId), starts_at: slot.starts_at, ends_at: slot.ends_at } : null;
    }).filter(Boolean);
    holdSyncQueue = holdSyncQueue.then(async () => {
        if (version !== holdSelectionVersion) return;
        if (!slots.length) {
            const token = holdToken.value;
            if (token) {
                await fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: token }) });
            }
            if (version === holdSelectionVersion) {
                holdToken.value = '';
                holdExpiresAt.value = null;
            }
            return;
        }
        const token = holdToken.value || null;
        const response = await fetch('/booking/holds', {
            method: 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' },
            body: JSON.stringify({ date, hold_token: token, slots }),
        });
        const result = await response.json();
        if (!response.ok) throw new Error(result.message || 'Kh?ng th? gi? khung gi? ?? ch?n.');
        if (version !== holdSelectionVersion) {
            fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: result.data.token }) });
            return;
        }
        holdToken.value = result.data.token;
        holdExpiresAt.value = result.data.expires_at;
        await loadAvailability();
    }).catch((error) => {
        if (version !== holdSelectionVersion) return;
        pageError.value = error instanceof Error ? error.message : 'Kh?ng th? gi? khung gi? ?? ch?n.';
        selectedMultiKeys.value = [];
        holdToken.value = '';
        holdExpiresAt.value = null;
        loadAvailability();
    });
    return holdSyncQueue;
}

function goToInformation() {
    pageError.value = '';
    fieldErrors.value = {};
    if (!selectedBookingSegments.value.length) {
        pageError.value = bookingMode.value === 'multiple'
            ? 'Chọn ít nhất một ô sân và khung giờ trên bảng lịch.'
            : 'Chọn sân và ít nhất một khung giờ trước khi tiếp tục.';
        document.getElementById(bookingMode.value === 'multiple' ? 'multi-court-matrix' : 'single-court-slots')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return;
    }
    currentStep.value = 2;
    document.getElementById('details-title')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function goToCourtSelection() {
    currentStep.value = 1;
    pageError.value = '';
}

function returnToContactInformation() {
    currentStep.value = 2;
    pageError.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function goToStep(step) {
    if (bookingStatus.value || step < 1 || step >= currentStep.value) return;
    currentStep.value = step;
    pageError.value = '';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateContactInformation() {
    fieldErrors.value = {};
    if (!form.customer_name.trim()) fieldErrors.value.customer_name = ['Vui lòng nhập họ và tên.'];
    if (form.customer_email.trim() && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.customer_email.trim())) fieldErrors.value.customer_email = ['Vui lòng nhập email hợp lệ.'];
    if (!form.customer_phone.trim()) fieldErrors.value.customer_phone = ['Vui lòng nhập số điện thoại.'];
    if (Object.keys(fieldErrors.value).length) {
        pageError.value = 'Vui lòng kiểm tra thông tin liên hệ.';
        const firstInvalidField = !form.customer_name.trim()
            ? 'customer-name'
            : !form.customer_phone.trim()
                ? 'customer-phone'
                : 'customer-email';
        document.getElementById(firstInvalidField)?.focus();
        return false;
    }
    return true;
}

async function createPaymentSession() {
    if (submitting.value) return;
    submitting.value = true;
    pageError.value = '';
    fieldErrors.value = {};
    if (!selectedBookingSegments.value.length) {
        pageError.value = 'Không tìm thấy khung giờ đã chọn. Quay lại bước 1 để chọn sân và giờ.';
        submitting.value = false;
        return;
    }
    if (!validateContactInformation()) {
        submitting.value = false;
        return;
    }
    await holdSyncQueue;
    if (!holdToken.value) {
        pageError.value = 'Thời gian giữ chỗ chưa sẵn sàng. Vui lòng chọn lại khung giờ.';
        submitting.value = false;
        return;
    }
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    try {
        const response = await fetch('/booking/payment-instructions', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                date: selectedDate.value,
                hold_token: holdToken.value,
                slots: selectedBookingSegments.value.map((segment) => ({ court_id: segment.court_id, starts_at: segment.starts_at, ends_at: segment.ends_at })),
                ...form,
            }),
        });
        const result = await response.json();
        if (!response.ok) {
            if (result.errors) fieldErrors.value = result.errors;
            throw new Error(result.message || 'Không thể tải thông tin thanh toán. Vui lòng kiểm tra lại lịch sân.');
        }
        paymentSession.value = result.data;
        currentStep.value = 3;
        startPaymentCountdown(result.data.payment_deadline_at);
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Không thể tải thông tin thanh toán.';
        if (Object.keys(fieldErrors.value).length) loadAvailability();
    } finally {
        submitting.value = false;
    }
}

async function submitPaymentProof() {
    if (submitting.value) return;
    pageError.value = '';
    fieldErrors.value = {};
    if (!paymentSession.value || paymentExpired.value) {
        pageError.value = 'Mã thanh toán đã hết hạn. Vui lòng đặt lại khung giờ.';
        return;
    }
    if (!receiptFile.value) {
        fieldErrors.value.proof_image = ['Vui lòng tải ảnh chuyển khoản thành công.'];
        return;
    }

    submitting.value = true;
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
    const payload = new FormData();
    payload.append('date', selectedDate.value);
    payload.append('hold_token', holdToken.value);
    selectedBookingSegments.value.forEach((segment, index) => {
        payload.append(`slots[${index}][court_id]`, String(segment.court_id));
        payload.append(`slots[${index}][starts_at]`, segment.starts_at);
        payload.append(`slots[${index}][ends_at]`, segment.ends_at);
    });
    payload.append('customer_name', form.customer_name);
    payload.append('customer_email', form.customer_email);
    payload.append('customer_phone', form.customer_phone);
    payload.append('customer_note', form.customer_note);
    payload.append('payment_reference', paymentSession.value.payment_reference);
    payload.append('proof_image', receiptFile.value);
    statusToken.value ||= createStatusToken();
    payload.append('status_token', statusToken.value);

    try {
        const response = await fetch('/booking/payment-proof', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            body: payload,
        });
        const result = await response.json();
        if (!response.ok) {
            if (result.errors) fieldErrors.value = result.errors;
            throw new Error(result.message || 'Không thể gửi ảnh chuyển khoản. Vui lòng thử lại.');
        }
        bookingStatus.value = { ...result.data, payment_submitted: true };
        currentStep.value = 4;
        if (paymentCountdownTimer) window.clearInterval(paymentCountdownTimer);
        paymentCountdownTimer = null;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (error) {
        try {
            const recovered = await fetch(`/booking/status/${encodeURIComponent(statusToken.value)}/data`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
            const recoveredResult = await recovered.json();
            if (recovered.ok) {
                bookingStatus.value = recoveredResult.data;
                currentStep.value = 4;
                if (paymentCountdownTimer) window.clearInterval(paymentCountdownTimer);
                paymentCountdownTimer = null;
                return;
            }
        } catch {
            // Keep the editable form available when no order was created.
        }
        pageError.value = error instanceof Error ? error.message : 'Không thể gửi ảnh chuyển khoản.';
    } finally {
        submitting.value = false;
    }
}

function continueBookingFlow() {
    if (currentStep.value === 1) goToInformation();
    else if (currentStep.value === 2) createPaymentSession();
    else if (currentStep.value === 3) submitPaymentProof();
}

function restartBookingSelection() {
    holdSelectionVersion++;
    if (holdToken.value) fetch('/booking/holds', { method: 'DELETE', credentials: 'same-origin', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' }, body: JSON.stringify({ hold_token: holdToken.value }) });
    holdToken.value = ''; holdExpiresAt.value = null;
    clearPaymentSelection();
    selectedMultiKeys.value = [];
    fieldErrors.value = {};
    pageError.value = '';
    currentStep.value = 1;
    loadAvailability();
}

async function loadBookingStatus(token) {
    availabilityRequest++;
    loading.value = true;
    pageError.value = '';
    try {
        const response = await fetch(`/booking/status/${encodeURIComponent(token)}/data`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
        const result = await response.json();
        if (!response.ok) throw new Error('Liên kết booking không hợp lệ hoặc đã hết hạn.');
        bookingStatus.value = result.data;
    } catch (error) {
        pageError.value = error instanceof Error ? error.message : 'Không thể tải thông tin booking.';
    } finally {
        loading.value = false;
    }
}

watch(() => route.params.token, (token) => {
    if (typeof token === 'string') loadBookingStatus(token);
    else {
        bookingStatus.value = null;
        loadAvailability();
    }
}, { immediate: true });

</script>

<template>
    <main class="booking-page">
        <section class="booking-hero">
            <div class="booking-hero-image" aria-hidden="true"></div>
            <div class="booking-hero-content">
                <p class="booking-eyebrow">BALLBURN · PICKLECLUB & BISTRO</p>
                <h1>Đặt sân</h1>
                <p>Chọn sân, chọn giờ chơi và sẵn sàng ra sân.</p>
            </div>
        </section>

        <div class="booking-content" :class="{ 'is-multi-court': bookingMode === 'multiple' && currentStep === 1 && !bookingStatus }">
            <aside class="booking-steps-column"><BookingStepper :current="activeStep" :complete="Boolean(bookingStatus)" @navigate="goToStep" /></aside>

            <section v-if="bookingStatus" class="booking-result" aria-live="polite">
                <div class="booking-result-icon" aria-hidden="true">✓</div>
                <p class="booking-eyebrow">{{ bookingStatus.payment_submitted ? 'ĐÃ GỬI MINH CHỨNG' : 'TRẠNG THÁI BOOKING' }}</p>
                <template v-if="bookingStatus.bookings?.length">
                    <h2>{{ bookingStatus.payment_submitted ? 'Đã nhận ảnh chuyển khoản' : 'Thông tin đơn đặt sân' }}</h2>
                    <p class="booking-order-code">Mã đơn: <strong>{{ bookingStatus.booking_code }}</strong></p>
                    <p class="booking-order-total">Tổng tiền đơn: <strong>{{ formatMoney(bookingStatus.total_amount, bookingStatus.currency) }}</strong></p>
                    <p>{{ bookingStatus.payment_submitted ? 'Booking đang chờ nhân viên đối soát. Ảnh chuyển khoản chưa đồng nghĩa với xác nhận thanh toán.' : 'Theo dõi trạng thái booking của bạn bên dưới.' }}</p>
                    <div class="booking-result-list">
                        <article v-for="(booking, index) in bookingStatus.bookings" :key="`${booking.court}-${booking.date}-${booking.starts_at}-${index}`" class="booking-result-item">
                            <dl class="booking-result-details">
                                <div><dt>Sân</dt><dd>{{ booking.court }}</dd></div>
                                <div><dt>Ngày</dt><dd>{{ formatDate(booking.date) }}</dd></div>
                                <div><dt>Giờ chơi</dt><dd>{{ booking.starts_at }} – {{ booking.ends_at }}</dd></div>
                                <div><dt>Tổng tiền</dt><dd>{{ formatMoney(booking.total_amount, booking.currency) }}</dd></div>
                                <div><dt>Trạng thái</dt><dd :class="booking.status === 'PAID' ? 'booking-status-confirmed' : 'booking-status-pending'">{{ booking.status === 'PAID' ? 'Đã thanh toán' : 'Chờ thanh toán' }}</dd></div>
                            </dl>
                        </article>
                    </div>
                    <a v-if="bookingStatus.status_url" :href="bookingStatus.status_url" class="booking-status-link">Liên kết xem trạng thái đơn ↗</a>
                </template>
                <template v-else>
                    <h2>{{ bookingStatus.payment_submitted ? 'Đã nhận ảnh chuyển khoản' : 'Thông tin đơn đặt sân' }}</h2>
                    <p class="booking-order-code">Mã đơn: <strong>{{ bookingStatus.booking_code }}</strong></p>
                    <p>Trạng thái hiện tại: {{ bookingStatus.status === 'PAID' ? 'Đã thanh toán' : 'Chờ thanh toán' }}</p>
                    <dl class="booking-result-details">
                        <div><dt>Mã booking</dt><dd>{{ bookingStatus.booking_code }}</dd></div>
                        <div><dt>Sân</dt><dd>{{ bookingStatus.court }}</dd></div>
                        <div><dt>Ngày</dt><dd>{{ formatDate(bookingStatus.date) }}</dd></div>
                        <div><dt>Giờ chơi</dt><dd>{{ bookingStatus.starts_at }} – {{ bookingStatus.ends_at }}</dd></div>
                        <div><dt>Tổng tiền</dt><dd>{{ formatMoney(bookingStatus.total_amount, bookingStatus.currency) }}</dd></div>
                        <div><dt>Trạng thái</dt><dd :class="bookingStatus.status === 'PAID' ? 'booking-status-confirmed' : 'booking-status-pending'">{{ bookingStatus.status === 'PAID' ? 'Đã thanh toán' : 'Chờ thanh toán' }}</dd></div>
                    </dl>
                </template>
                <a class="button button--outline" href="/">Về trang chủ</a>
            </section>

            <section v-else-if="typeof route.params.token !== 'string'" class="booking-main-column">
                <div v-if="pageError" class="booking-alert" role="alert">{{ pageError }}</div>
                <div v-if="loading && !data" class="booking-loading">Đang tải danh sách sân…</div>
                <template v-else>
                    <template v-if="currentStep === 1">
                    <div class="booking-mode-switch" role="group" aria-label="Chọn cách đặt sân">
                        <button type="button" :class="{ 'is-selected': bookingMode === 'single' }" :aria-pressed="bookingMode === 'single'" @click="setBookingMode('single')">Đặt một sân</button>
                        <button type="button" :class="{ 'is-selected': bookingMode === 'multiple' }" :aria-pressed="bookingMode === 'multiple'" @click="setBookingMode('multiple')">Chọn nhiều sân</button>
                    </div>

                    <section v-if="bookingMode === 'single'" class="booking-panel" aria-labelledby="court-select-title">
                        <header class="booking-section-heading"><div><p class="booking-section-index">01 / CHỌN SÂN</p><h2 id="court-select-title">Chọn sân của bạn</h2></div><span>{{ data?.courts?.length ?? 0 }} sân</span></header>
                        <div v-if="data?.courts?.length" class="booking-court-grid">
                            <button v-for="court in data.courts" :key="court.id" type="button" class="booking-court-card" :class="{ 'is-selected': selectedCourtId === court.id }" :aria-pressed="selectedCourtId === court.id" @click="chooseCourt(court.id)">
                                <img v-if="court.image_url" :src="court.image_url" :alt="court.name" loading="lazy" />
                                <div v-else class="booking-court-placeholder" aria-hidden="true">BALLBURN</div>
                                <div class="booking-court-copy"><strong>{{ court.name }}</strong><span>{{ court.surface_type || 'Sân pickleball' }}</span><small>Từ {{ formatMoney(court.price_per_hour, court.currency) }} / giờ</small></div>
                                <span class="booking-court-check" aria-hidden="true">{{ selectedCourtId === court.id ? '✓' : '›' }}</span>
                            </button>
                        </div>
                        <p v-else class="booking-empty-slots">Hiện chưa có sân nhận đặt lịch.</p>
                    </section>

                    <section class="booking-panel" aria-labelledby="date-time-title">
                        <header class="booking-section-heading"><div><p class="booking-section-index">01 / NGÀY & GIỜ</p><h2 id="date-time-title">Chọn ngày và khung giờ</h2></div><span v-if="data">Múi giờ {{ data.timezone }}</span></header>
                        <div class="booking-date-time-layout">
                            <div class="booking-times-panel">
                                <div class="booking-times-heading">
                                    <div class="booking-calendar-control">
                                        <button type="button" class="booking-date-toggle" :aria-expanded="showCalendar" aria-controls="booking-calendar-popup" @click="showCalendar = !showCalendar">
                                            <span><strong>{{ formatDate(selectedDate) }}</strong><small>Nhấn để chọn ngày khác</small></span>
                                            <span class="booking-date-toggle-icon" aria-hidden="true">{{ showCalendar ? '−' : '+' }}</span>
                                        </button>
                                        <div v-if="showCalendar && data" id="booking-calendar-popup" class="booking-calendar-popup">
                                            <BookingCalendar :date="selectedDate" :today="data.today" :max-date="data.max_date" @select="chooseDate" />
                                        </div>
                                    </div>
                                    <p class="booking-selection-hint">{{ bookingMode === 'single' ? 'Chọn một hoặc nhiều khung giờ 30 phút, không cần liền nhau.' : 'Chọn nhiều sân và khung giờ, không cần liền nhau.' }}</p>
                                    <span v-if="loading" class="booking-loading-inline">Đang cập nhật…</span>
                                </div>
                                <template v-if="bookingMode === 'single'">
                                    <div id="single-court-slots">
                                        <TimeSlotGrid :slots="slots" :selected-times="selectedCourtTimes" :currency="selectedCourt?.currency" :loading="loading" @select="toggleMultiSlot(selectedCourtId, $event)" />
                                    </div>
                                    <p class="booking-slot-legend"><span><i class="is-available"></i>Còn trống</span><span><i class="is-booked"></i>Đã đặt</span><span><i class="is-closed"></i>Đã khóa</span><span><i class="is-selected"></i>Đang chọn</span></p>
                                </template>
                                <div v-else id="multi-court-matrix" class="booking-matrix-scroll" :aria-busy="loading" aria-label="Lịch nhiều sân">
                                    <div v-if="data?.courts?.length && matrixTimeSlots.length" class="booking-matrix" :style="{ '--matrix-court-count': data.courts.length }">
                                        <div class="booking-matrix-head booking-matrix-time-head">Giờ</div>
                                        <div v-for="court in data.courts" :key="`multi-head-${court.id}`" class="booking-matrix-head booking-matrix-court-head">{{ court.name }}</div>
                                        <template v-for="timeSlot in matrixTimeSlots" :key="timeSlot.starts_at">
                                            <div class="booking-matrix-time">{{ timeSlot.starts_at }}–{{ timeSlot.ends_at }}</div>
                                            <button
                                                v-for="court in data.courts"
                                                :key="`${court.id}-${timeSlot.starts_at}`"
                                                type="button"
                                                class="booking-matrix-cell"
                                                :class="[`is-${matrixSlot(court.id, timeSlot.starts_at)?.status ?? 'closed'}`, { 'is-selected': isMultiSlotSelected(court.id, timeSlot.starts_at) }]"
                                                :disabled="loading || matrixSlot(court.id, timeSlot.starts_at)?.status !== 'available'"
                                                :aria-pressed="isMultiSlotSelected(court.id, timeSlot.starts_at)"
                                                :aria-label="`${court.name}, ${timeSlot.starts_at} đến ${timeSlot.ends_at}${isMultiSlotSelected(court.id, timeSlot.starts_at) ? ', đã chọn' : ''}`"
                                                @click="toggleMultiSlot(court.id, timeSlot.starts_at)"
                                            >
                                                <span v-if="isMultiSlotSelected(court.id, timeSlot.starts_at)" aria-hidden="true">✓</span>
                                                <small v-else-if="matrixSlot(court.id, timeSlot.starts_at)?.status === 'available'">{{ formatMoney(matrixSlot(court.id, timeSlot.starts_at).amount, court.currency) }}</small>
                                                <small v-else-if="matrixSlot(court.id, timeSlot.starts_at)?.status === 'booked'">Đã đặt</small>
                                                <small v-else-if="matrixSlot(court.id, timeSlot.starts_at)?.status === 'past'">Đã qua</small>
                                                <small v-else>—</small>
                                            </button>
                                        </template>
                                    </div>
                                    <p v-else class="booking-empty-slots">Không có sân hoạt động trong ngày đã chọn.</p>
                                </div>
                                <p v-if="bookingMode === 'multiple'" class="booking-slot-legend"><span><i class="is-available"></i>Còn trống</span><span><i class="is-booked"></i>Đã đặt</span><span><i class="is-closed"></i>Ngoài giờ / đã khóa</span><span><i class="is-selected"></i>Đang chọn</span></p>
                            </div>
                        </div>
                    </section>

                    </template>

                    <section v-else-if="currentStep === 2" class="booking-panel" aria-labelledby="details-title">
                        <header class="booking-section-heading"><div><p class="booking-section-index">02 / THÔNG TIN</p><h2 id="details-title">Thông tin liên hệ</h2></div><span>Không cần tạo tài khoản</span></header>
                        <button type="button" class="booking-back-button" @click="goToCourtSelection">← Quay lại chọn sân và giờ</button>
                        <div class="booking-form-grid">
                            <label class="booking-field">Họ và tên *<input id="customer-name" v-model="form.customer_name" autocomplete="name" maxlength="150" placeholder="Tên của bạn" /><small v-if="fieldErrors.customer_name">{{ fieldErrors.customer_name[0] }}</small></label>
                            <label class="booking-field">Email (không bắt buộc)<input id="customer-email" v-model="form.customer_email" type="email" autocomplete="email" maxlength="255" placeholder="ban@example.com" /><small v-if="fieldErrors.customer_email">{{ fieldErrors.customer_email[0] }}</small></label>
                            <label class="booking-field">Số điện thoại *<input id="customer-phone" v-model="form.customer_phone" type="tel" autocomplete="tel" maxlength="30" required placeholder="Số điện thoại của bạn" /><small v-if="fieldErrors.customer_phone">{{ fieldErrors.customer_phone[0] }}</small></label>
                            <label class="booking-field booking-field-wide">Ghi chú<textarea v-model="form.customer_note" rows="3" maxlength="1000" placeholder="Điều BALLBURN cần biết trước giờ chơi (không bắt buộc)" /><small v-if="fieldErrors.customer_note">{{ fieldErrors.customer_note[0] }}</small></label>
                        </div>
                    </section>

                    <section v-else-if="currentStep === 3 && paymentSession" class="booking-panel booking-payment-panel" aria-labelledby="payment-title">
                        <header class="booking-section-heading"><div><p class="booking-section-index">03 / THANH TOÁN</p><h2 id="payment-title">Quét mã và gửi ảnh chuyển khoản</h2></div><span>Giữ chỗ trong 5 phút</span></header>
                        <button type="button" class="booking-back-button" @click="returnToContactInformation">← Quay lại thông tin</button>
                        <div class="booking-payment-layout">
                            <div class="booking-qr-card">
                                <img v-if="paymentQrUrl" :src="paymentQrUrl" alt="Mã QR nhận thanh toán BALLBURN" />
                                <img v-else-if="qrCodeDataUrl" :src="qrCodeDataUrl" alt="Mã QR tham chiếu thanh toán" />
                                <div v-else class="booking-qr-loading">Đang tạo mã QR…</div>
                                <span>Mã tham chiếu</span><strong>{{ paymentSession.payment_reference }}</strong>
                                <p>Tổng tiền: <b>{{ formatMoney(paymentSession.total_amount, paymentSession.currency) }}</b></p>
                                <p v-if="paymentQrUrl">Vui lòng nhập mã tham chiếu ở trên làm nội dung chuyển khoản.</p>
                            </div>
                            <div class="booking-proof-form">
                                <p class="booking-payment-countdown" :class="{ 'is-expired': paymentExpired }">{{ paymentExpired ? 'Mã thanh toán đã hết hạn' : `Thời gian còn lại ${paymentCountdownLabel}` }}</p>
                                <p class="booking-payment-note">Chưa có đơn đặt sân nào được tạo. Sau khi tải ảnh chuyển khoản lên và gửi, hệ thống sẽ tạo đơn ở trạng thái chờ thanh toán và báo cho admin. Nhân viên sẽ kiểm tra ảnh trước khi xác nhận đã thanh toán.</p>
                                <label class="booking-field booking-upload-field">Ảnh chuyển khoản thành công *
                                    <input type="file" accept="image/jpeg,image/png,image/webp" :disabled="paymentExpired" @change="chooseReceipt" />
                                    <small v-if="fieldErrors.proof_image">{{ fieldErrors.proof_image[0] }}</small>
                                </label>
                                <img v-if="receiptPreviewUrl" class="booking-receipt-preview" :src="receiptPreviewUrl" alt="Xem trước ảnh chuyển khoản" />
                                <button v-if="paymentExpired" type="button" class="booking-back-button" @click="restartBookingSelection">Chọn lại sân và khung giờ</button>
                            </div>
                        </div>
                    </section>
                </template>
            </section>
            <section v-else-if="loading || pageError" class="booking-result" aria-live="polite">
                <div v-if="loading" class="booking-loading">Đang tải thông tin booking…</div>
                <template v-else><p class="booking-eyebrow">KHÔNG TÌM THẤY BOOKING</p><h2>Liên kết không hợp lệ</h2><p>{{ pageError }}</p><a class="button button--outline" href="/booking">Đặt sân mới</a></template>
            </section>

            <BookingSummary
                v-if="!bookingStatus && typeof route.params.token !== 'string'"
                :court="bookingMode === 'single' ? selectedCourt : null"
                :date="selectedDate"
                :total="selectedBookingTotal"
                :items="summaryItems"
                :submitting="submitting"
                :button-label="summaryActionLabel"
                :button-disabled="summaryActionDisabled"
                :note="summaryNote"
                :complete="Boolean(bookingStatus)"
                :complete-label="bookingStatus?.payment_submitted ? 'Đã gửi ảnh chuyển khoản' : 'Đã xác nhận'"
                @submit="continueBookingFlow"
            />
        </div>
    </main>
</template>

<style>
.booking-page{margin-inline:calc(-1 * clamp(20px,5vw,76px));color:var(--color-ink);background:var(--color-surface)}
.booking-hero{position:relative;isolation:isolate;min-height:190px;display:flex;align-items:center;overflow:hidden;background:var(--color-green);color:#fff}
.booking-hero-image{position:absolute;inset:0;z-index:-1;background-image:linear-gradient(90deg,rgba(5,35,26,.94),rgba(5,35,26,.65) 42%,rgba(5,35,26,.1)),url('/images/pickleball/court-rally-wide.jpg');background-size:cover;background-position:center 44%}
.booking-hero-content{width:min(1288px,100%);margin:auto;padding:34px clamp(20px,5vw,76px)}
.booking-eyebrow,.booking-section-index,.booking-summary-kicker{margin:0 0 10px;color:var(--color-brand);font-size:12px;font-weight:800;letter-spacing:.15em;text-transform:uppercase}
.booking-hero h1{margin:0;font-family:var(--font-heading);font-size:clamp(2.5rem,5vw,4rem);line-height:1.1}
.booking-hero-content>p:last-child{margin:12px 0 0;color:rgba(255,255,255,.9);font-size:16px}
.booking-content{width:min(1288px,100%);margin:auto;padding:42px clamp(20px,5vw,76px) 80px;display:grid;grid-template-columns:150px minmax(0,1fr) 300px;align-items:start;gap:28px}
.booking-steps-column{position:sticky;top:24px}
.booking-stepper{position:relative;display:grid;gap:28px;margin:12px 0;padding:0;list-style:none}
.booking-stepper::before{position:absolute;top:20px;bottom:20px;left:18px;width:1px;background:var(--color-border);content:''}
.booking-stepper li{position:relative;display:flex;align-items:center;gap:12px;color:var(--color-ink-muted);font-size:14px;font-weight:600}
.booking-step-number{z-index:1;display:grid;width:38px;height:38px;place-items:center;border:1px solid var(--color-border);border-radius:50%;background:var(--color-surface);color:var(--color-ink-muted);font-size:14px}
.booking-stepper li.is-current{color:var(--color-brand)}.booking-stepper li.is-current .booking-step-number{border-color:var(--color-brand);background:var(--color-brand);color:white}.booking-stepper li.is-complete .booking-step-number{border-color:var(--color-green);color:var(--color-green)}
.booking-main-column{display:grid;gap:22px;min-width:0}
.booking-panel{padding:22px;border:1px solid var(--color-border);border-radius:16px;background:#fffefa;box-shadow:0 6px 22px rgba(23,33,29,.035)}
.booking-section-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:18px}.booking-section-heading h2,.booking-summary h2{margin:0;color:var(--color-ink);font-size:20px;font-weight:800;line-height:1.3}.booking-section-index{margin-bottom:6px}.booking-section-heading>span{color:var(--color-ink-muted);font-size:12px}
.booking-court-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:12px}
.booking-court-card{position:relative;overflow:hidden;padding:7px;border:1px solid var(--color-border);border-radius:13px;background:var(--color-surface);text-align:left;cursor:pointer;transition:border-color .18s,box-shadow .18s,transform .18s}.booking-court-card:hover{transform:translateY(-2px);border-color:var(--color-brand)}.booking-court-card.is-selected{border:2px solid var(--color-brand);padding:6px;box-shadow:0 0 0 3px rgba(243,107,50,.1)}
.booking-court-card>img,.booking-court-placeholder{width:100%;height:86px;display:block;object-fit:cover;border-radius:8px}.booking-court-placeholder{display:grid;place-items:center;background:var(--color-green);color:white;font-weight:900;letter-spacing:.12em}
.booking-court-copy{display:grid;gap:3px;padding:9px 2px 3px}.booking-court-copy strong{color:var(--color-ink);font-size:14px}.booking-court-copy span{overflow:hidden;color:var(--color-ink-muted);font-size:12px;text-overflow:ellipsis;white-space:nowrap}.booking-court-copy small{color:var(--color-green);font-size:12px;font-weight:700}.booking-court-check{position:absolute;right:8px;top:8px;display:grid;width:25px;height:25px;place-items:center;border-radius:50%;background:rgba(255,255,255,.94);color:var(--color-green);font-size:18px;font-weight:800}
.booking-date-time-layout{display:grid;grid-template-columns:minmax(210px,.8fr) minmax(0,1.2fr);gap:22px}.booking-calendar{padding:14px;border:1px solid var(--color-border);border-radius:13px;background:#fff}.booking-calendar-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px}.booking-calendar-header h3{margin:0;text-transform:capitalize;font-size:15px;font-weight:800}.booking-calendar-header button{width:32px;height:32px;border:0;border-radius:50%;background:transparent;color:var(--color-green);font-size:24px;cursor:pointer}.booking-calendar-header button:disabled{opacity:.35;cursor:default}.booking-calendar-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:4px}.booking-calendar-grid span,.booking-calendar-grid button{display:grid;aspect-ratio:1;place-items:center;color:var(--color-ink);font-size:12px}.booking-calendar-weekdays span{color:var(--color-ink-muted);font-size:11px;font-weight:800}.booking-calendar-grid button{border:0;border-radius:50%;background:transparent;cursor:pointer}.booking-calendar-grid button:hover:not(:disabled){background:var(--color-surface-muted)}.booking-calendar-grid button:disabled{color:#b8b9b5;cursor:not-allowed}.booking-calendar-grid button.is-outside{color:#a9aca7}.booking-calendar-grid button.is-today:not(.is-selected){box-shadow:inset 0 0 0 1px var(--color-green)}.booking-calendar-grid button.is-selected{background:var(--color-green);color:#fff;font-weight:800}.booking-calendar-note{margin:10px 0 0;color:var(--color-ink-muted);font-size:12px}
.booking-times-panel{min-width:0}.booking-times-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:10px;margin:1px 0 14px}.booking-times-heading h3{margin:0;text-transform:capitalize;font-size:15px;font-weight:800}.booking-times-heading p{margin:4px 0 0;color:var(--color-ink-muted);font-size:12px}.booking-loading-inline{color:var(--color-ink-muted);font-size:12px}.booking-slot-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:8px;max-height:300px;overflow:auto;padding:2px}.booking-slot{display:grid;gap:4px;min-height:54px;padding:8px 10px;border:1px solid var(--color-border);border-radius:9px;background:#fff;color:var(--color-ink);text-align:left;cursor:pointer;transition:border-color .15s,background .15s}.booking-slot span{font-size:12px;font-weight:700}.booking-slot small{color:var(--color-ink-muted);font-size:11px}.booking-slot:hover:not(:disabled){border-color:var(--color-green);background:#f6faf7}.booking-slot.is-selected{border-color:var(--color-green);background:#dcefe3;box-shadow:inset 0 0 0 1px var(--color-green)}.booking-slot:disabled{cursor:not-allowed}.booking-slot.is-booked{background:#f3f1ed;color:#777}.booking-slot.is-closed{background:#e8e6e1;color:#777}.booking-slot.is-past{background:#f6f6f4;color:#9b9d98}.booking-slot-legend{display:flex;flex-wrap:wrap;gap:12px;margin:13px 0 0;color:var(--color-ink-muted);font-size:11px}.booking-slot-legend span{display:flex;align-items:center;gap:6px}.booking-slot-legend i{width:10px;height:10px;border:1px solid var(--color-border);border-radius:3px;background:#fff}.booking-slot-legend i.is-available{background:#fff}.booking-slot-legend i.is-booked{background:#f3f1ed}.booking-slot-legend i.is-closed{background:#e8e6e1}.booking-empty-slots{margin:0;padding:22px;border-radius:10px;background:var(--color-surface-muted);color:var(--color-ink-muted);font-size:14px;text-align:center}
.booking-duration-row{display:grid;gap:6px;margin-top:14px}.booking-duration-row label{font-size:12px;font-weight:700}.booking-duration-row select,.booking-field input,.booking-field textarea{width:100%;min-height:44px;padding:10px 12px;border:1px solid var(--color-border);border-radius:9px;background:#fff;color:var(--color-ink);font:inherit;font-size:14px;outline:none}.booking-duration-row select:focus,.booking-field input:focus,.booking-field textarea:focus{border-color:var(--color-green);box-shadow:0 0 0 3px rgba(11,56,44,.1)}
.booking-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:15px}.booking-field{display:grid;gap:6px;color:var(--color-ink);font-size:13px;font-weight:700}.booking-field-wide{grid-column:1/-1}.booking-field textarea{min-height:90px;resize:vertical}.booking-field input::placeholder,.booking-field textarea::placeholder{color:#9a9d97;font-size:13px;font-weight:400}.booking-field small{color:#b42318;font-size:12px;font-weight:500}
.booking-summary{position:sticky;top:24px;padding:21px;border:1px solid var(--color-border);border-radius:16px;background:#fffefa;box-shadow:0 8px 24px rgba(23,33,29,.06)}.booking-summary-kicker{margin-bottom:6px}.booking-summary h2{margin-bottom:16px}.booking-summary-court{display:flex;align-items:center;gap:11px;padding-bottom:15px;border-bottom:1px solid var(--color-border)}.booking-summary-court img{width:58px;height:48px;border-radius:8px;object-fit:cover}.booking-summary-court div{display:grid;gap:3px}.booking-summary-court strong{font-size:14px}.booking-summary-court span{color:var(--color-ink-muted);font-size:12px}.booking-summary-details{display:grid;gap:12px;margin:15px 0}.booking-summary-details div,.booking-summary-total{display:flex;align-items:flex-start;justify-content:space-between;gap:12px}.booking-summary-details dt,.booking-summary-total span{color:var(--color-ink-muted);font-size:12px}.booking-summary-details dd{margin:0;text-align:right;font-size:12px;font-weight:700}.booking-summary-total{align-items:center;padding:13px 0;border-top:1px solid var(--color-border)}.booking-summary-total strong{font-size:18px}.booking-summary-note{margin:0 0 14px;color:var(--color-ink-muted);font-size:12px;line-height:1.6}.booking-submit{width:100%;border:0;border-radius:10px;background:var(--color-brand);color:#fff}.booking-submit:disabled{opacity:.45;cursor:not-allowed;transform:none}.booking-confirmed-note{display:flex;align-items:center;gap:9px;padding:12px;border-radius:10px;background:#e6f2e9;color:var(--color-green);font-size:13px}.booking-confirmed-note>span{display:grid;width:24px;height:24px;place-items:center;border-radius:50%;background:var(--color-green);color:#fff;font-weight:800}
.booking-alert{padding:13px 15px;border:1px solid #e9b5b0;border-radius:10px;background:#fff4f2;color:#9e2a20;font-size:14px}.booking-loading{padding:60px 20px;text-align:center;color:var(--color-ink-muted)}
.booking-result{grid-column:2/4;max-width:660px;margin:0 auto;padding:42px;border:1px solid var(--color-border);border-radius:18px;background:#fffefa;text-align:center;box-shadow:0 10px 32px rgba(23,33,29,.06)}.booking-result-icon{display:grid;width:60px;height:60px;margin:0 auto 20px;place-items:center;border-radius:50%;background:var(--color-green);color:white;font-size:28px}.booking-result h2{margin:0;color:var(--color-ink);font-size:clamp(1.5rem,3vw,2.2rem)}.booking-result>p:not(.booking-eyebrow){color:var(--color-ink-muted);font-size:14px}.booking-result-details{display:grid;grid-template-columns:1fr 1fr;gap:13px;margin:25px 0;padding:20px;border-radius:12px;background:var(--color-surface-muted);text-align:left}.booking-result-details div{display:grid;gap:4px}.booking-result-details dt{color:var(--color-ink-muted);font-size:12px}.booking-result-details dd{margin:0;font-size:14px;font-weight:700}.booking-status-confirmed{color:var(--color-green)}
.booking-mode-switch{display:flex;gap:8px;padding:5px;border:1px solid var(--color-border);border-radius:12px;background:#fffefa}.booking-mode-switch button{min-height:42px;flex:1;padding:8px 12px;border:1px solid transparent;border-radius:9px;background:transparent;color:var(--color-ink-muted);font:inherit;font-size:14px;font-weight:700;cursor:pointer}.booking-mode-switch button:hover{background:var(--color-surface-muted);color:var(--color-ink)}.booking-mode-switch button.is-selected{border-color:var(--color-green);background:var(--color-green);color:white}
.booking-matrix-scroll{max-height:min(65vh,620px);overflow:auto;border:1px solid var(--color-border);border-radius:10px;background:#fff}.booking-matrix{display:grid;grid-template-columns:90px repeat(var(--matrix-court-count),minmax(112px,1fr));min-width:calc(90px + var(--matrix-court-count)*112px)}.booking-matrix-head{position:sticky;top:0;z-index:2;display:grid;min-height:48px;place-items:center;border-right:1px solid var(--color-border);border-bottom:1px solid var(--color-border);background:#f8f7f3;padding:8px;font-size:12px;font-weight:800}.booking-matrix-time-head{left:0;z-index:4}.booking-matrix-court-head{color:var(--color-green)}.booking-matrix-time{position:sticky;left:0;z-index:1;display:grid;min-height:44px;place-items:center;border-right:1px solid var(--color-border);border-bottom:1px solid var(--color-border);background:#fffefa;padding:5px;font-size:11px;font-weight:700;white-space:nowrap}.booking-matrix-cell{display:grid;min-height:44px;place-items:center;padding:4px;border:0;border-right:1px solid var(--color-border);border-bottom:1px solid var(--color-border);background:#fff;color:var(--color-green);cursor:pointer;transition:background .14s,color .14s}.booking-matrix-cell small{font-size:10px;font-weight:700;line-height:1.2}.booking-matrix-cell.is-available:hover{background:#e6f2e9}.booking-matrix-cell.is-selected{background:#b9ddc6;color:#073b2b;box-shadow:inset 0 0 0 2px var(--color-green);font-size:15px;font-weight:900}.booking-matrix-cell.is-booked{background:#eeece7;color:#777;cursor:not-allowed}.booking-matrix-cell.is-closed{background:#deddd8;color:#85857f;cursor:not-allowed}.booking-matrix-cell.is-past{background:#f4f4f1;color:#a5a59f;cursor:not-allowed}.booking-slot-legend i.is-selected{border-color:var(--color-green);background:#b9ddc6}.booking-summary-items{display:grid;gap:9px;max-height:250px;overflow:auto;padding:12px 0;border-bottom:1px solid var(--color-border)}.booking-summary-items>div{display:grid;gap:3px}.booking-summary-items strong{font-size:12px}.booking-summary-items span{color:var(--color-ink-muted);font-size:11px}.booking-result{max-width:820px}.booking-result-list{display:grid;gap:14px;margin:24px 0}.booking-result-item{padding:14px;border:1px solid var(--color-border);border-radius:12px;background:#fff;text-align:left}.booking-result-item .booking-result-details{margin:0;padding:14px}.booking-status-link{display:inline-block;margin-top:10px;color:var(--color-green);font-size:13px;font-weight:700;text-decoration:underline;text-underline-offset:3px}
@media(max-width:1100px){.booking-content{grid-template-columns:120px minmax(0,1fr) 270px;gap:18px}.booking-court-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.booking-date-time-layout{grid-template-columns:1fr}.booking-calendar{max-width:340px;width:100%;margin-inline:auto}}
@media(max-width:800px){.booking-content{grid-template-columns:1fr;padding-top:25px}.booking-steps-column{position:static;overflow:auto}.booking-stepper{display:flex;gap:18px;min-width:max-content;margin:0 0 4px}.booking-stepper::before{display:none}.booking-stepper li{gap:7px;font-size:12px}.booking-step-number{width:30px;height:30px}.booking-summary{position:static;grid-row:2}.booking-main-column{grid-row:3}.booking-result{grid-column:1;grid-row:2}.booking-panel{padding:17px}}
@media(max-width:520px){.booking-page{margin-inline:-20px}.booking-hero{min-height:165px}.booking-hero-content{padding-block:28px}.booking-hero h1{font-size:2.7rem}.booking-content{padding-inline:16px}.booking-court-grid{gap:8px}.booking-court-card{padding:5px}.booking-court-card.is-selected{padding:4px}.booking-court-card>img,.booking-court-placeholder{height:72px}.booking-court-copy{padding-top:7px}.booking-court-copy strong{font-size:13px}.booking-court-copy small{font-size:11px}.booking-form-grid{grid-template-columns:1fr}.booking-field-wide{grid-column:auto}.booking-result{padding:25px 18px}.booking-result-details{grid-template-columns:1fr 1fr;padding:14px}.booking-section-heading{align-items:flex-start}.booking-section-heading h2{font-size:18px}}
.booking-content{grid-template-columns:minmax(0,1fr) 300px;gap:22px 28px}
.booking-steps-column{position:static;grid-column:1/-1;min-width:0}
.booking-stepper{display:flex;align-items:center;justify-content:space-between;gap:14px;margin:0;padding:12px 18px;border:1px solid var(--color-border);border-radius:14px;background:#fffefa}
.booking-stepper::before{top:50%;right:38px;bottom:auto;left:38px;width:auto;height:1px}
.booking-stepper li{flex:1;gap:9px}
.booking-step-number{flex:0 0 36px;width:36px;height:36px}
.booking-main-column{min-width:0}
.booking-result{grid-column:1/-1}
.booking-court-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
.booking-date-time-layout{display:block}
.booking-times-panel{min-width:0}
.booking-times-heading{display:grid;grid-template-columns:minmax(0,1fr) auto;align-items:center}
.booking-calendar-control{position:relative;min-width:0}
.booking-date-toggle{display:flex;align-items:center;justify-content:space-between;gap:15px;width:min(100%,420px);min-height:58px;padding:10px 14px;border:1px solid var(--color-border);border-radius:11px;background:#fff;color:var(--color-ink);text-align:left;cursor:pointer}
.booking-date-toggle:hover,.booking-date-toggle[aria-expanded="true"]{border-color:var(--color-brand)}
.booking-date-toggle>span:first-child{display:grid;gap:3px}
.booking-date-toggle strong{font-size:14px}
.booking-date-toggle small{color:var(--color-ink-muted);font-size:12px}
.booking-date-toggle-icon{display:grid;width:28px;height:28px;place-items:center;border-radius:50%;background:var(--color-surface-muted);font-size:20px}
.booking-calendar-popup{position:absolute;top:calc(100% + 8px);left:0;z-index:20;width:min(340px,calc(100vw - 48px));padding:8px;border:1px solid var(--color-border);border-radius:14px;background:var(--color-surface);box-shadow:0 14px 40px rgba(23,33,29,.16)}
.booking-calendar-popup .booking-calendar{width:100%;max-width:none;margin:0;border:0;padding:10px}
.booking-selection-hint{margin:0;color:var(--color-ink-muted);font-size:13px}
.booking-slot-grid{grid-template-columns:repeat(3,minmax(0,1fr));max-height:430px}
.booking-payment-layout{display:grid;grid-template-columns:minmax(220px,.8fr) minmax(0,1.2fr);gap:24px;align-items:start}
.booking-qr-card{display:flex;flex-direction:column;align-items:center;gap:10px;padding:20px;border:1px solid var(--color-border);border-radius:16px;background:var(--color-surface-muted);text-align:center}
.booking-qr-card img,.booking-qr-loading{width:260px;max-width:100%;aspect-ratio:1;object-fit:contain;border-radius:10px;background:#fff}
.booking-qr-loading{display:grid;place-items:center;color:var(--color-ink-muted)}
.booking-qr-card>span{color:var(--color-ink-muted);font-size:13px}
.booking-qr-card>strong{font-size:18px;letter-spacing:.06em}
.booking-qr-card>p{margin:0;color:var(--color-ink-muted)}
.booking-proof-form{display:grid;gap:16px}
.booking-payment-countdown{margin:0;color:var(--color-brand);font-size:22px;font-weight:800}
.booking-payment-countdown.is-expired{color:var(--color-danger)}
.booking-payment-note{margin:0;padding:14px;border-radius:10px;background:var(--color-surface-muted);color:var(--color-ink-muted);line-height:1.6}
.booking-upload-field input{display:block;width:100%;margin-top:8px;padding:12px;border:1px dashed var(--color-border);border-radius:10px;background:var(--color-surface)}
.booking-receipt-preview{width:min(100%,420px);max-height:280px;object-fit:contain;border:1px solid var(--color-border);border-radius:12px;background:var(--color-surface-muted)}
.booking-back-button{margin:0 0 16px;padding:8px 0;border:0;background:transparent;color:var(--color-ink-muted);font:inherit;text-decoration:underline;text-underline-offset:3px;cursor:pointer}
.booking-status-pending{color:var(--color-warning);font-weight:700}
@media(max-width:700px){.booking-payment-layout{grid-template-columns:1fr}}
@media(max-width:1100px){.booking-content{grid-template-columns:minmax(0,1fr) 270px;gap:18px}.booking-court-grid{grid-template-columns:repeat(3,minmax(0,1fr))}.booking-date-time-layout{display:block}.booking-calendar{max-width:none;margin:0}}
@media(max-width:800px){.booking-content{grid-template-columns:1fr;padding-top:25px}.booking-stepper{gap:10px;padding:10px}.booking-stepper::before{display:block;right:28px;left:28px}.booking-stepper li{gap:6px;font-size:11px}.booking-step-number{flex-basis:30px;width:30px;height:30px}.booking-summary{position:static;grid-row:auto}.booking-main-column{grid-row:auto}.booking-result{grid-column:1;grid-row:auto}.booking-court-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.booking-slot-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.booking-times-heading{grid-template-columns:1fr}.booking-selection-hint{font-size:12px}}
@media(max-width:520px){.booking-stepper{justify-content:flex-start;overflow-x:auto}.booking-stepper li{flex:0 0 auto}.booking-stepper::before{display:none}.booking-court-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
.booking-content.is-multi-court{grid-template-columns:minmax(0,1.7fr) minmax(280px,.9fr);align-items:start}
.booking-content.is-multi-court .booking-main-column,.booking-content.is-multi-court .booking-panel,.booking-content.is-multi-court .booking-matrix-scroll{min-width:0;width:100%}
.booking-content.is-multi-court .booking-summary{position:static;grid-column:2;grid-row:2;align-self:start;min-width:0}
.booking-content.is-multi-court .booking-matrix{width:max-content;grid-template-columns:90px repeat(var(--matrix-court-count),148px);min-width:calc(90px + var(--matrix-court-count)*148px)}
.booking-content.is-multi-court .booking-matrix-court-head{z-index:3;white-space:nowrap}
@media(max-width:800px){.booking-content.is-multi-court{grid-template-columns:minmax(0,1fr)}.booking-content.is-multi-court .booking-summary{grid-column:1;grid-row:auto}}
.booking-step-jump{font:inherit;cursor:pointer}
</style>
