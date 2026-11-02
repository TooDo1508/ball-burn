<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';

type HomeContent = Record<string, string>;

const props = defineProps<{ content: HomeContent }>();
const form = useForm({ content: { ...props.content } });

const fields = [
    { key: 'site_name', label: 'Tên website', type: 'text' },
    { key: 'meta_title', label: 'Tiêu đề SEO', type: 'text', hint: 'Tối đa 70 ký tự.' },
    { key: 'meta_description', label: 'Mô tả SEO', type: 'textarea', hint: 'Tối đa 160 ký tự, nên mô tả rõ dịch vụ và khu vực.' },
    { key: 'hero_title', label: 'Tiêu đề chính', type: 'text' },
    { key: 'hero_description', label: 'Mô tả hero', type: 'textarea' },
    { key: 'hero_image', label: 'Ảnh hero URL', type: 'url', hint: 'Dùng ảnh venue đã được cấp quyền, có alt mô tả khi hiển thị.' },
    { key: 'about_title', label: 'Tiêu đề giới thiệu', type: 'text' },
    { key: 'about_text', label: 'Nội dung giới thiệu', type: 'textarea' },
    { key: 'courts_title', label: 'Tiêu đề khu sân', type: 'text' },
    { key: 'courts_text', label: 'Mô tả khu sân', type: 'textarea' },
    { key: 'bistro_title', label: 'Tiêu đề Bistro', type: 'text' },
    { key: 'bistro_text', label: 'Mô tả Bistro', type: 'textarea' },
    { key: 'booking_title', label: 'Tiêu đề CTA đặt sân', type: 'text' },
    { key: 'og_image', label: 'Ảnh chia sẻ mạng xã hội URL', type: 'url' },
];

function save() {
    form.put('/admin/settings/home-page', { preserveScroll: true });
}
</script>

<template>
    <Head title="Quản lý trang chủ" />
    <section class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
        <Heading title="Quản lý trang chủ" description="Chỉnh sửa nội dung hiển thị, ảnh và metadata SEO của landing page." />
        <form class="grid gap-5 rounded-xl border bg-card p-6" @submit.prevent="save">
            <div v-for="field in fields" :key="field.key" class="grid gap-2">
                <label class="text-sm font-medium" :for="field.key">{{ field.label }}</label>
                <textarea v-if="field.type === 'textarea'" :id="field.key" v-model="form.content[field.key]" rows="3" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                <input v-else :id="field.key" v-model="form.content[field.key]" :type="field.type" class="w-full rounded-md border bg-background px-3 py-2 text-sm" />
                <p v-if="field.hint" class="text-xs text-muted-foreground">{{ field.hint }} <span v-if="field.key === 'meta_title'">{{ form.content[field.key]?.length ?? 0 }}/70</span><span v-if="field.key === 'meta_description'">{{ form.content[field.key]?.length ?? 0 }}/160</span></p>
                <p v-if="form.errors[`content.${field.key}`]" class="text-sm text-destructive">{{ form.errors[`content.${field.key}`] }}</p>
            </div>
            <p v-if="$page.props.flash?.success" class="text-sm text-green-700">{{ $page.props.flash.success }}</p>
            <div class="flex items-center gap-3">
                <button type="submit" :disabled="form.processing" class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-primary-foreground disabled:opacity-50">{{ form.processing ? 'Đang lưu…' : 'Lưu thay đổi' }}</button>
                <a href="/" target="_blank" class="text-sm underline">Xem trang chủ</a>
            </div>
        </form>
    </section>
</template>
