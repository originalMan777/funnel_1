<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { useAnalyticsRecentlyViewed } from '@/composables/useAnalyticsRecentlyViewed';

const props = defineProps<{
    currentHref: string;
}>();

const { items } = useAnalyticsRecentlyViewed();

const visibleItems = computed(() =>
    items.value.filter((item) => item.href !== props.currentHref).slice(0, 5),
);

const formatViewedAt = (value: string) => {
    try {
        return new Date(value).toLocaleDateString(undefined, {
            month: 'short',
            day: 'numeric',
        });
    } catch {
        return '';
    }
};
</script>

<template>
    <section
        class="rounded-[2rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p
                    class="text-xs font-semibold tracking-[0.18em] text-slate-500 uppercase"
                >
                    Recently Viewed
                </p>
                <h2
                    class="mt-1 text-xl font-semibold tracking-tight text-slate-950"
                >
                    Jump back into analytics work
                </h2>
            </div>
            <span
                class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-700"
            >
                {{ visibleItems.length }} saved
            </span>
        </div>

        <div v-if="visibleItems.length" class="mt-4 space-y-3">
            <Link
                v-for="item in visibleItems"
                :key="item.key"
                :href="item.href"
                class="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 transition hover:border-slate-300 hover:bg-white"
            >
                <div class="min-w-0">
                    <p class="text-sm font-semibold text-slate-900">
                        {{ item.title }}
                    </p>
                    <p
                        v-if="item.subtitle"
                        class="mt-1 truncate text-xs tracking-[0.18em] text-slate-500 uppercase"
                    >
                        {{ item.subtitle }}
                    </p>
                </div>
                <div class="shrink-0 text-right">
                    <p
                        class="text-[11px] font-semibold tracking-[0.18em] text-slate-500 uppercase"
                    >
                        Viewed
                    </p>
                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ formatViewedAt(item.viewedAt) }}
                    </p>
                </div>
            </Link>
        </div>

        <div
            v-else
            class="mt-4 rounded-[1.5rem] border border-dashed border-slate-300 bg-slate-50 p-4 text-sm leading-6 text-slate-500"
        >
            As you move through funnels, scenarios, attribution, and conversion
            reports, your last few analytics pages will appear here as
            shortcuts.
        </div>
    </section>
</template>
