<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Save, Tags, Upload, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { dashboard } from '@/routes';

type Category = { id: number; name: string };
type ExistingPost = null | {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    cover_image_path: string | null;
    cover_image_url: string | null;
    cover_image_alt: string | null;
    meta_title: string | null;
    meta_description: string | null;
    status: 'DRAFT' | 'PUBLISHED' | 'ARCHIVED';
    published_at: string | null;
    category_ids: number[];
};

const props = defineProps<{ post: ExistingPost; categories: Category[] }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Tin tức', href: '/admin/news' }, { title: 'Bài viết', href: '/admin/news/create' }] } });

const form = useForm({
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ?? '',
    body: props.post?.body ?? '',
    cover_image: null as File | null,
    cover_image_alt: props.post?.cover_image_alt ?? '',
    remove_cover_image: false,
    meta_title: props.post?.meta_title ?? '',
    meta_description: props.post?.meta_description ?? '',
    status: props.post?.status ?? 'DRAFT',
    published_at: props.post?.published_at ?? '',
    category_ids: props.post?.category_ids ?? [] as number[],
});
const pickedImageUrl = ref<string | null>(null);
const imageUrl = computed(() => pickedImageUrl.value ?? (form.remove_cover_image ? null : props.post?.cover_image_url ?? null));

function chooseImage(event: Event) {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0] ?? null;
    form.cover_image = file;
    form.remove_cover_image = false;
    pickedImageUrl.value = file ? URL.createObjectURL(file) : null;
}

function clearImage() {
    form.cover_image = null;
    form.remove_cover_image = true;
    pickedImageUrl.value = null;
}

function save() {
    if (props.post) {
        form.transform((data) => ({ ...data, _method: 'put' })).post(`/admin/news/${props.post.id}`, { forceFormData: true, preserveScroll: true });
    } else {
        form.post('/admin/news', { forceFormData: true, preserveScroll: true });
    }
}
</script>

<template>
    <Head :title="post ? 'Chỉnh sửa bài viết' : 'Tạo bài viết'" />
    <main class="min-h-full bg-[#0b0c0b] p-4 text-zinc-100 sm:p-6 xl:p-8">
        <div class="mx-auto max-w-6xl space-y-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div><p class="mb-2 text-xs font-bold uppercase tracking-[0.18em] text-orange-400">BALLBURN · ADMIN · TIN TỨC</p><h1 class="text-2xl font-bold tracking-tight sm:text-3xl">{{ post ? 'Chỉnh sửa bài viết' : 'Tạo bài viết mới' }}</h1><p class="mt-2 text-sm text-zinc-400">Biên tập nội dung, ảnh bìa, danh mục và thông tin SEO.</p></div>
                <Link href="/admin/news" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-zinc-700 px-3 text-sm font-semibold text-zinc-300 hover:border-orange-500 hover:text-orange-300"><ArrowLeft class="size-4" />Danh sách bài viết</Link>
            </header>

            <form class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]" @submit.prevent="save">
                <div class="space-y-5">
                    <section class="space-y-5 rounded-xl border border-zinc-800 bg-[#121412] p-4 sm:p-6">
                        <div class="grid gap-2"><label for="title" class="text-sm font-semibold">Tiêu đề <span class="text-orange-400">*</span></label><input id="title" v-model="form.title" required maxlength="220" class="h-11 rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Nhập tiêu đề bài viết" /><InputError :message="form.errors.title" /></div>
                        <div class="grid gap-2"><label for="slug" class="text-sm font-semibold">Đường dẫn (slug)</label><div class="flex h-11 items-center rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3"><span class="shrink-0 text-sm text-zinc-500">/blog/</span><input id="slug" v-model="form.slug" maxlength="240" class="min-w-0 flex-1 bg-transparent text-sm focus:outline-none" placeholder="tu-dong-tao-tu-tieu-de" /></div><p class="text-xs text-zinc-500">Để trống để tạo slug tự động từ tiêu đề.</p><InputError :message="form.errors.slug" /></div>
                        <div class="grid gap-2"><label for="excerpt" class="text-sm font-semibold">Mô tả ngắn</label><textarea id="excerpt" v-model="form.excerpt" rows="3" maxlength="1000" class="rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 py-2.5 text-sm leading-6 focus:border-orange-500 focus:outline-none" placeholder="Tóm tắt nội dung để hiển thị trong danh sách bài viết." /><div class="flex justify-between gap-3"><InputError :message="form.errors.excerpt" /><span class="text-xs text-zinc-500">{{ form.excerpt.length }}/1000</span></div></div>
                        <div class="grid gap-2"><label for="body" class="text-sm font-semibold">Nội dung bài viết <span class="text-orange-400">*</span></label><textarea id="body" v-model="form.body" required rows="18" class="rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 py-3 text-sm leading-7 focus:border-orange-500 focus:outline-none" placeholder="Nhập nội dung bài viết. Xuống dòng để tách đoạn." /><p class="text-xs text-zinc-500">Nội dung dạng văn bản thuần để hiển thị an toàn; trình soạn thảo định dạng sẽ được bổ sung riêng.</p><InputError :message="form.errors.body" /></div>
                    </section>

                    <section class="space-y-5 rounded-xl border border-zinc-800 bg-[#121412] p-4 sm:p-6">
                        <div><h2 class="text-base font-bold">Thông tin SEO</h2><p class="mt-1 text-sm text-zinc-400">Tối ưu tiêu đề và mô tả khi bài viết được chia sẻ hoặc tìm kiếm.</p></div>
                        <div class="grid gap-2"><label for="meta_title" class="text-sm font-semibold">Tiêu đề SEO</label><input id="meta_title" v-model="form.meta_title" maxlength="255" class="h-11 rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Mặc định dùng tiêu đề bài viết" /><div class="flex justify-between gap-3"><InputError :message="form.errors.meta_title" /><span class="text-xs text-zinc-500">{{ form.meta_title.length }}/255</span></div></div>
                        <div class="grid gap-2"><label for="meta_description" class="text-sm font-semibold">Mô tả SEO</label><textarea id="meta_description" v-model="form.meta_description" rows="3" maxlength="500" class="rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 py-2.5 text-sm leading-6 focus:border-orange-500 focus:outline-none" placeholder="Mô tả ngắn cho kết quả tìm kiếm." /><div class="flex justify-between gap-3"><InputError :message="form.errors.meta_description" /><span class="text-xs text-zinc-500">{{ form.meta_description.length }}/500</span></div></div>
                    </section>
                </div>

                <aside class="space-y-5">
                    <section class="space-y-4 rounded-xl border border-zinc-800 bg-[#121412] p-4 sm:p-5">
                        <div><h2 class="text-base font-bold">Xuất bản</h2><p class="mt-1 text-sm text-zinc-400">Chọn trạng thái và thời điểm hiển thị.</p></div>
                        <div class="grid gap-2"><label for="status" class="text-sm font-semibold">Trạng thái</label><select id="status" v-model="form.status" class="h-11 rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 text-sm focus:border-orange-500 focus:outline-none"><option value="DRAFT">Bản nháp</option><option value="PUBLISHED">Xuất bản</option><option value="ARCHIVED">Lưu trữ</option></select><InputError :message="form.errors.status" /></div>
                        <div class="grid gap-2"><label for="published_at" class="text-sm font-semibold">Ngày xuất bản / lên lịch</label><input id="published_at" v-model="form.published_at" type="datetime-local" class="h-11 rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 text-sm focus:border-orange-500 focus:outline-none" /><p class="text-xs leading-5 text-zinc-500">Để trống khi xuất bản để dùng thời điểm hiện tại. Ngày trong tương lai sẽ được tính là lên lịch.</p><InputError :message="form.errors.published_at" /></div>
                        <button type="submit" :disabled="form.processing" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-orange-500 px-4 text-sm font-bold text-white transition hover:bg-orange-600 disabled:cursor-wait disabled:opacity-60"><Save class="size-4" />{{ form.processing ? 'Đang lưu...' : 'Lưu bài viết' }}</button>
                        <p v-if="$page.props.flash?.success" class="text-sm text-emerald-300">{{ $page.props.flash.success }}</p>
                    </section>

                    <section class="space-y-4 rounded-xl border border-zinc-800 bg-[#121412] p-4 sm:p-5">
                        <div class="flex items-center justify-between gap-3"><div><h2 class="text-base font-bold">Danh mục</h2><p class="mt-1 text-sm text-zinc-400">Chọn một hoặc nhiều danh mục.</p></div><Link href="/admin/news/categories" class="rounded-md p-2 text-zinc-400 hover:bg-zinc-800 hover:text-orange-300" aria-label="Quản lý danh mục"><Tags class="size-4" /></Link></div>
                        <div v-if="categories.length" class="max-h-56 space-y-3 overflow-y-auto">
                            <label v-for="category in categories" :key="category.id" class="flex cursor-pointer items-center gap-3 text-sm text-zinc-200"><input v-model="form.category_ids" type="checkbox" :value="category.id" class="size-4 rounded border-zinc-600 accent-orange-500" />{{ category.name }}</label>
                        </div>
                        <div v-else class="rounded-lg border border-dashed border-zinc-700 p-4 text-center"><p class="text-sm text-zinc-400">Chưa có danh mục tin tức.</p><Link href="/admin/news/categories" class="mt-2 inline-flex text-sm font-semibold text-orange-300 hover:text-orange-200">Tạo danh mục</Link></div>
                        <InputError :message="form.errors.category_ids" />
                    </section>

                    <section class="space-y-4 rounded-xl border border-zinc-800 bg-[#121412] p-4 sm:p-5">
                        <div><h2 class="text-base font-bold">Ảnh bìa</h2><p class="mt-1 text-sm text-zinc-400">JPG, PNG hoặc WebP, tối đa 5 MB.</p></div>
                        <div v-if="imageUrl" class="relative overflow-hidden rounded-lg border border-zinc-700"><img :src="imageUrl" alt="Ảnh bìa xem trước" class="aspect-[16/10] w-full object-cover" /><button type="button" class="absolute right-2 top-2 grid size-8 place-items-center rounded-full bg-black/70 text-white hover:bg-rose-600" aria-label="Gỡ ảnh bìa" @click="clearImage"><X class="size-4" /></button></div>
                        <label class="flex min-h-11 cursor-pointer items-center justify-center gap-2 rounded-lg border border-dashed border-zinc-700 px-3 text-sm font-semibold text-zinc-300 hover:border-orange-500 hover:text-orange-300"><Upload class="size-4" />{{ imageUrl ? 'Thay ảnh bìa' : 'Tải ảnh lên' }}<input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="chooseImage" /></label>
                        <InputError :message="form.errors.cover_image" />
                        <div class="grid gap-2"><label for="cover_image_alt" class="text-sm font-semibold">Alt text ảnh</label><input id="cover_image_alt" v-model="form.cover_image_alt" maxlength="255" class="h-10 rounded-lg border border-zinc-700 bg-[#0b0c0b] px-3 text-sm focus:border-orange-500 focus:outline-none" placeholder="Mô tả nội dung ảnh" /><InputError :message="form.errors.cover_image_alt" /></div>
                    </section>
                </aside>
            </form>
        </div>
    </main>
</template>
