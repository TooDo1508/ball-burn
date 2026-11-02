<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Pencil } from '@lucide/vue';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }, { title: 'Tin tức', href: '/admin/news' }, { title: 'Xem trước', href: '#' }] } });
defineProps<{ post: {
    id: number;
    title: string;
    slug: string;
    excerpt: string | null;
    body: string;
    status: string;
    published_at: string | null;
    cover_image_url: string | null;
    cover_image_alt: string | null;
    categories: string[];
    author: string | null;
    meta_title: string | null;
    meta_description: string | null;
} }>();
</script>

<template>
    <Head :title="`Xem trước · ${post.title}`" />
    <main class="min-h-full bg-[#0b0c0b] p-4 text-zinc-100 sm:p-6 xl:p-8">
        <article class="mx-auto max-w-4xl overflow-hidden rounded-xl border border-zinc-800 bg-[#121412]">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b border-zinc-800 px-5 py-4">
                <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-orange-400">XEM TRƯỚC · /blog/{{ post.slug }}</p><p class="mt-1 text-sm text-zinc-400">Trạng thái: {{ post.status }}<span v-if="post.published_at"> · {{ post.published_at }}</span></p></div>
                <div class="flex gap-2"><Link href="/admin/news" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-zinc-700 px-3 text-sm font-semibold text-zinc-300 hover:border-orange-500 hover:text-orange-300"><ArrowLeft class="size-4" />Quay lại</Link><Link :href="`/admin/news/${post.id}/edit`" class="inline-flex min-h-10 items-center gap-2 rounded-lg bg-orange-500 px-3 text-sm font-bold text-white hover:bg-orange-600"><Pencil class="size-4" />Chỉnh sửa</Link></div>
            </header>
            <img v-if="post.cover_image_url" :src="post.cover_image_url" :alt="post.cover_image_alt || post.title" class="max-h-[480px] w-full object-cover" />
            <div class="space-y-6 p-5 sm:p-10">
                <div class="flex flex-wrap gap-2"><span v-for="category in post.categories" :key="category" class="rounded-full border border-orange-500/25 bg-orange-500/10 px-3 py-1 text-xs font-semibold text-orange-200">{{ category }}</span></div>
                <div><h1 class="text-3xl font-bold leading-tight tracking-tight sm:text-5xl">{{ post.title }}</h1><p v-if="post.excerpt" class="mt-5 text-base leading-7 text-zinc-300">{{ post.excerpt }}</p><p class="mt-4 text-sm text-zinc-500">{{ post.author || 'BALLBURN' }}<span v-if="post.published_at"> · {{ post.published_at }}</span></p></div>
                <div class="whitespace-pre-line text-sm leading-7 text-zinc-200">{{ post.body }}</div>
                <section class="rounded-lg border border-zinc-800 bg-[#0b0c0b] p-4"><p class="text-xs font-bold uppercase tracking-wide text-zinc-500">SEO</p><p class="mt-2 text-sm font-semibold">{{ post.meta_title || post.title }}</p><p class="mt-1 text-sm text-zinc-400">{{ post.meta_description || post.excerpt || 'Chưa thiết lập mô tả SEO.' }}</p></section>
            </div>
        </article>
    </main>
</template>
