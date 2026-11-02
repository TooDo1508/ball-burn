<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { dashboard } from '@/routes';

defineOptions({ layout: { breadcrumbs: [{ title: 'Quản trị', href: dashboard() }] } });
const page = usePage();
</script>

<template>
    <Head title="Quản trị BALLBURN" />
    <section class="flex flex-col gap-4 p-6">
        <p class="text-sm font-semibold uppercase tracking-widest text-muted-foreground">BALLBURN · Admin</p>
        <h1 class="text-3xl font-semibold">Xin chào, {{ page.props.auth.user.name }}</h1>
        <p class="max-w-xl text-muted-foreground">Khu vực dành cho quản trị viên và nhân viên. Bạn có thể cập nhật hồ sơ, đổi mật khẩu và bật xác thực hai bước trong phần cài đặt tài khoản.</p>
        <div class="rounded-xl border p-6 text-sm text-muted-foreground">Các chức năng quản lý sân, lịch đặt và thanh toán sẽ được bổ sung ở các bước tiếp theo.</div>
    </section>
</template>
