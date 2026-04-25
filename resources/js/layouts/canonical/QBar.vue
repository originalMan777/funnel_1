<template>
    <div class="qbar flex h-full w-full flex-col bg-white">
        <div
            class="flex items-center justify-between border-b border-gray-200 px-4 py-1.5"
        >
            <div>
                <div
                    class="text-[10px] font-semibold tracking-[0.18em] text-gray-500 uppercase"
                >
                    {{ isAnalyticsMode ? 'QBar' : 'Domain Queue' }}
                </div>
                <div class="text-xs font-medium leading-5 text-gray-800">
                    {{ headerTitle }}
                </div>
            </div>

            <div class="flex items-center gap-2.5">
                <div class="text-[11px] text-gray-500">
                    <span v-if="!isAnalyticsMode && isLoading"
                        >Refreshing…</span
                    >
                    <span v-else>{{ headerCount }}</span>
                </div>

                <button
                    type="button"
                    class="inline-flex items-center rounded-md border border-gray-300 px-2.5 py-0.5 text-[11px] font-medium text-gray-700 hover:bg-gray-50"
                    @click="$emit('toggle')"
                >
                    {{ open ? 'Collapse' : 'Expand' }}
                </button>
            </div>
        </div>

        <div v-if="open" class="min-h-0 flex-1 overflow-x-auto overflow-y-hidden px-4 pt-3 pb-2">
            <template v-if="isAnalyticsMode">
                <div>
                    <div
                        v-if="visibleAnalyticsMetrics.length === 0"
                        class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-sm text-gray-500"
                    >
                        No analytics metrics are available for this page yet.
                    </div>

                    <div v-else class="flex flex-nowrap gap-3">
                        <button
                            v-for="(item, index) in visibleAnalyticsMetrics"
                            :key="item.key"
                            type="button"
                            class="group relative flex h-16 w-48 shrink-0 flex-col justify-between rounded-xl border px-3 py-2 text-left transition"
                            :class="
                                item.key ===
                                analyticsQBarState.selectedMetricKey
                                    ? 'border-black bg-gray-900 text-white'
                                    : 'border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-400 hover:bg-white'
                            "
                            @click="setAnalyticsMetric(item.key)"
                        >
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex min-w-0 items-center gap-2">
                                    <span
                                        class="inline-flex h-5 min-w-5 items-center justify-center rounded-md px-1.5 text-[10px] font-semibold"
                                        :class="
                                            item.key ===
                                            analyticsQBarState.selectedMetricKey
                                                ? 'bg-white/15 text-white'
                                                : 'bg-white text-gray-700 ring-1 ring-gray-200'
                                        "
                                    >
                                        {{ index + 1 }}
                                    </span>

                                    <div
                                        class="truncate text-[12px] leading-tight font-semibold"
                                    >
                                        {{ item.label }}
                                    </div>
                                </div>

                                <span
                                    v-if="
                                        item.key ===
                                        analyticsQBarState.selectedMetricKey
                                    "
                                    class="shrink-0 text-[9px] font-semibold tracking-[0.14em] uppercase"
                                >
                                    Active
                                </span>
                            </div>

                            <div class="flex items-end justify-between gap-2">
                                <div
                                    class="text-base leading-none font-semibold"
                                >
                                    {{ item.value }}
                                </div>

                                <div
                                    v-if="item.meta"
                                    class="truncate text-[10px] leading-none tracking-[0.12em] uppercase opacity-70"
                                >
                                    {{ item.meta }}
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </template>

            <template v-else>
                <div
                    v-if="displayDomains.length === 0"
                    class="rounded-lg border border-dashed border-gray-300 px-4 py-4 text-sm text-gray-500"
                >
                    No domains available yet.
                </div>

                <div v-else class="flex flex-nowrap gap-3">
                    <component
                        :is="item.href ? Link : 'div'"
                        v-for="(item, index) in displayDomains"
                        :key="item.key"
                        :href="item.href || undefined"
                        class="group relative flex h-16 w-44 shrink-0 flex-col justify-between rounded-xl border px-3 py-2 text-left transition"
                        :class="
                            item.key === currentDomain
                                ? 'border-black bg-gray-900 text-white'
                                : 'border-gray-300 bg-gray-50 text-gray-800 hover:border-gray-400 hover:bg-white'
                        "
                    >
                        <div class="flex items-center justify-between gap-2">
                            <div class="flex min-w-0 items-center gap-2">
                                <span
                                    class="inline-flex h-5 min-w-5 items-center justify-center rounded-md px-1.5 text-[10px] font-semibold"
                                    :class="
                                        item.key === currentDomain
                                            ? 'bg-white/15 text-white'
                                            : 'bg-white text-gray-700 ring-1 ring-gray-200'
                                    "
                                >
                                    {{
                                        recentDomains.length > 0
                                            ? index + 1
                                            : item.badge
                                    }}
                                </span>

                                <div
                                    class="truncate text-[12px] leading-tight font-semibold"
                                >
                                    {{ item.label }}
                                </div>
                            </div>

                            <span
                                v-if="item.key === currentDomain"
                                class="shrink-0 text-[9px] font-semibold tracking-[0.14em] uppercase"
                            >
                                Current
                            </span>
                        </div>

                        <div class="flex items-end justify-between gap-2">
                            <div
                                class="truncate text-[11px] leading-none uppercase opacity-70"
                            >
                                {{ item.key }}
                            </div>
                        </div>
                    </component>
                </div>
            </template>
        </div>
    </div>
</template>

<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed, onMounted, ref, watch } from 'vue';
import { useQBarState } from '@/layouts/canonical/useQBarState';

type QBarDomain = {
    key: string;
    label: string;
    badge: string;
    href: string | null;
};

type QBarPayload = {
    currentDomain: string | null;
    recentDomains: QBarDomain[];
    availableDomains: QBarDomain[];
};

const fallbackDomains: QBarDomain[] = [
    { key: 'dashboard', label: 'Dashboard', badge: 'DB', href: '/admin' },
    { key: 'posts', label: 'Posts', badge: 'PO', href: '/admin/posts' },
    {
        key: 'categories',
        label: 'Categories',
        badge: 'CA',
        href: '/admin/categories',
    },
    { key: 'tags', label: 'Tags', badge: 'TG', href: '/admin/tags' },
    { key: 'media', label: 'Media', badge: 'ME', href: '/admin/media' },
    { key: 'popups', label: 'Popups', badge: 'PU', href: '/admin/popups' },
];

defineProps<{
    open: boolean;
}>();

defineEmits<{
    (e: 'toggle'): void;
}>();

const page = usePage();
const isLoading = ref(false);
const currentDomain = ref<string>('dashboard');
const recentDomains = ref<QBarDomain[]>([]);
const availableDomains = ref<QBarDomain[]>(fallbackDomains);
const { analyticsQBarState, visibleAnalyticsMetrics, setAnalyticsMetric } =
    useQBarState();

const isAnalyticsRoute = computed(() =>
    String(page.url || '').startsWith('/admin/analytics'),
);
const isAnalyticsMode = computed(
    () => isAnalyticsRoute.value && analyticsQBarState.active,
);

const displayDomains = computed(() => {
    return recentDomains.value.length > 0
        ? recentDomains.value
        : availableDomains.value;
});

const headerTitle = computed(() => {
    if (isAnalyticsMode.value) {
        return analyticsQBarState.title || 'Analytics Focus';
    }

    return recentDomains.value.length > 0
        ? 'Recent domains'
        : 'All admin domains';
});

const headerCount = computed(() => {
    if (isAnalyticsMode.value) {
        return `${visibleAnalyticsMetrics.value.length} metric${visibleAnalyticsMetrics.value.length === 1 ? '' : 's'}`;
    }

    return `${displayDomains.value.length} tile${displayDomains.value.length === 1 ? '' : 's'}`;
});

function inferDomainFromUrl(url: string): string {
    if (url === '/admin' || url === '/admin/') {
        return 'dashboard';
    }

    if (url.startsWith('/admin/posts')) {
        return 'posts';
    }

    if (url.startsWith('/admin/categories')) {
        return 'categories';
    }

    if (url.startsWith('/admin/tags')) {
        return 'tags';
    }

    if (url.startsWith('/admin/media')) {
        return 'media';
    }

    if (url.startsWith('/admin/popups')) {
        return 'popups';
    }

    if (url.startsWith('/admin/analytics')) {
        return 'dashboard';
    }

    if (url.startsWith('/admin/coming-soon')) {
        return 'dashboard';
    }

    return 'dashboard';
}

async function fetchQueue() {
    const url = String(page.url || '');
    currentDomain.value = inferDomainFromUrl(url);
    availableDomains.value = fallbackDomains;

    if (isAnalyticsMode.value) {
        isLoading.value = false;

        return;
    }

    isLoading.value = true;

    try {
        const response = await fetch(
            `/admin/qbar?domain=${encodeURIComponent(currentDomain.value)}`,
            {
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            },
        );

        if (!response.ok) {
            throw new Error('Failed to load Q-Bar data');
        }

        const data = (await response.json()) as QBarPayload;

        recentDomains.value = Array.isArray(data.recentDomains)
            ? data.recentDomains
            : [];
        availableDomains.value =
            Array.isArray(data.availableDomains) &&
            data.availableDomains.length > 0
                ? data.availableDomains
                : fallbackDomains;
        currentDomain.value = data.currentDomain || currentDomain.value;
    } catch {
        recentDomains.value = [];
        availableDomains.value = fallbackDomains;
    } finally {
        isLoading.value = false;
    }
}

onMounted(fetchQueue);
watch(() => page.url, fetchQueue);
</script>
