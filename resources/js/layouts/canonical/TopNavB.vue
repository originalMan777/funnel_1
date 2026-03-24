<script setup lang="ts">
import { computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

const page = usePage();

const currentUrl = computed(() => String(page.url || '').toLowerCase());

const currentSection = computed(() => {
    const url = currentUrl.value;

    if (url.startsWith('/admin/posts')) {
        return {
            title: 'Posts',
            description: 'Manage blog posts, drafts, and publishing.',
        };
    }

    if (url.startsWith('/admin/categories')) {
        return {
            title: 'Categories',
            description: 'Organize blog categories and content structure.',
        };
    }

    if (url.startsWith('/admin/tags')) {
        return {
            title: 'Tags',
            description: 'Manage tagging for filtering and discovery.',
        };
    }

    if (url.startsWith('/admin/media')) {
        return {
            title: 'Media Library',
            description: 'Browse, upload, and manage site media.',
        };
    }

    if (url.startsWith('/admin/popups')) {
        return {
            title: 'Popups',
            description: 'Manage popup content, targeting, and lead capture.',
        };
    }

    if (url.startsWith('/admin/lead-boxes')) {
        return {
            title: 'Lead Boxes',
            description: 'Build and manage reusable lead capture blocks.',
        };
    }

    if (url.startsWith('/admin/lead-slots')) {
        return {
            title: 'Lead Slots',
            description:
                'Control which lead blocks appear in each homepage slot.',
        };
    }

    if (url.startsWith('/admin/content-formula')) {
        return {
            title: 'Content Formula',
            description:
                'Generate structured article directions and weighted content ideas.',
        };
    }

    return {
        title: 'Dashboard',
        description: 'Admin overview and system navigation.',
    };
});

const adminLinks = [
    { label: 'Dashboard', href: '/admin' },
    { label: 'Posts', href: '/admin/posts' },
    { label: 'Categories', href: '/admin/categories' },
    { label: 'Tags', href: '/admin/tags' },
    { label: 'Media Library', href: '/admin/media' },
    { label: 'Media Browser', href: '/admin/media/browser' },
    { label: 'Popups', href: '/admin/popups' },
    { label: 'Lead Boxes', href: '/admin/lead-boxes' },
    { label: 'Lead Slots', href: '/admin/lead-slots' },
    { label: 'Content Formula', href: '/admin/content-formula' },
];

const siteLinks = [
    { label: 'Home', href: '/' },
    { label: 'About', href: '/about' },
    { label: 'Services', href: '/services' },
    { label: 'Consultation', href: '/consultation' },
    { label: 'Resources', href: '/resources' },
    { label: 'Buyers', href: '/buyers' },
    { label: 'Sellers', href: '/sellers' },
    { label: 'Blog Index', href: '/blog' },
    { label: 'Contact', href: '/contact' },
];

const isActive = (href: string) =>
    currentUrl.value === href.toLowerCase() ||
    currentUrl.value.startsWith(`${href.toLowerCase()}/`);
</script>

<template>
    <div class="border-b border-gray-200 bg-white px-4 py-3">
        <div
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between"
        >
            <div class="min-w-0">
                <div
                    class="text-[11px] font-semibold tracking-[0.18em] text-gray-500 uppercase"
                >
                    Admin
                </div>
                <div class="mt-1 flex min-w-0 items-baseline gap-3">
                    <h2 class="truncate text-base font-semibold text-gray-900">
                        {{ currentSection.title }}
                    </h2>
                    <p class="hidden truncate text-sm text-gray-500 md:block">
                        {{ currentSection.description }}
                    </p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2 text-sm">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                        >
                            Admin Nav
                            <ChevronDown class="h-4 w-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-64 rounded-xl">
                        <DropdownMenuLabel>Admin pages</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-for="item in adminLinks"
                            :key="item.href"
                            :as-child="true"
                        >
                            <Link
                                :href="item.href"
                                class="flex w-full cursor-pointer items-center justify-between"
                            >
                                <span>{{ item.label }}</span>
                                <span
                                    v-if="isActive(item.href)"
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Current
                                </span>
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>

                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <button
                            type="button"
                            class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-3.5 py-2 font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                        >
                            Site Nav
                            <ChevronDown class="h-4 w-4" />
                        </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="w-64 rounded-xl">
                        <DropdownMenuLabel>Front-end pages</DropdownMenuLabel>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem
                            v-for="item in siteLinks"
                            :key="item.href"
                            :as-child="true"
                        >
                            <Link
                                :href="item.href"
                                class="block w-full cursor-pointer"
                            >
                                {{ item.label }}
                            </Link>
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
        </div>
    </div>
</template>
