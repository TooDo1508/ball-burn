<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { CalendarDays, Mail, MessageSquareText, Phone, RotateCcw, Save, Search, UserRound, X } from '@lucide/vue';
import { nextTick, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Liên hệ', href: '/admin/contact-messages' }] } });

type ContactStatus = 'NEW' | 'IN_PROGRESS' | 'RESOLVED' | 'SPAM';
type ContactItem = { id: number; name: string; email: string; phone: string | null; subject: string; message: string; status: ContactStatus; admin_notes: string | null; created_at: string };
type PageLink = { url: string | null; label: string; active: boolean };
type ContactPage = { data: ContactItem[]; links: PageLink[]; from: number | null; to: number | null; total: number };

const props = defineProps<{
    messages: ContactPage;
    stats: { total: number; new: number; in_progress: number; resolved: number; spam: number };
    filters: { search: string; status: string; from: string; to: string; per_page: number };
}>();

const filters = reactive({ ...props.filters });
const activeMessage = ref<ContactItem | null>(null);
const quickEditDialog = ref<HTMLElement | null>(null);
const quickEditForm = useForm({ status: 'NEW' as ContactStatus, admin_notes: '' });
const statuses: Record<ContactStatus, string> = {
    NEW: 'Mới',
    IN_PROGRESS: 'Đang xử lý',
    RESOLVED: 'Đã giải quyết',
    SPAM: 'Spam',
};
const statusClasses: Record<ContactStatus, string> = {
    NEW: 'border-orange-500/30 bg-orange-500/10 text-orange-800 dark:text-orange-300',
    IN_PROGRESS: 'border-sky-500/30 bg-sky-500/10 text-sky-800 dark:text-sky-300',
    RESOLVED: 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300',
    SPAM: 'border-input bg-muted text-muted-foreground',
};

watch(activeMessage, async (message) => {
    document.body.style.overflow = message ? 'hidden' : '';
    if (message) {
        quickEditForm.status = message.status;
        quickEditForm.admin_notes = message.admin_notes ?? '';
        quickEditForm.clearErrors();
        await nextTick();
        quickEditDialog.value?.focus();
    }
});

onBeforeUnmount(() => { document.body.style.overflow = ''; });

function openQuickEdit(message: ContactItem) {
    activeMessage.value = message;
}

function closeQuickEdit() {
    if (!quickEditForm.processing) activeMessage.value = null;
}

function saveQuickEdit() {
    if (!activeMessage.value) return;
    quickEditForm.put(`/admin/contact-messages/${activeMessage.value.id}`, {
        preserveScroll: true,
        onSuccess: () => { activeMessage.value = null; },
    });
}

function applyFilters() {
    router.get('/admin/contact-messages', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', status: '', from: '', to: '', per_page: 10 });
    applyFilters();
}

function pageLabel(label: string) {
    return label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}
</script>

<template>
    <Head title="Yêu cầu liên hệ" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1500px] space-y-6">
            <header class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN · HỘP THƯ</p>
                    <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><MessageSquareText class="size-7 text-orange-700 dark:text-orange-400" />Yêu cầu liên hệ</h1>
                    <p class="mt-2 text-sm text-muted-foreground">Đọc tin nhắn từ khách và theo dõi trạng thái xử lý.</p>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Thống kê yêu cầu liên hệ">
                <article v-for="item in [
                    { label: 'Tất cả yêu cầu', value: stats.total, icon: MessageSquareText, color: 'text-foreground' },
                    { label: 'Mới', value: stats.new, icon: Mail, color: 'text-orange-800 dark:text-orange-300' },
                    { label: 'Đang xử lý', value: stats.in_progress, icon: UserRound, color: 'text-sky-800 dark:text-sky-300' },
                    { label: 'Đã giải quyết', value: stats.resolved, icon: CalendarDays, color: 'text-emerald-800 dark:text-emerald-300' },
                ]" :key="item.label" class="rounded-xl border border-border bg-card p-4">
                    <div class="flex items-center justify-between"><p class="text-sm text-muted-foreground">{{ item.label }}</p><component :is="item.icon" :class="['size-4', item.color]" /></div>
                    <p class="mt-3 text-2xl font-bold">{{ item.value }}</p>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <form class="grid gap-3 border-b border-border p-4 md:grid-cols-2 xl:grid-cols-[minmax(220px,1.5fr)_180px_160px_160px_auto_auto]" @submit.prevent="applyFilters">
                    <label class="relative block"><span class="sr-only">Tìm theo tên, email hoặc điện thoại</span><Search class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><input v-model="filters.search" type="search" placeholder="Tên, email, số điện thoại..." class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-3 text-sm outline-none focus:border-orange-500" /></label>
                    <label><span class="sr-only">Lọc theo trạng thái</span><select v-model="filters.status" class="h-10 w-full rounded-lg border border-input bg-background px-3 text-sm outline-none focus:border-orange-500"><option value="">Tất cả trạng thái</option><option value="NEW">Mới</option><option value="IN_PROGRESS">Đang xử lý</option><option value="RESOLVED">Đã giải quyết</option><option value="SPAM">Spam</option></select></label>
                    <label class="relative"><span class="sr-only">Từ ngày</span><CalendarDays class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><input v-model="filters.from" type="date" class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-2 text-sm outline-none focus:border-orange-500" /></label>
                    <label class="relative"><span class="sr-only">Đến ngày</span><CalendarDays class="pointer-events-none absolute left-3 top-3 size-4 text-muted-foreground" /><input v-model="filters.to" type="date" class="h-10 w-full rounded-lg border border-input bg-background pl-9 pr-2 text-sm outline-none focus:border-orange-500" /></label>
                    <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600"><Search class="size-4" />Lọc</button>
                    <button type="button" class="inline-flex h-10 items-center justify-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold text-foreground/80 hover:border-orange-500" @click="resetFilters"><RotateCcw class="size-4" />Xóa lọc</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-left text-sm">
                        <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-4 py-3">Người gửi</th><th class="px-4 py-3">Chủ đề</th><th class="px-4 py-3">Nội dung</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Ngày nhận</th><th class="px-4 py-3 text-right">Chi tiết</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="message in messages.data" :key="message.id" class="transition hover:bg-accent">
                                <td class="px-4 py-4"><p class="font-semibold text-foreground">{{ message.name }}</p><a :href="`mailto:${message.email}`" class="mt-1 block text-xs text-muted-foreground hover:text-orange-700 dark:hover:text-orange-300">{{ message.email }}</a><p v-if="message.phone" class="mt-1 text-xs text-muted-foreground">{{ message.phone }}</p></td>
                                <td class="max-w-[220px] px-4 py-4"><p class="line-clamp-2 font-medium">{{ message.subject }}</p></td>
                                <td class="max-w-[360px] px-4 py-4"><p class="line-clamp-2 text-muted-foreground">{{ message.message }}</p></td>
                                <td class="px-4 py-4"><span :class="['inline-flex whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold', statusClasses[message.status]]">{{ statuses[message.status] }}</span></td>
                                <td class="whitespace-nowrap px-4 py-4 text-muted-foreground">{{ message.created_at }}</td>
                                <td class="px-4 py-4 text-right"><button type="button" class="inline-flex min-h-9 items-center rounded-lg border border-input px-3 text-xs font-bold text-foreground hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300" @click="openQuickEdit(message)">Chỉnh sửa</button></td>
                            </tr>
                            <tr v-if="!messages.data.length"><td colspan="6" class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground"><Search class="size-5" /></div><p class="mt-3 text-sm font-semibold">Chưa có yêu cầu phù hợp</p><p class="mt-1 text-sm text-muted-foreground">Tin nhắn được gửi từ trang Liên hệ sẽ xuất hiện ở đây.</p></td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">Hiển thị <span class="font-semibold text-foreground">{{ messages.from ?? 0 }}–{{ messages.to ?? 0 }}</span> trong tổng số <span class="font-semibold text-foreground">{{ messages.total }}</span> yêu cầu</p>
                    <nav class="flex items-center justify-end gap-1" aria-label="Phân trang yêu cầu liên hệ"><Link v-for="(link, index) in messages.links" :key="`${index}-${link.label}`" :href="link.url || '#'" :preserve-scroll="true" :class="['grid min-h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm transition', link.active ? 'border-orange-500 bg-orange-500 font-bold text-white' : 'border-input text-foreground/80 hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300', !link.url && 'pointer-events-none opacity-40']">{{ pageLabel(link.label) }}</Link></nav>
                </footer>
            </section>
        </div>
    </main>

    <Teleport to="body">
        <div v-if="activeMessage" class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/65 p-3 backdrop-blur-sm sm:p-6" @click.self="closeQuickEdit" @keydown.esc="closeQuickEdit">
            <section ref="quickEditDialog" role="dialog" aria-modal="true" aria-labelledby="contact-dialog-title" tabindex="-1" class="w-full max-w-3xl overflow-hidden rounded-2xl border border-border bg-card text-card-foreground shadow-2xl outline-none">
                <header class="flex items-start justify-between gap-4 border-b border-border px-5 py-4 sm:px-7">
                    <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-[0.16em] text-orange-500">Yêu cầu #{{ activeMessage.id }} · {{ activeMessage.created_at }}</p><h2 id="contact-dialog-title" class="mt-1 truncate text-xl font-bold sm:text-2xl">{{ activeMessage.subject }}</h2></div>
                    <button type="button" class="grid size-9 shrink-0 place-items-center rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground" aria-label="Đóng cửa sổ chỉnh sửa" @click="closeQuickEdit"><X class="size-5" /></button>
                </header>

                <div class="grid max-h-[calc(100dvh-10rem)] gap-0 overflow-y-auto md:grid-cols-[minmax(0,1.2fr)_minmax(260px,.8fr)]">
                    <div class="p-5 sm:p-7">
                        <div class="flex flex-wrap gap-x-5 gap-y-2 border-b border-border pb-4 text-sm text-muted-foreground">
                            <span class="inline-flex items-center gap-2 text-foreground"><UserRound class="size-4 text-orange-500" />{{ activeMessage.name }}</span>
                            <a :href="`mailto:${activeMessage.email}`" class="inline-flex min-w-0 items-center gap-2 hover:text-orange-500"><Mail class="size-4 shrink-0" />{{ activeMessage.email }}</a>
                            <a v-if="activeMessage.phone" :href="`tel:${activeMessage.phone}`" class="inline-flex items-center gap-2 hover:text-orange-500"><Phone class="size-4" />{{ activeMessage.phone }}</a>
                        </div>
                        <p class="mt-5 whitespace-pre-wrap break-words text-sm leading-7 text-foreground">{{ activeMessage.message }}</p>
                    </div>

                    <form class="grid content-start gap-4 border-t border-border bg-muted/30 p-5 md:border-l md:border-t-0 sm:p-6" @submit.prevent="saveQuickEdit">
                        <label class="grid gap-2 text-sm font-semibold">Trạng thái
                            <select v-model="quickEditForm.status" class="h-11 rounded-lg border border-input bg-background px-3 text-sm font-normal text-foreground outline-none focus:border-orange-500"><option value="NEW">Mới</option><option value="IN_PROGRESS">Đang xử lý</option><option value="RESOLVED">Đã giải quyết</option><option value="SPAM">Spam</option></select>
                            <span v-if="quickEditForm.errors.status" class="text-xs text-destructive">{{ quickEditForm.errors.status }}</span>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Ghi chú nội bộ
                            <textarea v-model="quickEditForm.admin_notes" rows="6" maxlength="5000" placeholder="Ghi chú chỉ dành cho đội ngũ BALLBURN..." class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm font-normal leading-6 text-foreground outline-none placeholder:text-muted-foreground focus:border-orange-500" />
                            <span v-if="quickEditForm.errors.admin_notes" class="text-xs text-destructive">{{ quickEditForm.errors.admin_notes }}</span>
                        </label>
                        <div class="mt-1 flex flex-wrap justify-end gap-2">
                            <button type="button" class="inline-flex min-h-10 items-center justify-center rounded-lg border border-input px-4 text-sm font-semibold hover:bg-muted" :disabled="quickEditForm.processing" @click="closeQuickEdit">Hủy</button>
                            <button type="submit" class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600 disabled:opacity-50" :disabled="quickEditForm.processing"><Save class="size-4" />{{ quickEditForm.processing ? 'Đang lưu…' : 'Lưu cập nhật' }}</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </Teleport>
</template>
