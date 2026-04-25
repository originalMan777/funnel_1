<script setup lang="ts">
import { computed, onMounted, watch } from 'vue';
import AnalyticsFilterBar from '@/components/admin/analytics/AnalyticsFilterBar.vue';
import { useAnalyticsRecentlyViewed } from '@/composables/useAnalyticsRecentlyViewed';

const props = defineProps<{
    title: string;
    description: string;
    filters: {
        from: string;
        to: string;
        presets: FilterPreset[];
    };
    currentRoute: string;
    showHero?: boolean;
    showDateControls?: boolean;
}>();

const { recordItem } = useAnalyticsRecentlyViewed();

const tabs = computed(() => [
    { name: 'Overview', href: route('admin.analytics.index') },
    { name: 'Metrics Catalog', href: route('admin.analytics.metrics.index') },
    { name: 'Funnels', href: route('admin.analytics.funnels.index') },
    { name: 'Scenarios', href: route('admin.analytics.scenarios.index') },
    { name: 'Attribution', href: route('admin.analytics.attribution.index') },
    { name: 'Pages', href: route('admin.analytics.pages.index') },
    { name: 'CTAs', href: route('admin.analytics.ctas.index') },
    { name: 'Lead Boxes', href: route('admin.analytics.lead-boxes.index') },
    { name: 'Popups', href: route('admin.analytics.popups.index') },
    { name: 'Conversions', href: route('admin.analytics.conversions.index') },
]);

const historyHref = computed(
    () =>
        `${props.currentRoute}?from=${props.filters.from}&to=${props.filters.to}`,
);
const historyKey = computed(
    () => `${props.currentRoute}:${props.filters.from}:${props.filters.to}`,
);
const historySubtitle = computed(
    () => `${props.filters.from} to ${props.filters.to}`,
);

const recordVisit = () => {
    recordItem({
        key: historyKey.value,
        title: props.title,
        href: historyHref.value,
        subtitle: historySubtitle.value,
    });
};

onMounted(recordVisit);

watch(
    () => [
        props.title,
        props.currentRoute,
        props.filters.from,
        props.filters.to,
    ],
    () => {
        recordVisit();
    },
);
</script>

<template>
    <div class="space-y-4">
        <section
            v-if="showHero !== false"
            class="overflow-hidden rounded-[2rem] border border-slate-200 bg-gradient-to-br from-white via-slate-50 to-slate-100 p-6 shadow-sm shadow-slate-200/60"
        >
            <div
                class="flex flex-col gap-6 xl:flex-row xl:items-start xl:justify-between"
            >
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.22em] text-slate-500 uppercase"
                    >
                        Analytics
                    </p>
                    <h1
                        class="mt-3 text-3xl font-semibold tracking-tight text-slate-950 sm:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-slate-600">
                        {{ description }}
                    </p>
                    <p
                        class="mt-4 text-xs font-medium tracking-[0.18em] text-slate-500 uppercase"
                    >
                        Based on daily rollups and analytics-owned derived
                        summaries
                    </p>
                </div>
            </div>
        </section>

        <AnalyticsFilterBar
            :filters="filters"
            :current-route="currentRoute"
            :tabs="tabs"
            :show-date-controls="showDateControls"
        />
    </div>
</template>
