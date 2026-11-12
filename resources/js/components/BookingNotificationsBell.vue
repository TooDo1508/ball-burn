<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Bell, X } from '@lucide/vue';
import { onMounted, onUnmounted, ref } from 'vue';

type BookingNotice = {
    id: number;
    booking_id: number;
    booking_order_id: number;
    booking_code: string;
    customer_name: string;
    courts: string;
    starts_at: string;
    total_amount: string;
    currency: string;
    created_at: string;
    read_at: string | null;
};

const bookingNotices = ref<BookingNotice[]>([]);
const unreadNoticeCount = ref(0);
const noticesOpen = ref(false);
const notificationToast = ref('');
let pollTimer: number | null = null;
let toastTimer: number | null = null;
let loadedInitially = false;
let requestActive = false;

function money(value: string | number, currency = 'VND') {
    return new Intl.NumberFormat('vi-VN', { style: 'currency', currency, minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value));
}

async function refreshNotifications() {
    if (requestActive) return;
    requestActive = true;
    try {
        const response = await fetch('/admin/bookings/notifications', {
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) return;
        const result = await response.json();
        const notices: BookingNotice[] = result.data?.notifications ?? [];

        if (loadedInitially) {
            const knownIds = new Set(bookingNotices.value.map((notice) => notice.id));
            const newestNotice = notices.find((notice) => !knownIds.has(notice.id));
            if (newestNotice) {
                notificationToast.value = `Đơn mới ${newestNotice.booking_code} · ${newestNotice.courts}`;
                if (toastTimer) window.clearTimeout(toastTimer);
                toastTimer = window.setTimeout(() => { notificationToast.value = ''; }, 7000);
            }
        }

        bookingNotices.value = notices;
        unreadNoticeCount.value = result.data?.unread_count ?? 0;
        loadedInitially = true;
    } catch {
        // Keep the bell available during a temporary network failure.
    } finally {
        requestActive = false;
    }
}

async function openNotice(notice: BookingNotice) {
    if (!notice.read_at) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const response = await fetch(`/admin/bookings/notifications/${notice.id}/read`, {
                method: 'PUT',
                credentials: 'same-origin',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (response.ok) {
                notice.read_at = new Date().toISOString();
                unreadNoticeCount.value = Math.max(0, unreadNoticeCount.value - 1);
            }
        } catch {
            // Navigation still works if marking the notice read temporarily fails.
        }
    }

    noticesOpen.value = false;
    router.visit(`/admin/bookings/${notice.booking_id}`);
}

onMounted(() => {
    void refreshNotifications();
    pollTimer = window.setInterval(() => { void refreshNotifications(); }, 15000);
});

onUnmounted(() => {
    if (pollTimer) window.clearInterval(pollTimer);
    if (toastTimer) window.clearTimeout(toastTimer);
});
</script>

<template>
    <div class="relative">
        <button
            type="button"
            class="relative grid size-10 place-items-center rounded-lg text-foreground transition hover:bg-accent hover:text-accent-foreground"
            :aria-label="`Thông báo đơn chờ thanh toán, ${unreadNoticeCount} chưa đọc`"
            :aria-expanded="noticesOpen"
            aria-haspopup="dialog"
            @click="noticesOpen = !noticesOpen"
        >
            <Bell class="size-5" />
            <span v-if="unreadNoticeCount" class="absolute right-0 top-0 grid min-h-5 min-w-5 place-items-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">
                {{ unreadNoticeCount > 99 ? '99+' : unreadNoticeCount }}
            </span>
        </button>

        <div v-if="noticesOpen" class="absolute right-0 top-12 z-50 w-[min(380px,calc(100vw-2rem))] overflow-hidden rounded-xl border border-border bg-card text-card-foreground shadow-2xl" role="dialog" aria-label="Thông báo đơn mới">
            <header class="flex items-center justify-between border-b border-border px-4 py-3">
                <div>
                    <h2 class="text-sm font-bold">Đơn chờ thanh toán</h2>
                    <p class="mt-0.5 text-xs text-muted-foreground">Cập nhật tự động mỗi 15 giây</p>
                </div>
                <span class="rounded-full bg-orange-500/10 px-2 py-1 text-xs font-bold text-orange-700 dark:text-orange-300">{{ unreadNoticeCount }} mới</span>
            </header>
            <div v-if="!bookingNotices.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
                Chưa có đơn mới đang chờ thanh toán.
            </div>
            <div v-else class="max-h-[min(65vh,440px)] divide-y divide-border overflow-y-auto">
                <button
                    v-for="notice in bookingNotices"
                    :key="notice.id"
                    type="button"
                    class="block w-full px-4 py-3 text-left transition hover:bg-muted/60"
                    :class="!notice.read_at ? 'bg-orange-500/[0.04]' : ''"
                    @click="openNotice(notice)"
                >
                    <span class="flex items-start justify-between gap-3">
                        <strong class="text-sm">{{ notice.booking_code }}</strong>
                        <span v-if="!notice.read_at" class="mt-1 size-2 shrink-0 rounded-full bg-orange-600" aria-label="Chưa đọc" />
                    </span>
                    <span class="mt-1 block text-sm">{{ notice.customer_name }} · {{ notice.courts }}</span>
                    <span class="mt-1 block text-xs text-muted-foreground">{{ notice.starts_at }} · {{ money(notice.total_amount, notice.currency) }} · {{ notice.created_at }}</span>
                </button>
            </div>
        </div>

        <div v-if="notificationToast" class="fixed right-5 top-20 z-[60] flex max-w-[calc(100vw-2.5rem)] items-center gap-3 rounded-xl border border-orange-500/30 bg-card px-4 py-3 text-sm font-semibold text-foreground shadow-xl" role="status" aria-live="polite">
            <Bell class="size-4 shrink-0 text-orange-600" />
            {{ notificationToast }}
            <button type="button" class="ml-auto text-muted-foreground hover:text-foreground" aria-label="Đóng thông báo" @click="notificationToast = ''"><X class="size-4" /></button>
        </div>
    </div>
</template>
