<script setup lang="ts">
import { onBeforeUnmount } from 'vue';
import { router } from '@inertiajs/vue3';
import { toast } from 'vue-sonner';
import { Toaster } from '@/components/ui/sonner';
import type { FlashToast } from '@/types/ui';

type SharedFlash = { success?: unknown; error?: unknown };

function sharedFlash(props: unknown): SharedFlash | null {
    if (!props || typeof props !== 'object') return null;

    const flash = (props as Record<string, unknown>).flash;
    return flash && typeof flash === 'object' ? flash as SharedFlash : null;
}

const removeListeners = [
    router.on('success', (event) => {
        const flash = sharedFlash(event.detail.page.props);

        if (typeof flash?.success === 'string' && flash.success) {
            toast.success(flash.success, { duration: 6000 });
        } else if (typeof flash?.error === 'string' && flash.error) {
            toast.error(flash.error, { duration: 6000 });
        }
    }),
    router.on('flash', (event) => {
        const flash = event.detail.flash as unknown as { toast?: FlashToast } | null | undefined;
        const data = flash?.toast;

        if (data) toast[data.type](data.message, { duration: 6000 });
    }),
    router.on('error', (event) => {
        const firstError = Object.values(event.detail.errors).find((message) => typeof message === 'string' && message);
        toast.error(firstError ?? 'Không thể hoàn tất yêu cầu. Vui lòng kiểm tra lại thông tin.', { duration: 6000 });
    }),
    router.on('httpException', (event) => {
        const status = event.detail.response.status;
        toast.error(status === 502
            ? 'Máy chủ chưa thể xử lý yêu cầu. Vui lòng thử lại sau.'
            : 'Máy chủ gặp lỗi khi xử lý yêu cầu. Vui lòng thử lại sau.', { duration: 6000 });
    }),
    router.on('networkError', () => {
        toast.error('Không thể kết nối đến máy chủ. Vui lòng kiểm tra mạng và thử lại.', { duration: 6000 });
    }),
];

onBeforeUnmount(() => removeListeners.forEach((remove) => remove()));
</script>

<template>
    <Teleport to="body">
        <Toaster position="top-right" :duration="6000" :close-button="true" rich-colors />
    </Teleport>
</template>
