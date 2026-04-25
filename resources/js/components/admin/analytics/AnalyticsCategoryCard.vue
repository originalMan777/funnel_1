<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        title: string;
        description: string;
        href: string;
        statLabel?: string | null;
        statValue?: string | null;
        stats?: Array<{ label: string; value: string }>;
        tone?: 'neutral' | 'sky' | 'emerald' | 'amber';
        variant?: 'compact' | 'preview';
        eyebrow?: string;
        actionLabel?: string;
    }>(),
    {
        statLabel: null,
        statValue: null,
        stats: () => [],
        tone: 'neutral',
        variant: 'preview',
        eyebrow: 'Category',
        actionLabel: 'Open report',
    },
);

const toneClasses = computed(() => {
    switch (props.tone) {
        case 'sky':
            return props.variant === 'compact'
                ? 'border-sky-200/80 bg-sky-50/70 hover:border-sky-300'
                : 'border-sky-200 bg-sky-50/80 hover:border-sky-300';
        case 'emerald':
            return props.variant === 'compact'
                ? 'border-emerald-200/80 bg-emerald-50/70 hover:border-emerald-300'
                : 'border-emerald-200 bg-emerald-50/80 hover:border-emerald-300';
        case 'amber':
            return props.variant === 'compact'
                ? 'border-amber-200/80 bg-amber-50/80 hover:border-amber-300'
                : 'border-amber-200 bg-amber-50/90 hover:border-amber-300';
        default:
            return props.variant === 'compact'
                ? 'border-slate-200/90 bg-white/90 hover:border-slate-300'
                : 'border-slate-200 bg-white hover:border-slate-300';
    }
});
</script>

<template>
    <Link
        :href="href"
        class="group flex h-full flex-col justify-between rounded-[1.5rem] border transition hover:-translate-y-0.5 hover:shadow-lg"
        :class="[
            variant === 'compact' ? 'p-4 shadow-sm shadow-slate-200/40' : 'p-5',
            toneClasses,
        ]"
    >
        <div>
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p
                        class="text-xs font-semibold tracking-[0.2em] text-slate-500 uppercase"
                    >
                        {{ eyebrow }}
                    </p>
                    <h3
                        class="mt-2 font-semibold text-slate-900"
                        :class="variant === 'compact' ? 'text-base' : 'text-lg'"
                    >
                        {{ title }}
                    </h3>
                </div>
                <span
                    v-if="variant === 'compact'"
                    class="rounded-full bg-white/80 px-2.5 py-1 text-[11px] font-medium tracking-[0.16em] text-slate-600 uppercase"
                >
                    View
                </span>
            </div>
            <p class="mt-2 text-sm leading-6 text-slate-600">
                {{ description }}
            </p>
        </div>

        <div class="mt-5 space-y-3">
            <slot />
            <div
                v-if="statLabel && statValue"
                class="rounded-2xl bg-white/80 px-4 py-3"
            >
                <p class="text-xs tracking-[0.18em] text-slate-500 uppercase">
                    {{ statLabel }}
                </p>
                <p
                    class="mt-1 font-semibold text-slate-900"
                    :class="variant === 'compact' ? 'text-lg' : 'text-xl'"
                >
                    {{ statValue }}
                </p>
            </div>
            <div v-if="stats.length" class="grid gap-2 sm:grid-cols-2">
                <div
                    v-for="item in stats"
                    :key="`${title}-${item.label}`"
                    class="rounded-2xl bg-white/80 px-4 py-3"
                >
                    <p
                        class="text-[11px] tracking-[0.18em] text-slate-500 uppercase"
                    >
                        {{ item.label }}
                    </p>
                    <p class="mt-1 text-sm font-semibold text-slate-900">
                        {{ item.value }}
                    </p>
                </div>
            </div>
            <p
                class="text-sm font-medium text-slate-700 transition group-hover:text-slate-900"
            >
                {{ actionLabel }}
            </p>
        </div>
    </Link>
</template>
