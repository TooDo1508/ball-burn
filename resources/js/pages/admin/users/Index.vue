<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, ShieldCheck, UserRound, Users } from '@lucide/vue';
import { reactive } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Quản lý người dùng', href: '/admin/users' }] } });

type UserItem = {
    id: number;
    name: string;
    email: string;
    role: 'ADMIN' | 'STAFF';
    is_active: boolean;
    created_at: string | null;
};
type PageLink = { url: string | null; label: string; active: boolean };
type UserPage = { data: UserItem[]; links: PageLink[]; from: number | null; to: number | null; total: number };

const props = defineProps<{ users: UserPage; filters: { search: string } }>();
const filters = reactive({ ...props.filters });

function applyFilters() {
    router.get('/admin/users', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function pageLabel(label: string) {
    return label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}
</script>

<template>
    <Head title="Quản lý người dùng" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1400px] space-y-6">
            <header>
                <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN · TÀI KHOẢN</p>
                <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><Users class="size-7 text-orange-700 dark:text-orange-400" />Quản lý người dùng</h1>
                <p class="mt-2 text-sm text-muted-foreground">Danh sách tài khoản quản trị và nhân sự có quyền truy cập trang quản trị.</p>
            </header>

            <section class="flex gap-3 rounded-xl border border-orange-500/20 bg-orange-500/5 p-4 text-sm leading-6 text-foreground/80">
                <ShieldCheck class="mt-0.5 size-5 shrink-0 text-orange-600" />
                <p>Khách đặt sân không cần tài khoản. Tài khoản quản trị mới được cấp bằng lệnh <code class="rounded bg-background px-1.5 py-0.5 font-mono text-xs">php artisan admin:create</code>.</p>
            </section>

            <section class="overflow-hidden rounded-xl border border-border bg-card">
                <form class="flex flex-col gap-3 border-b border-border p-4 sm:flex-row" @submit.prevent="applyFilters">
                    <label class="relative block min-w-0 flex-1">
                        <span class="sr-only">Tìm theo tên hoặc email</span>
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
                        <input v-model="filters.search" type="search" placeholder="Tìm tên hoặc email..." class="h-11 w-full rounded-lg border border-input bg-background pl-10 pr-3 text-sm outline-none focus:border-orange-500" />
                    </label>
                    <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600"><Search class="size-4" />Tìm kiếm</button>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[700px] text-left text-sm">
                        <thead class="bg-muted/50 text-xs uppercase tracking-wide text-muted-foreground"><tr><th class="px-4 py-3">Người dùng</th><th class="px-4 py-3">Vai trò</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3">Ngày tạo</th></tr></thead>
                        <tbody class="divide-y divide-border">
                            <tr v-for="user in users.data" :key="user.id" class="transition hover:bg-accent">
                                <td class="px-4 py-4"><div class="flex items-center gap-3"><span class="grid size-10 shrink-0 place-items-center rounded-full bg-orange-500/10 text-orange-700 dark:text-orange-300"><UserRound class="size-5" /></span><div class="min-w-0"><p class="truncate font-semibold">{{ user.name }}</p><p class="mt-1 truncate text-xs text-muted-foreground">{{ user.email }}</p></div></div></td>
                                <td class="px-4 py-4"><span class="inline-flex rounded-full border border-border bg-muted px-2.5 py-1 text-xs font-semibold">{{ user.role === 'ADMIN' ? 'Quản trị viên' : 'Nhân viên' }}</span></td>
                                <td class="px-4 py-4"><span :class="['inline-flex rounded-full border px-2.5 py-1 text-xs font-semibold', user.is_active ? 'border-emerald-500/30 bg-emerald-500/10 text-emerald-800 dark:text-emerald-300' : 'border-border bg-muted text-muted-foreground']">{{ user.is_active ? 'Đang hoạt động' : 'Đã khóa' }}</span></td>
                                <td class="whitespace-nowrap px-4 py-4 text-muted-foreground">{{ user.created_at || '—' }}</td>
                            </tr>
                            <tr v-if="!users.data.length"><td colspan="4" class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground"><Users class="size-5" /></div><p class="mt-3 text-sm font-semibold">Không tìm thấy tài khoản</p><p class="mt-1 text-sm text-muted-foreground">Thử tìm bằng tên hoặc địa chỉ email.</p></td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-col gap-3 border-t border-border px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-sm text-muted-foreground">Hiển thị <span class="font-semibold text-foreground">{{ users.from ?? 0 }}–{{ users.to ?? 0 }}</span> trong tổng số <span class="font-semibold text-foreground">{{ users.total }}</span> tài khoản</p>
                    <nav class="flex items-center justify-end gap-1" aria-label="Phân trang tài khoản"><Link v-for="(link, index) in users.links" :key="`${index}-${link.label}`" :href="link.url || '#'" :preserve-scroll="true" :class="['grid min-h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm transition', link.active ? 'border-orange-500 bg-orange-500 font-bold text-white' : 'border-input text-foreground/80 hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300', !link.url && 'pointer-events-none opacity-40']">{{ pageLabel(link.label) }}</Link></nav>
                </footer>
            </section>
        </div>
    </main>
</template>
