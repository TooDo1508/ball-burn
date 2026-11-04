<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { CalendarDays, CalendarRange, Home, LayoutGrid, Mail, Newspaper, PanelsTopLeft, Settings2 } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Tin tức (Blog)',
        href: '/admin/news',
        icon: Newspaper,
    },
    {
        title: 'Quản lý sân',
        href: '/admin/courts',
        icon: CalendarRange,
    },
    {
        title: 'Quản lý đặt sân',
        href: '/admin/bookings',
        icon: CalendarDays,
    },
    {
        title: 'Liên hệ',
        href: '/admin/contact-messages',
        icon: Mail,
    },
    {
        title: 'Cài đặt liên hệ',
        href: '/admin/settings/contact',
        icon: Settings2,
    },
    {
        title: 'Quản lý trang chủ',
        href: '/admin/settings/home-page',
        icon: PanelsTopLeft,
    },
];

const footerNavItems: NavItem[] = [{ title: 'Website BALLBURN', href: '/', icon: Home }];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
