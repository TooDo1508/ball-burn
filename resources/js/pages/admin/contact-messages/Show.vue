<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Mail, MessageSquareText, Phone, Save, UserRound } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Liên hệ', href: '/admin/contact-messages' }, { title: 'Chi tiết yêu cầu' }] } });

type ContactItem = { id: number; name: string; email: string; phone: string | null; subject: string; message: string; status: 'NEW' | 'IN_PROGRESS' | 'RESOLVED' | 'SPAM'; admin_notes: string | null; created_at: string; resolved_at: string | null };
const props = defineProps<{ message: ContactItem }>();
const form = useForm({ status: props.message.status, admin_notes: props.message.admin_notes ?? '' });

function save() {
    form.put(`/admin/contact-messages/${props.message.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Liên hệ từ ${message.name}`" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <Link href="/admin/contact-messages" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold text-foreground/80 hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300"><ArrowLeft class="size-4" />Quay lại hộp thư</Link>
            <div class="grid gap-6 lg:grid-cols-[minmax(0,1.5fr)_minmax(280px,.8fr)]">
                <article class="rounded-xl border border-border bg-card p-5 sm:p-7">
                    <p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">YÊU CẦU #{{ message.id }} · {{ message.created_at }}</p>
                    <h1 class="mt-3 text-2xl font-bold tracking-tight sm:text-3xl">{{ message.subject }}</h1>
                    <div class="mt-6 grid gap-3 border-y border-border py-4 sm:grid-cols-2">
                        <p class="flex items-center gap-2 text-sm text-foreground/80"><UserRound class="size-4 text-orange-700 dark:text-orange-400" />{{ message.name }}</p>
                        <a :href="`mailto:${message.email}`" class="flex min-w-0 items-center gap-2 truncate text-sm text-foreground/80 hover:text-orange-700 dark:hover:text-orange-300"><Mail class="size-4 shrink-0 text-orange-700 dark:text-orange-400" />{{ message.email }}</a>
                        <a v-if="message.phone" :href="`tel:${message.phone}`" class="flex items-center gap-2 text-sm text-foreground/80 hover:text-orange-700 dark:hover:text-orange-300"><Phone class="size-4 text-orange-700 dark:text-orange-400" />{{ message.phone }}</a>
                    </div>
                    <div class="mt-6 whitespace-pre-wrap break-words text-sm leading-7 text-foreground">{{ message.message }}</div>
                </article>

                <aside class="rounded-xl border border-border bg-card p-5 sm:p-6">
                    <h2 class="flex items-center gap-2 text-lg font-bold"><MessageSquareText class="size-5 text-orange-700 dark:text-orange-400" />Theo dõi yêu cầu</h2>
                    <form class="mt-5 grid gap-4" @submit.prevent="save">
                        <label class="grid gap-2 text-sm font-semibold">Trạng thái
                            <select v-model="form.status" class="h-11 rounded-lg border border-input bg-background px-3 text-sm font-normal outline-none focus:border-orange-500"><option value="NEW">Mới</option><option value="IN_PROGRESS">Đang xử lý</option><option value="RESOLVED">Đã giải quyết</option><option value="SPAM">Spam</option></select>
                        </label>
                        <label class="grid gap-2 text-sm font-semibold">Ghi chú nội bộ
                            <textarea v-model="form.admin_notes" rows="7" maxlength="5000" placeholder="Ghi chú chỉ dành cho đội ngũ BALLBURN..." class="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm font-normal leading-6 outline-none placeholder:text-muted-foreground focus:border-orange-500" />
                        </label>
                        <p v-if="$page.props.flash?.success" class="text-sm text-emerald-800 dark:text-emerald-300">{{ $page.props.flash.success }}</p>
                        <p v-if="form.errors.status || form.errors.admin_notes" class="text-sm text-rose-800 dark:text-rose-300">{{ form.errors.status || form.errors.admin_notes }}</p>
                        <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600 disabled:opacity-50"><Save class="size-4" />{{ form.processing ? 'Đang lưu…' : 'Lưu cập nhật' }}</button>
                    </form>
                    <a :href="`mailto:${message.email}?subject=${encodeURIComponent(`BALLBURN - ${message.subject}`)}`" class="mt-3 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold text-foreground hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300"><Mail class="size-4" />Trả lời qua email</a>
                </aside>
            </div>
        </div>
    </main>
</template>
