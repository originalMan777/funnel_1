<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, FolderGit2, LayoutGrid, Mail, Megaphone, MessageSquareText, Send, Settings2, Users } from 'lucide-vue-next';
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
        title: 'Communications',
        href: route('admin.communications.index'),
        icon: Mail,
    },
    {
        title: 'Communication Events',
        href: route('admin.communications.events.index'),
        icon: MessageSquareText,
    },
    {
        title: 'Communication Deliveries',
        href: route('admin.communications.deliveries.index'),
        icon: Send,
    },
    {
        title: 'Marketing Syncs',
        href: route('admin.communications.syncs.index'),
        icon: Users,
    },
    {
        title: 'Communication Settings',
        href: route('admin.communications.settings.index'),
        icon: Settings2,
    },
    {
        title: 'Campaigns',
        href: route('admin.campaigns.index'),
        icon: Megaphone,
    },
    {
        title: 'Campaign Enrollments',
        href: route('admin.campaign-enrollments.index'),
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
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
