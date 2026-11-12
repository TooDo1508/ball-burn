<script setup lang="ts">
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import BookingNotificationsBell from '@/components/BookingNotificationsBell.vue';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const showBookingNotifications = computed(() => page.url.startsWith('/admin'));
</script>

<template>
    <header
        class="max-md:sticky max-md:top-0 max-md:z-40 max-md:bg-background/95 max-md:backdrop-blur flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 max-md:px-3 md:px-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1 shrink-0" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>
        <BookingNotificationsBell v-if="showBookingNotifications" class="ml-auto shrink-0" />
    </header>
</template>
