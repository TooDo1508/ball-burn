<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Check, Pencil, Plus, Tags, Trash2, X } from '@lucide/vue';
import { ref } from 'vue';
import { confirmAction } from '@/lib/confirmDialog';
import InputError from '@/components/InputError.vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Tin tức', href: '/admin/news' }, { title: 'Danh mục', href: '/admin/news/categories' }] } });
type Category = { id: number; name: string; slug: string; description: string | null; posts_count: number };
const props = defineProps<{ categories: Category[] }>();
const editingId = ref<number | null>(null);
const form = useForm({ name: '', slug: '', description: '' });

function startEdit(category: Category) {
    editingId.value = category.id;
    form.name = category.name;
    form.slug = category.slug;
    form.description = category.description ?? '';
    form.clearErrors();
}

function reset() {
    editingId.value = null;
    form.reset();
    form.clearErrors();
}

function save() {
    if (editingId.value) {
        form.put(`/admin/news/categories/${editingId.value}`, { preserveScroll: true, onSuccess: reset });
    } else {
        form.post('/admin/news/categories', { preserveScroll: true, onSuccess: reset });
    }
}

async function destroyCategory(category: Category) {
    const detail = category.posts_count ? ` Danh mục này đang gắn với ${category.posts_count} bài viết.` : '';
    if (await confirmAction({
        title: 'Xóa danh mục?',
        description: `Xóa danh mục “${category.name}”?${detail} Bài viết vẫn được giữ lại.`,
        confirmLabel: 'Xóa danh mục',
        destructive: true,
    })) {
        router.delete(`/admin/news/categories/${category.id}`, { preserveScroll: true });
    }
}
</script>

<template>
    <Head title="Danh mục tin tức" />
    <main class="min-h-full bg-background p-4 text-foreground sm:p-6 xl:p-8">
        <div class="mx-auto max-w-5xl space-y-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-700 dark:text-orange-400">BALLBURN · ADMIN · TIN TỨC</p><h1 class="flex items-center gap-3 text-2xl font-bold tracking-tight sm:text-3xl"><Tags class="size-7 text-orange-700 dark:text-orange-400" />Danh mục tin tức</h1><p class="mt-2 text-sm text-muted-foreground">Tạo và sắp xếp danh mục để phân loại bài viết.</p></div>
                <Link href="/admin/news" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-input px-3 text-sm font-semibold text-foreground/80 hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300"><ArrowLeft class="size-4" />Danh sách bài viết</Link>
            </header>

            <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
                <section class="overflow-hidden rounded-xl border border-border bg-card">
                    <div class="border-b border-border px-5 py-4"><h2 class="font-bold">Danh mục hiện có</h2><p class="mt-1 text-sm text-muted-foreground">{{ props.categories.length }} danh mục</p></div>
                    <div v-if="categories.length" class="divide-y divide-border">
                        <article v-for="category in categories" :key="category.id" class="flex items-center justify-between gap-4 px-5 py-4 hover:bg-accent">
                            <div class="min-w-0"><h3 class="truncate text-sm font-semibold">{{ category.name }}</h3><p class="mt-1 truncate text-xs text-muted-foreground">{{ category.slug }}<span v-if="category.description"> · {{ category.description }}</span></p><p class="mt-1 text-xs text-muted-foreground">{{ category.posts_count }} bài viết</p></div>
                            <div class="flex shrink-0 gap-2"><button class="grid size-9 place-items-center rounded-lg border border-input text-foreground/80 hover:border-orange-500 hover:text-orange-700 dark:hover:text-orange-300" :aria-label="`Sửa ${category.name}`" @click="startEdit(category)"><Pencil class="size-4" /></button><button class="grid size-9 place-items-center rounded-lg border border-rose-500/20 bg-rose-500/10 text-rose-800 dark:text-rose-300 hover:border-rose-500" :aria-label="`Xóa ${category.name}`" @click="destroyCategory(category)"><Trash2 class="size-4" /></button></div>
                        </article>
                    </div>
                    <div v-else class="px-6 py-16 text-center"><div class="mx-auto grid size-12 place-items-center rounded-full bg-muted text-muted-foreground"><Tags class="size-5" /></div><p class="mt-3 text-sm font-semibold">Chưa có danh mục</p><p class="mt-1 text-sm text-muted-foreground">Tạo danh mục đầu tiên ở khung bên cạnh.</p></div>
                </section>

                <form class="h-fit space-y-4 rounded-xl border border-border bg-card p-5" @submit.prevent="save">
                    <div class="flex items-start justify-between gap-2"><div><h2 class="font-bold">{{ editingId ? 'Chỉnh sửa danh mục' : 'Tạo danh mục' }}</h2><p class="mt-1 text-sm text-muted-foreground">Slug sẽ tự tạo nếu để trống.</p></div><button v-if="editingId" type="button" class="rounded p-1 text-muted-foreground hover:bg-muted" aria-label="Hủy chỉnh sửa" @click="reset"><X class="size-4" /></button></div>
                    <div class="grid gap-2"><label for="category-name" class="text-sm font-semibold">Tên danh mục <span class="text-orange-700 dark:text-orange-400">*</span></label><input id="category-name" v-model="form.name" required maxlength="120" class="h-11 rounded-lg border border-input bg-background px-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Ví dụ: Tips & Tricks" /><InputError :message="form.errors.name" /></div>
                    <div class="grid gap-2"><label for="category-slug" class="text-sm font-semibold">Slug</label><input id="category-slug" v-model="form.slug" maxlength="140" class="h-11 rounded-lg border border-input bg-background px-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="tips-tricks" /><InputError :message="form.errors.slug" /></div>
                    <div class="grid gap-2"><label for="category-description" class="text-sm font-semibold">Mô tả</label><textarea id="category-description" v-model="form.description" rows="3" maxlength="500" class="rounded-lg border border-input bg-background px-3 py-2.5 text-sm focus:border-orange-500 focus:outline-none" placeholder="Mô tả ngắn về danh mục" /><InputError :message="form.errors.description" /></div>
                    <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white hover:bg-orange-600 disabled:opacity-60"><Check v-if="editingId" class="size-4" /><Plus v-else class="size-4" />{{ editingId ? 'Cập nhật danh mục' : 'Tạo danh mục' }}</button>
                </form>
            </div>
        </div>
    </main>
</template>
