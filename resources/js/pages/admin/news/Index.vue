<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { CalendarDays, Check, Clock3, Eye, FileText, Filter, Newspaper, Pencil, Plus, RotateCcw, Search, Tags, Trash2 } from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Tin tức', href: '/admin/news' }] } });

type Category = { id: number; name: string };
type Post = {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    status: 'DRAFT' | 'PUBLISHED' | 'SCHEDULED' | 'ARCHIVED';
    published_at: string | null;
    updated_at: string;
    cover_image_url: string | null;
    categories: Category[];
    author: string | null;
};
type PageLink = { url: string | null; label: string; active: boolean };
type Paginator = { data: Post[]; links: PageLink[]; from: number | null; to: number | null; total: number; per_page: number };

const props = defineProps<{
    posts: Paginator;
    stats: { total: number; published: number; draft: number; scheduled: number };
    categories: Category[];
    filters: { search: string; status: string; category: string | number; from: string; to: string; per_page: number };
}>();

const filters = reactive({ ...props.filters });
const selectedIds = ref<number[]>([]);
const selectVisible = computed({
    get: () => props.posts.data.length > 0 && props.posts.data.every((post) => selectedIds.value.includes(post.id)),
    set: (selected: boolean) => {
        const visibleIds = props.posts.data.map((post) => post.id);
        selectedIds.value = selected
            ? [...new Set([...selectedIds.value, ...visibleIds])]
            : selectedIds.value.filter((id) => !visibleIds.includes(id));
    },
});
const statusLabel: Record<Post['status'], string> = {
    PUBLISHED: 'Đã xuất bản',
    DRAFT: 'Bản nháp',
    SCHEDULED: 'Lên lịch',
    ARCHIVED: 'Đã lưu trữ',
};
const statusClass: Record<Post['status'], string> = {
    PUBLISHED: 'border-emerald-500/25 bg-emerald-500/10 text-emerald-300',
    DRAFT: 'border-zinc-500/30 bg-zinc-500/10 text-zinc-300',
    SCHEDULED: 'border-amber-500/25 bg-amber-500/10 text-amber-300',
    ARCHIVED: 'border-rose-500/25 bg-rose-500/10 text-rose-300',
};

function applyFilters() {
    router.get('/admin/news', filters, { preserveState: true, preserveScroll: true, replace: true });
}

function resetFilters() {
    Object.assign(filters, { search: '', status: '', category: '', from: '', to: '', per_page: 5 });
    applyFilters();
}

function destroyPost(post: Post) {
    if (window.confirm(`Bạn muốn xóa bài viết “${post.title}”?`)) {
        router.delete(`/admin/news/${post.id}`, { preserveScroll: true });
    }
}

function destroySelected() {
    if (window.confirm(`Xóa ${selectedIds.value.length} bài viết đã chọn?`)) {
        router.delete('/admin/news/bulk', {
            data: { ids: selectedIds.value },
            preserveScroll: true,
            onSuccess: () => { selectedIds.value = []; },
        });
    }
}

function pageLabel(label: string) {
    return label.replace('&laquo; Previous', '‹').replace('Next &raquo;', '›');
}
</script>

<template>
    <Head title="Quản lý tin tức" />
    <main class="news-admin min-h-full bg-[#0b0c0b] p-4 text-zinc-100 sm:p-6 xl:p-8">
        <div class="mx-auto max-w-[1600px] space-y-6">
            <header class="flex flex-col justify-between gap-4 md:flex-row md:items-end">
                <div>
                    <p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-400">BALLBURN · ADMIN · TIN TỨC</p>
                    <h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><Newspaper class="size-7 text-orange-400" />Quản lý tin tức</h1>
                    <p class="mt-2 text-sm text-zinc-400">Tạo, cập nhật và quản lý các bài viết trên website.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link href="/admin/news/categories" class="inline-flex min-h-11 items-center gap-2 rounded-lg border border-zinc-700 px-4 text-sm font-semibold text-zinc-200 transition hover:border-orange-500 hover:text-orange-300"><Tags class="size-4" />Danh mục</Link>
                    <Link href="/admin/news/create" class="inline-flex min-h-11 items-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white transition hover:bg-orange-600"><Plus class="size-4" />Tạo bài viết</Link>
                </div>
            </header>

            <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4" aria-label="Thống kê bài viết">
                <article class="flex min-h-24 items-center gap-4 rounded-xl border border-zinc-800 bg-[#121412] p-4">
                    <span class="grid size-11 place-items-center rounded-full bg-orange-500/15 text-orange-400"><FileText class="size-5" /></span>
                    <div><p class="text-sm text-zinc-400">Tổng bài viết</p><p class="mt-1 text-2xl font-bold">{{ stats.total }}</p></div>
                </article>
                <article class="flex min-h-24 items-center gap-4 rounded-xl border border-zinc-800 bg-[#121412] p-4">
                    <span class="grid size-11 place-items-center rounded-full bg-emerald-500/15 text-emerald-400"><Check class="size-5" /></span>
                    <div><p class="text-sm text-zinc-400">Đã xuất bản</p><p class="mt-1 text-2xl font-bold">{{ stats.published }}</p></div>
                </article>
                <article class="flex min-h-24 items-center gap-4 rounded-xl border border-zinc-800 bg-[#121412] p-4">
                    <span class="grid size-11 place-items-center rounded-full bg-zinc-500/15 text-zinc-300"><Clock3 class="size-5" /></span>
                    <div><p class="text-sm text-zinc-400">Bản nháp</p><p class="mt-1 text-2xl font-bold">{{ stats.draft }}</p></div>
                </article>
                <article class="flex min-h-24 items-center gap-4 rounded-xl border border-zinc-800 bg-[#121412] p-4">
                    <span class="grid size-11 place-items-center rounded-full bg-amber-500/15 text-amber-300"><CalendarDays class="size-5" /></span>
                    <div><p class="text-sm text-zinc-400">Lên lịch</p><p class="mt-1 text-2xl font-bold">{{ stats.scheduled }}</p></div>
                </article>
            </section>

            <section class="overflow-hidden rounded-xl border border-zinc-800 bg-[#111311]">
                <form class="grid gap-3 border-b border-zinc-800 p-4 2xl:grid-cols-[minmax(230px,1.4fr)_minmax(150px,.7fr)_minmax(150px,.7fr)_minmax(260px,1fr)_auto]" @submit.prevent="applyFilters">
                    <label class="relative block">
                        <span class="sr-only">Tìm bài viết</span>
                        <Search class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500" />
                        <input v-model="filters.search" class="h-11 w-full rounded-lg border border-zinc-700 bg-[#0c0d0c] pl-10 pr-3 text-sm placeholder:text-zinc-500 focus:border-orange-500 focus:outline-none" placeholder="Tìm tiêu đề, nội dung..." />
                    </label>
                    <label>
                        <span class="sr-only">Lọc trạng thái</span>
                        <select v-model="filters.status" class="h-11 w-full rounded-lg border border-zinc-700 bg-[#0c0d0c] px-3 text-sm focus:border-orange-500 focus:outline-none">
                            <option value="">Trạng thái · Tất cả</option><option value="PUBLISHED">Đã xuất bản</option><option value="DRAFT">Bản nháp</option><option value="SCHEDULED">Lên lịch</option><option value="ARCHIVED">Đã lưu trữ</option>
                        </select>
                    </label>
                    <label>
                        <span class="sr-only">Lọc danh mục</span>
                        <select v-model="filters.category" class="h-11 w-full rounded-lg border border-zinc-700 bg-[#0c0d0c] px-3 text-sm focus:border-orange-500 focus:outline-none">
                            <option value="">Danh mục · Tất cả</option><option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
                        </select>
                    </label>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="relative"><span class="sr-only">Từ ngày xuất bản</span><CalendarDays class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500" /><input v-model="filters.from" type="date" class="h-11 w-full rounded-lg border border-zinc-700 bg-[#0c0d0c] pl-9 pr-2 text-sm text-zinc-300 focus:border-orange-500 focus:outline-none" /></label>
                        <label class="relative"><span class="sr-only">Đến ngày xuất bản</span><CalendarDays class="absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500" /><input v-model="filters.to" type="date" class="h-11 w-full rounded-lg border border-zinc-700 bg-[#0c0d0c] pl-9 pr-2 text-sm text-zinc-300 focus:border-orange-500 focus:outline-none" /></label>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600"><Filter class="size-4" /><span class="2xl:hidden">Lọc</span><span class="hidden 2xl:inline">Áp dụng</span></button>
                        <button type="button" class="inline-flex h-11 items-center justify-center gap-2 rounded-lg border border-zinc-700 px-3 text-sm text-zinc-300 hover:border-orange-500 hover:text-orange-300" aria-label="Đặt lại bộ lọc" @click="resetFilters"><RotateCcw class="size-4" /></button>
                    </div>
                </form>

                <div v-if="selectedIds.length" class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-800 bg-orange-500/5 px-4 py-3">
                    <p class="text-sm text-zinc-200">Đã chọn <strong>{{ selectedIds.length }}</strong> bài viết</p>
                    <button class="inline-flex min-h-9 items-center gap-2 rounded-lg border border-rose-500/30 bg-rose-500/10 px-3 text-sm font-semibold text-rose-200 hover:border-rose-500" @click="destroySelected"><Trash2 class="size-4" />Xóa bài đã chọn</button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px] border-collapse text-left">
                        <thead class="bg-[#191b19] text-xs font-bold uppercase tracking-wide text-zinc-400">
                            <tr><th class="w-12 px-4 py-4"><input v-model="selectVisible" type="checkbox" aria-label="Chọn tất cả bài viết đang hiển thị" class="size-4 accent-orange-500" /></th><th class="px-4 py-4">Bài viết</th><th class="px-4 py-4">Danh mục</th><th class="px-4 py-4">Trạng thái</th><th class="px-4 py-4">Ngày xuất bản</th><th class="px-4 py-4">Cập nhật lần cuối</th><th class="px-4 py-4 text-right">Thao tác</th></tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800/80">
                            <tr v-for="post in posts.data" :key="post.id" class="transition hover:bg-white/[0.025]">
                                <td class="px-4 py-4 align-middle"><input v-model="selectedIds" type="checkbox" :value="post.id" :aria-label="`Chọn bài ${post.title}`" class="size-4 accent-orange-500" /></td>
                                <td class="max-w-[500px] px-4 py-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <img v-if="post.cover_image_url" :src="post.cover_image_url" :alt="post.title" class="size-[68px] shrink-0 rounded-lg border border-zinc-700 object-cover" />
                                        <div v-else class="grid size-[68px] shrink-0 place-items-center rounded-lg border border-zinc-700 bg-zinc-800 text-zinc-500"><Newspaper class="size-6" /></div>
                                        <div class="min-w-0"><Link :href="`/admin/news/${post.id}/edit`" class="block truncate text-sm font-bold text-zinc-100 hover:text-orange-300">{{ post.title }}</Link><p class="mt-1 line-clamp-2 text-xs leading-5 text-zinc-400">{{ post.excerpt || post.slug }}</p></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3"><div v-if="post.categories.length" class="flex flex-wrap gap-1.5"><span v-for="category in post.categories.slice(0, 2)" :key="category.id" class="rounded-full border border-orange-500/25 bg-orange-500/10 px-2.5 py-1 text-xs font-semibold text-orange-200">{{ category.name }}</span><span v-if="post.categories.length > 2" class="text-xs text-zinc-400">+{{ post.categories.length - 2 }}</span></div><span v-else class="text-xs text-zinc-500">Chưa phân loại</span></td>
                                <td class="px-4 py-3"><span :class="['inline-flex items-center gap-2 whitespace-nowrap rounded-full border px-2.5 py-1.5 text-xs font-semibold', statusClass[post.status]]"><span class="size-1.5 rounded-full bg-current" />{{ statusLabel[post.status] }}</span></td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-300">{{ post.published_at || '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-zinc-300">{{ post.updated_at }}</td>
                                <td class="px-4 py-3"><div class="flex justify-end gap-2">
                                    <Link :href="`/admin/news/${post.id}/preview`" class="grid size-9 place-items-center rounded-lg border border-zinc-700 bg-zinc-800/70 text-zinc-200 transition hover:border-orange-500 hover:text-orange-300" :aria-label="`Xem trước ${post.title}`"><Eye class="size-4" /></Link>
                                    <Link :href="`/admin/news/${post.id}/edit`" class="grid size-9 place-items-center rounded-lg border border-zinc-700 bg-zinc-800/70 text-zinc-200 transition hover:border-orange-500 hover:text-orange-300" :aria-label="`Sửa ${post.title}`"><Pencil class="size-4" /></Link>
                                    <button class="grid size-9 place-items-center rounded-lg border border-rose-500/15 bg-rose-500/10 text-rose-300 transition hover:border-rose-500 hover:bg-rose-500/20" :aria-label="`Xóa ${post.title}`" @click="destroyPost(post)"><Trash2 class="size-4" /></button>
                                </div></td>
                            </tr>
                            <tr v-if="!posts.data.length"><td colspan="7" class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-zinc-800 text-zinc-400"><Search class="size-5" /></div><p class="mt-3 text-sm font-semibold">Không tìm thấy bài viết</p><p class="mt-1 text-sm text-zinc-400">Hãy thử thay đổi từ khóa hoặc bộ lọc.</p></td></tr>
                        </tbody>
                    </table>
                </div>

                <footer class="flex flex-col gap-3 border-t border-zinc-800 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-wrap items-center gap-3"><p class="text-sm text-zinc-400">Hiển thị <span class="font-semibold text-zinc-200">{{ posts.from ?? 0 }} – {{ posts.to ?? 0 }}</span> trong tổng số <span class="font-semibold text-zinc-200">{{ posts.total }}</span> bài viết</p><label class="flex items-center gap-2 text-sm text-zinc-400">Mỗi trang<select v-model.number="filters.per_page" class="h-9 rounded-lg border border-zinc-700 bg-[#0c0d0c] px-2 text-sm text-zinc-200 focus:border-orange-500 focus:outline-none" @change="applyFilters"><option :value="5">5</option><option :value="10">10</option><option :value="20">20</option><option :value="50">50</option></select></label></div>
                    <nav class="flex items-center justify-end gap-1" aria-label="Phân trang bài viết"><Link v-for="(link, index) in posts.links" :key="`${index}-${link.label}`" :href="link.url || '#'" :preserve-scroll="true" :class="['grid min-h-9 min-w-9 place-items-center rounded-lg border px-2 text-sm transition', link.active ? 'border-orange-500 bg-orange-500 font-bold text-white' : 'border-zinc-700 text-zinc-300 hover:border-orange-500 hover:text-orange-300', !link.url && 'pointer-events-none opacity-40']">{{ pageLabel(link.label) }}</Link></nav>
                </footer>
            </section>
        </div>
    </main>
</template>
