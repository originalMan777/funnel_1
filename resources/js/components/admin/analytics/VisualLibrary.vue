<script setup lang="ts">
import { computed } from 'vue';

type VisualSize = 'sm' | 'md' | 'lg';

type MomentumData = {
    views?: number;
    clicks?: number;
    leads?: number;
};

type SourceQualityRow = {
    source: string;
    leads: number;
    quality: number;
};

const props = withDefaults(
    defineProps<{
        visual: string;
        data?: Record<string, unknown>;
        value?: number | string | null;
        size?: VisualSize;
    }>(),
    {
        data: () => ({}),
        value: null,
        size: 'md',
    },
);

const sizeClasses = computed(() => {
    const classes = {
        sm: {
            card: 'min-h-[20rem] p-4',
            body: 'mt-6 p-4',
            gauge: 'max-w-[14rem]',
            value: 'text-4xl',
            row: 'p-3',
        },
        md: {
            card: 'min-h-[26rem] p-5 sm:p-6',
            body: 'mt-9 p-5',
            gauge: 'max-w-[18rem]',
            value: 'text-5xl',
            row: 'p-4',
        },
        lg: {
            card: 'min-h-[30rem] p-6 sm:p-7',
            body: 'mt-10 p-6',
            gauge: 'max-w-[20rem]',
            value: 'text-6xl',
            row: 'p-5',
        },
    };

    return classes[props.size];
});

const strengthValue = computed(() => {
    const numericValue =
        typeof props.value === 'number'
            ? props.value
            : typeof props.value === 'string'
              ? Number.parseFloat(props.value)
              : 72;

    if (!Number.isFinite(numericValue)) {
        return 72;
    }

    return Math.min(Math.max(numericValue, 0), 100);
});

const strengthLabel = computed(() => {
    if (strengthValue.value < 40) {
        return 'Weak signal';
    }

    if (strengthValue.value < 70) {
        return 'Moderate signal';
    }

    return 'Strong signal';
});

const momentumData = computed<MomentumData>(() => ({
    views: typeof props.data.views === 'number' ? props.data.views : 1240,
    clicks: typeof props.data.clicks === 'number' ? props.data.clicks : 318,
    leads: typeof props.data.leads === 'number' ? props.data.leads : 74,
}));

const momentumMax = computed(() =>
    Math.max(momentumData.value.views ?? 0, momentumData.value.clicks ?? 0, momentumData.value.leads ?? 0, 1),
);

const momentumWidth = (value = 0) => `${Math.max(Math.min((value / momentumMax.value) * 100, 100), 4)}%`;

const formatNumber = (value = 0) => new Intl.NumberFormat().format(value);

const sourceRows = computed<SourceQualityRow[]>(() => {
    if (Array.isArray(props.data.sources)) {
        return props.data.sources
            .filter((row): row is SourceQualityRow => {
                if (!row || typeof row !== 'object') {
                    return false;
                }

                const source = 'source' in row ? row.source : null;
                const leads = 'leads' in row ? row.leads : null;
                const quality = 'quality' in row ? row.quality : null;

                return typeof source === 'string' && typeof leads === 'number' && typeof quality === 'number';
            })
            .slice(0, 6);
    }

    return [
        { source: 'Organic', leads: 34, quality: 91 },
        { source: 'LinkedIn', leads: 18, quality: 84 },
        { source: 'Referral', leads: 14, quality: 78 },
        { source: 'Paid Search', leads: 8, quality: 62 },
    ];
});
</script>

<template>
    <article
        v-if="visual === 'premium-strength-meter'"
        class="bg-slate-950"
        :class="sizeClasses.card"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500">
                    Strength Index
                </p>
                <h3 class="mt-2 text-base font-semibold tracking-tight text-white">
                    Premium Strength Meter
                </h3>
            </div>
            <span class="rounded-md border border-emerald-400/30 bg-emerald-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-emerald-200">
                {{ Math.round(strengthValue) }}%
            </span>
        </div>

        <div
            class="rounded-xl border border-white/10 bg-slate-900/80 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"
            :class="sizeClasses.body"
        >
            <div class="relative mx-auto" :class="sizeClasses.gauge">
                <svg viewBox="0 0 220 144" class="h-auto w-full overflow-visible">
                    <path
                        d="M 26 112 A 84 84 0 0 1 194 112"
                        fill="none"
                        pathLength="100"
                        stroke="currentColor"
                        stroke-linecap="round"
                        stroke-width="18"
                        class="text-slate-800"
                    />
                    <path
                        d="M 26 112 A 84 84 0 0 1 194 112"
                        fill="none"
                        pathLength="100"
                        stroke="currentColor"
                        :stroke-dasharray="`${strengthValue} 100`"
                        stroke-linecap="round"
                        stroke-width="18"
                        class="text-cyan-300 drop-shadow-[0_0_14px_rgba(103,232,249,0.45)]"
                    />
                    <path
                        d="M 26 112 A 84 84 0 0 1 194 112"
                        fill="none"
                        pathLength="100"
                        stroke="currentColor"
                        stroke-dasharray="1 11.5"
                        stroke-linecap="round"
                        stroke-width="4"
                        class="text-white/35"
                    />
                </svg>

                <div class="absolute inset-x-0 bottom-2 text-center">
                    <p class="font-semibold tracking-tight text-white" :class="sizeClasses.value">
                        {{ Math.round(strengthValue) }}<span class="text-2xl text-slate-400">%</span>
                    </p>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">
                        {{ strengthLabel }}
                    </p>
                </div>
            </div>

            <div class="mt-7 grid grid-cols-3 gap-2 text-center">
                <div class="rounded-lg border border-rose-300/15 bg-rose-300/5 px-2 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-rose-200">
                        Weak
                    </p>
                </div>
                <div class="rounded-lg border border-amber-300/15 bg-amber-300/5 px-2 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-amber-200">
                        Moderate
                    </p>
                </div>
                <div class="rounded-lg border border-emerald-300/25 bg-emerald-300/10 px-2 py-3">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-200">
                        Strong
                    </p>
                </div>
            </div>
        </div>
    </article>

    <article
        v-else-if="visual === 'conversion-momentum-bar'"
        class="bg-slate-950"
        :class="sizeClasses.card"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500">
                    Funnel Motion
                </p>
                <h3 class="mt-2 text-base font-semibold tracking-tight text-white">
                    Conversion Momentum Bar
                </h3>
            </div>
            <span class="rounded-md border border-cyan-400/30 bg-cyan-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-cyan-200">
                Mock
            </span>
        </div>

        <div
            class="rounded-xl border border-white/10 bg-slate-900/80 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"
            :class="sizeClasses.body"
        >
            <div class="space-y-5">
                <div>
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        <span>Views</span>
                        <span class="text-white">{{ formatNumber(momentumData.views) }}</span>
                    </div>
                    <div class="mt-2 h-4 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="h-full rounded-full bg-cyan-300 shadow-[0_0_20px_rgba(103,232,249,0.28)]"
                            :style="{ width: momentumWidth(momentumData.views) }"
                        ></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        <span>Clicks</span>
                        <span class="text-white">{{ formatNumber(momentumData.clicks) }}</span>
                    </div>
                    <div class="mt-2 h-4 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="h-full rounded-full bg-indigo-300 shadow-[0_0_20px_rgba(165,180,252,0.28)]"
                            :style="{ width: momentumWidth(momentumData.clicks) }"
                        ></div>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-[0.18em] text-slate-400">
                        <span>Leads</span>
                        <span class="text-white">{{ formatNumber(momentumData.leads) }}</span>
                    </div>
                    <div class="mt-2 h-4 overflow-hidden rounded-full bg-slate-800">
                        <div
                            class="h-full rounded-full bg-emerald-300 shadow-[0_0_20px_rgba(110,231,183,0.28)]"
                            :style="{ width: momentumWidth(momentumData.leads) }"
                        ></div>
                    </div>
                </div>
            </div>

            <div class="mt-8 rounded-lg border border-white/10 bg-slate-950/80 p-4">
                <div class="flex items-center gap-2">
                    <div class="h-2 flex-1 rounded-full bg-cyan-300"></div>
                    <div class="h-px w-6 bg-slate-600"></div>
                    <div class="h-2 flex-[0.62] rounded-full bg-indigo-300"></div>
                    <div class="h-px w-6 bg-slate-600"></div>
                    <div class="h-2 flex-[0.31] rounded-full bg-emerald-300"></div>
                </div>
                <div class="mt-4 grid grid-cols-3 gap-2 text-center">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-cyan-200">
                        Views
                    </p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-indigo-200">
                        Clicks
                    </p>
                    <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-emerald-200">
                        Leads
                    </p>
                </div>
            </div>
        </div>
    </article>

    <article
        v-else-if="visual === 'source-quality-stack'"
        class="bg-slate-950"
        :class="sizeClasses.card"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-[0.24em] text-slate-500">
                    Ranked Intake
                </p>
                <h3 class="mt-2 text-base font-semibold tracking-tight text-white">
                    Source Quality Stack
                </h3>
            </div>
            <span class="rounded-md border border-violet-400/30 bg-violet-400/10 px-2.5 py-1 text-[10px] font-bold uppercase tracking-[0.18em] text-violet-200">
                Ranked
            </span>
        </div>

        <div
            class="space-y-3 rounded-xl border border-white/10 bg-slate-900/80 p-4 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"
            :class="sizeClasses.body"
        >
            <div
                v-for="(row, index) in sourceRows"
                :key="row.source"
                class="rounded-lg border border-white/10 bg-slate-950/80"
                :class="sizeClasses.row"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-7 w-7 items-center justify-center rounded-md border border-white/10 bg-white/5 text-xs font-bold text-slate-300">
                            {{ index + 1 }}
                        </span>
                        <div>
                            <p class="text-sm font-semibold text-white">
                                {{ row.source }}
                            </p>
                            <p class="mt-1 text-xs font-medium text-slate-500">
                                {{ row.leads }} leads
                            </p>
                        </div>
                    </div>
                    <p class="text-sm font-bold text-cyan-200">
                        {{ row.quality }}
                    </p>
                </div>
                <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-800">
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-cyan-300 via-emerald-300 to-lime-200"
                        :style="{ width: `${Math.max(Math.min(row.quality, 100), 0)}%` }"
                    ></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                    <span>Lead count</span>
                    <span>Quality score</span>
                </div>
            </div>
        </div>
    </article>

    <div
        v-else
        class="flex min-h-[12rem] items-center justify-center rounded-xl border border-dashed border-slate-700 bg-slate-950 p-6 text-center text-sm font-semibold text-slate-500"
    >
        Unknown accepted visual: {{ visual }}
    </div>
</template>
