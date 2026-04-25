<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type LabCard = {
    key: string;
    label: string;
    eyebrow: string;
    status: string;
    value: string;
};

type TableRow = {
    name: string;
    value: string;
    status: string;
    tone: string;
    width: string;
    delta?: string;
};

const cards: LabCard[] = [
    { key: 'premium-donut', label: 'Premium donut prototype', eyebrow: 'depth study', status: 'specular ring', value: '74% completion' },
    { key: 'glow-donut', label: 'Glow donut prototype', eyebrow: 'luminous ring', status: 'ambient glow', value: '128 active sources' },
    { key: 'gauge', label: 'Gauge prototype', eyebrow: 'threshold dial', status: 'signal stable', value: 'Health 82' },
    { key: 'signal-wave', label: 'Signal wave prototype', eyebrow: 'motion trace', status: 'pulse live', value: 'Wave offset +14' },
    { key: 'funnel-flow', label: 'Funnel flow prototype', eyebrow: 'conversion path', status: 'drop-off mapped', value: '812 converted' },
    { key: 'orbital', label: 'Orbital attribution prototype', eyebrow: 'source gravity', status: 'orbit synced', value: '6 source nodes' },
    { key: 'layered-pie', label: 'Layered Pie Prototype', eyebrow: 'pie depth', status: 'stacked slices', value: '4 weighted segments' },
    { key: 'deep-shadow-pie', label: 'Deep Shadow Pie Prototype', eyebrow: 'core shadow', status: 'contrast heavy', value: '61% retained' },
    { key: 'exploded-pie', label: 'Exploded Pie Prototype', eyebrow: 'slice separation', status: 'focus split', value: 'Top slice 42%' },
    { key: 'radial-progress-stack', label: 'Radial Progress Stack', eyebrow: 'multi ring', status: 'rings aligned', value: '3 layer stack' },
    { key: 'half-ring-score', label: 'Half-Ring Score Gauge', eyebrow: 'score arc', status: 'target ahead', value: 'Score 88' },
    { key: 'premium-vertical-bar', label: 'Premium Vertical Bar Chart', eyebrow: 'column field', status: 'gloss active', value: 'Peak 9.8k' },
    { key: 'glass-bar', label: 'Glass Bar Chart', eyebrow: 'glass columns', status: 'light bleed', value: '5 transparent bars' },
    { key: 'stacked-bar', label: 'Stacked Bar Performance Chart', eyebrow: 'performance mix', status: 'layered totals', value: '3 stacked series' },
    { key: 'horizontal-progress', label: 'Horizontal Progress Bars', eyebrow: 'channel spread', status: 'progress lanes', value: '91% top lane' },
    { key: 'floating-column', label: 'Floating Column Chart', eyebrow: 'range lift', status: 'variance shown', value: 'Range 22-84' },
    { key: 'shadow-depth-bar', label: 'Shadow Depth Bar Chart', eyebrow: 'depth bars', status: 'shadow offset', value: 'Depth index 6' },
    { key: 'premium-data-table', label: 'Premium Data Table', eyebrow: 'table study', status: 'table polished', value: '6 premium rows' },
    { key: 'kpi-comparison-table', label: 'KPI Comparison Table', eyebrow: 'metric matchup', status: 'delta highlighted', value: '4 KPI pairs' },
    { key: 'source-ranking-table', label: 'Source Ranking Table', eyebrow: 'source ladder', status: 'ranked source mix', value: 'Top 5 sources' },
    { key: 'pie-table-hybrid', label: 'Pie Table Hybrid', eyebrow: 'share + ranking', status: 'hybrid active', value: 'Pie with ranked rows' },
    { key: 'bar-table-hybrid', label: 'Bar Table Hybrid', eyebrow: 'bars + rows', status: 'compact track', value: '4 compact lanes' },
    { key: 'mini-report-card-table', label: 'Mini Report Card Table', eyebrow: 'report strip', status: 'snapshot stacked', value: '4 report cards' },
];

const premiumTableRows: TableRow[] = [
    { name: 'North Ridge Campaign', value: '8,420', status: 'Strong', tone: 'bg-cyan-400', width: '84%', delta: '+12%' },
    { name: 'Seller Guide Entry', value: '6,180', status: 'Stable', tone: 'bg-emerald-400', width: '68%', delta: '+4%' },
    { name: 'Valuation CTA Cluster', value: '4,950', status: 'Watch', tone: 'bg-amber-400', width: '54%', delta: '-2%' },
    { name: 'Retargeting Drift', value: '3,320', status: 'Low', tone: 'bg-fuchsia-400', width: '36%', delta: '-9%' },
];

const comparisonRows: TableRow[] = [
    { name: 'Qualified Leads', value: '1,284', status: 'Ahead', tone: 'bg-cyan-400', width: '82%', delta: '+18%' },
    { name: 'Booked Consults', value: '412', status: 'Ahead', tone: 'bg-emerald-400', width: '64%', delta: '+11%' },
    { name: 'Offer Requests', value: '96', status: 'Flat', tone: 'bg-amber-400', width: '34%', delta: '+1%' },
    { name: 'Dormant Sessions', value: '228', status: 'Risk', tone: 'bg-rose-400', width: '48%', delta: '+7%' },
];

const sourceRows: TableRow[] = [
    { name: 'Organic Search', value: '38%', status: 'Prime', tone: 'bg-cyan-400', width: '86%', delta: '+6%' },
    { name: 'Direct Return', value: '24%', status: 'Steady', tone: 'bg-indigo-400', width: '58%', delta: '+2%' },
    { name: 'Referral Network', value: '18%', status: 'Growing', tone: 'bg-violet-400', width: '44%', delta: '+9%' },
    { name: 'Paid Prospecting', value: '13%', status: 'Watch', tone: 'bg-amber-400', width: '32%', delta: '-3%' },
    { name: 'Social Touches', value: '7%', status: 'Light', tone: 'bg-emerald-400', width: '18%', delta: '+1%' },
];

const pieHybridRows: TableRow[] = [
    { name: 'Consultation', value: '42%', status: 'Primary', tone: 'bg-cyan-400', width: '76%' },
    { name: 'Guide Download', value: '27%', status: 'Active', tone: 'bg-violet-400', width: '52%' },
    { name: 'Home Valuation', value: '18%', status: 'Stable', tone: 'bg-emerald-400', width: '36%' },
    { name: 'Callback Request', value: '13%', status: 'Emerging', tone: 'bg-amber-400', width: '26%' },
];

const barHybridRows: TableRow[] = [
    { name: 'Landing Page A', value: '9.4k', status: 'Lead', tone: 'bg-cyan-400', width: '88%' },
    { name: 'Landing Page B', value: '7.8k', status: 'Strong', tone: 'bg-indigo-400', width: '72%' },
    { name: 'Landing Page C', value: '5.3k', status: 'Mid', tone: 'bg-violet-400', width: '49%' },
    { name: 'Landing Page D', value: '3.1k', status: 'Low', tone: 'bg-amber-400', width: '29%' },
];

const reportCards = [
    { title: 'Traffic Pulse', value: '18.2k', detail: 'Sessions this cycle', tone: 'from-cyan-400/35 to-sky-500/10' },
    { title: 'Intent Lift', value: '31%', detail: 'Signal acceleration', tone: 'from-violet-400/35 to-fuchsia-500/10' },
    { title: 'Lead Yield', value: '412', detail: 'Premium prospects', tone: 'from-emerald-400/35 to-teal-500/10' },
    { title: 'Time Drag', value: '42m', detail: 'Median delay band', tone: 'from-amber-400/30 to-orange-500/10' },
];

const statusClass = (status: string) => {
    if (status === 'Strong' || status === 'Ahead' || status === 'Prime' || status === 'Primary' || status === 'Lead') {
        return 'bg-cyan-400/12 text-cyan-200 ring-cyan-300/20';
    }

    if (status === 'Stable' || status === 'Steady' || status === 'Active' || status === 'Strong') {
        return 'bg-emerald-400/12 text-emerald-200 ring-emerald-300/20';
    }

    if (status === 'Watch' || status === 'Flat' || status === 'Emerging' || status === 'Mid') {
        return 'bg-amber-400/12 text-amber-200 ring-amber-300/20';
    }

    return 'bg-fuchsia-400/12 text-fuchsia-200 ring-fuchsia-300/20';
};
</script>

<template>
    <Head title="Graphics Lab" />

    <AdminLayout>
        <div class="min-h-screen bg-[#f5efe2] px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section
                    class="overflow-hidden rounded-[2rem] border border-stone-300/70 bg-[linear-gradient(135deg,rgba(255,251,245,0.98),rgba(245,239,226,0.94)_60%,rgba(237,230,214,0.96))] px-6 py-8 shadow-[0_20px_50px_-30px_rgba(68,56,42,0.35)] sm:px-8"
                >
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-500">
                        Analytics Sandbox
                    </p>
                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">
                        Graphics Lab
                    </h1>
                    <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-600 sm:text-base">
                        Experimental analytics visual system
                    </p>
                </section>

                <section
                    class="overflow-hidden rounded-[2rem] border border-slate-800/70 bg-[radial-gradient(circle_at_top,rgba(34,211,238,0.18),transparent_28%),radial-gradient(circle_at_80%_20%,rgba(168,85,247,0.16),transparent_22%),linear-gradient(180deg,#07111f_0%,#091524_45%,#050b14_100%)] p-5 shadow-[0_30px_80px_-36px_rgba(2,8,23,0.9)] sm:p-6 lg:p-8"
                >
                    <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                        <article
                            v-for="(card, index) in cards"
                            :key="card.key"
                            class="group relative flex min-h-[25.5rem] flex-col overflow-hidden rounded-[1.75rem] border border-white/10 bg-white/[0.03] p-5 shadow-[inset_0_1px_0_rgba(255,255,255,0.08),0_18px_40px_-28px_rgba(0,0,0,0.95)] backdrop-blur-sm"
                        >
                            <div class="pointer-events-none absolute inset-x-6 top-0 h-20 bg-gradient-to-b from-white/10 to-transparent opacity-70"></div>
                            <div class="pointer-events-none absolute inset-0 rounded-[1.75rem] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.08),transparent_32%)]"></div>

                            <div class="relative z-10 flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-semibold uppercase tracking-[0.26em] text-slate-400">
                                        {{ card.eyebrow }}
                                    </p>
                                    <h2 class="mt-2 text-base font-semibold tracking-tight text-white">
                                        {{ card.label }}
                                    </h2>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span
                                        class="rounded-full border border-cyan-300/18 bg-cyan-300/8 px-2.5 py-1 text-[10px] font-semibold tracking-[0.2em] text-cyan-100"
                                    >
                                        {{ String(index + 1).padStart(2, '0') }}
                                    </span>
                                    <span
                                        class="rounded-full border border-white/10 bg-white/5 px-2.5 py-1 text-[10px] font-medium uppercase tracking-[0.18em] text-slate-300"
                                    >
                                        test
                                    </span>
                                </div>
                            </div>

                            <div class="relative z-10 mt-5 flex flex-1 items-center justify-center">
                                <div
                                    v-if="card.key === 'premium-donut'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-44 w-44 rounded-full bg-cyan-400/10 blur-3xl"></div>
                                    <svg viewBox="0 0 240 240" class="h-56 w-56 drop-shadow-[0_18px_30px_rgba(0,0,0,0.55)]">
                                        <defs>
                                            <linearGradient id="premium-base" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#22d3ee" />
                                                <stop offset="52%" stop-color="#60a5fa" />
                                                <stop offset="100%" stop-color="#6366f1" />
                                            </linearGradient>
                                            <linearGradient id="premium-top" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#d8fbff" />
                                                <stop offset="100%" stop-color="#67e8f9" />
                                            </linearGradient>
                                            <filter id="premium-shadow" x="-50%" y="-50%" width="200%" height="200%">
                                                <feDropShadow dx="0" dy="20" stdDeviation="16" flood-color="#020617" flood-opacity="0.6" />
                                            </filter>
                                        </defs>
                                        <g filter="url(#premium-shadow)">
                                            <circle cx="120" cy="124" r="70" fill="none" stroke="rgba(15,23,42,0.95)" stroke-width="34" />
                                            <circle
                                                cx="120"
                                                cy="120"
                                                r="70"
                                                fill="none"
                                                stroke="url(#premium-base)"
                                                stroke-width="34"
                                                stroke-linecap="round"
                                                stroke-dasharray="300 160"
                                                transform="rotate(-90 120 120)"
                                            />
                                            <circle
                                                cx="120"
                                                cy="112"
                                                r="70"
                                                fill="none"
                                                stroke="url(#premium-top)"
                                                stroke-opacity="0.55"
                                                stroke-width="10"
                                                stroke-linecap="round"
                                                stroke-dasharray="200 260"
                                                transform="rotate(-90 120 120)"
                                            />
                                        </g>
                                        <circle cx="120" cy="120" r="42" fill="#08111d" />
                                        <ellipse cx="120" cy="102" rx="30" ry="10" fill="rgba(255,255,255,0.16)" />
                                        <text x="120" y="122" text-anchor="middle" class="fill-white text-[26px] font-semibold">
                                            74%
                                        </text>
                                        <text x="120" y="144" text-anchor="middle" class="fill-slate-400 text-[11px] uppercase tracking-[0.32em]">
                                            depth
                                        </text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'glow-donut'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-48 w-48 rounded-full bg-fuchsia-500/20 blur-3xl"></div>
                                    <div class="absolute h-40 w-40 rounded-full bg-cyan-400/10 blur-2xl"></div>
                                    <svg viewBox="0 0 240 240" class="h-56 w-56">
                                        <defs>
                                            <linearGradient id="glow-ring" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#f472b6" />
                                                <stop offset="50%" stop-color="#a855f7" />
                                                <stop offset="100%" stop-color="#22d3ee" />
                                            </linearGradient>
                                            <filter id="glow-soft" x="-80%" y="-80%" width="260%" height="260%">
                                                <feGaussianBlur stdDeviation="7" result="blur" />
                                                <feMerge>
                                                    <feMergeNode in="blur" />
                                                    <feMergeNode in="SourceGraphic" />
                                                </feMerge>
                                            </filter>
                                        </defs>
                                        <circle cx="120" cy="120" r="68" fill="none" stroke="rgba(148,163,184,0.16)" stroke-width="22" />
                                        <circle
                                            cx="120"
                                            cy="120"
                                            r="68"
                                            fill="none"
                                            stroke="url(#glow-ring)"
                                            stroke-width="22"
                                            stroke-linecap="round"
                                            stroke-dasharray="290 140"
                                            transform="rotate(-82 120 120)"
                                            filter="url(#glow-soft)"
                                        />
                                        <circle
                                            cx="120"
                                            cy="120"
                                            r="50"
                                            fill="#060d18"
                                            stroke="rgba(255,255,255,0.08)"
                                        />
                                        <text x="120" y="118" text-anchor="middle" class="fill-white text-[28px] font-semibold">
                                            128
                                        </text>
                                        <text x="120" y="140" text-anchor="middle" class="fill-slate-400 text-[11px] uppercase tracking-[0.24em]">
                                            active sources
                                        </text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'gauge'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <svg viewBox="0 0 280 220" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <linearGradient id="gauge-arc" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#f59e0b" />
                                                <stop offset="50%" stop-color="#22d3ee" />
                                                <stop offset="100%" stop-color="#34d399" />
                                            </linearGradient>
                                            <filter id="gauge-glow" x="-50%" y="-50%" width="200%" height="200%">
                                                <feDropShadow dx="0" dy="0" stdDeviation="8" flood-color="#22d3ee" flood-opacity="0.35" />
                                            </filter>
                                        </defs>
                                        <path
                                            d="M 40 170 A 100 100 0 0 1 240 170"
                                            fill="none"
                                            stroke="rgba(148,163,184,0.16)"
                                            stroke-width="20"
                                            stroke-linecap="round"
                                        />
                                        <path
                                            d="M 40 170 A 100 100 0 0 1 210 86"
                                            fill="none"
                                            stroke="url(#gauge-arc)"
                                            stroke-width="20"
                                            stroke-linecap="round"
                                            filter="url(#gauge-glow)"
                                        />
                                        <g transform="translate(140 170)">
                                            <line
                                                x1="0"
                                                y1="0"
                                                x2="62"
                                                y2="-74"
                                                stroke="#e2e8f0"
                                                stroke-width="5"
                                                stroke-linecap="round"
                                            />
                                            <circle r="12" fill="#e2e8f0" />
                                            <circle r="5" fill="#0f172a" />
                                        </g>
                                        <text x="140" y="118" text-anchor="middle" class="fill-slate-300 text-[12px] uppercase tracking-[0.34em]">
                                            health index
                                        </text>
                                        <text x="140" y="150" text-anchor="middle" class="fill-white text-[40px] font-semibold">
                                            82
                                        </text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'signal-wave'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute inset-x-4 top-1/2 h-px -translate-y-1/2 bg-cyan-300/10"></div>
                                    <svg viewBox="0 0 320 180" class="h-48 w-full">
                                        <defs>
                                            <linearGradient id="wave-stroke" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#0ea5e9" />
                                                <stop offset="50%" stop-color="#22d3ee" />
                                                <stop offset="100%" stop-color="#a855f7" />
                                            </linearGradient>
                                            <filter id="wave-glow" x="-20%" y="-80%" width="140%" height="260%">
                                                <feDropShadow dx="0" dy="0" stdDeviation="7" flood-color="#22d3ee" flood-opacity="0.45" />
                                            </filter>
                                        </defs>
                                        <path
                                            d="M 10 110 C 40 110, 45 48, 74 48 S 110 134, 144 134 S 182 62, 216 62 S 252 128, 286 128 S 300 92, 310 92"
                                            fill="none"
                                            stroke="url(#wave-stroke)"
                                            stroke-width="6"
                                            stroke-linecap="round"
                                            filter="url(#wave-glow)"
                                        />
                                        <path
                                            d="M 10 124 C 44 124, 58 86, 82 86 S 112 146, 146 146 S 176 96, 210 96 S 250 142, 286 142 S 301 118, 310 118"
                                            fill="none"
                                            stroke="rgba(148,163,184,0.32)"
                                            stroke-width="2"
                                            stroke-dasharray="3 7"
                                        />
                                        <circle cx="74" cy="48" r="6" fill="#67e8f9" />
                                        <circle cx="216" cy="62" r="6" fill="#c084fc" />
                                        <circle cx="286" cy="128" r="6" fill="#38bdf8" />
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'funnel-flow'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <svg viewBox="0 0 320 220" class="h-52 w-full max-w-[19rem]">
                                        <defs>
                                            <linearGradient id="funnel-fill-1" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#1d4ed8" stop-opacity="0.95" />
                                                <stop offset="100%" stop-color="#38bdf8" stop-opacity="0.78" />
                                            </linearGradient>
                                            <linearGradient id="funnel-fill-2" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#0f766e" stop-opacity="0.92" />
                                                <stop offset="100%" stop-color="#2dd4bf" stop-opacity="0.75" />
                                            </linearGradient>
                                            <linearGradient id="funnel-fill-3" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#7c3aed" stop-opacity="0.92" />
                                                <stop offset="100%" stop-color="#c084fc" stop-opacity="0.76" />
                                            </linearGradient>
                                        </defs>
                                        <polygon points="34,40 286,40 246,88 74,88" fill="url(#funnel-fill-1)" stroke="rgba(255,255,255,0.16)" />
                                        <polygon points="74,98 246,98 214,138 106,138" fill="url(#funnel-fill-2)" stroke="rgba(255,255,255,0.16)" />
                                        <polygon points="106,148 214,148 186,188 134,188" fill="url(#funnel-fill-3)" stroke="rgba(255,255,255,0.16)" />
                                        <text x="160" y="68" text-anchor="middle" class="fill-white text-[14px] font-medium">12.4k reach</text>
                                        <text x="160" y="124" text-anchor="middle" class="fill-white text-[14px] font-medium">4.8k engaged</text>
                                        <text x="160" y="174" text-anchor="middle" class="fill-white text-[14px] font-medium">812 converted</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'orbital'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-44 w-44 rounded-full border border-cyan-300/10"></div>
                                    <div class="absolute h-32 w-32 rounded-full border border-fuchsia-300/10"></div>
                                    <svg viewBox="0 0 280 220" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <radialGradient id="orb-core" cx="50%" cy="45%" r="60%">
                                                <stop offset="0%" stop-color="#67e8f9" />
                                                <stop offset="55%" stop-color="#2563eb" />
                                                <stop offset="100%" stop-color="#0f172a" />
                                            </radialGradient>
                                            <filter id="orb-glow" x="-100%" y="-100%" width="300%" height="300%">
                                                <feDropShadow dx="0" dy="0" stdDeviation="12" flood-color="#60a5fa" flood-opacity="0.4" />
                                            </filter>
                                        </defs>
                                        <ellipse cx="140" cy="110" rx="92" ry="44" fill="none" stroke="rgba(96,165,250,0.35)" stroke-width="2.5" />
                                        <ellipse cx="140" cy="110" rx="56" ry="88" fill="none" stroke="rgba(192,132,252,0.28)" stroke-width="2.5" transform="rotate(-26 140 110)" />
                                        <ellipse cx="140" cy="110" rx="78" ry="28" fill="none" stroke="rgba(45,212,191,0.26)" stroke-width="2.5" transform="rotate(22 140 110)" />
                                        <circle cx="140" cy="110" r="28" fill="url(#orb-core)" filter="url(#orb-glow)" />
                                        <circle cx="224" cy="110" r="6" fill="#7dd3fc" />
                                        <circle cx="102" cy="38" r="5" fill="#c084fc" />
                                        <circle cx="78" cy="150" r="5" fill="#2dd4bf" />
                                        <circle cx="166" cy="184" r="4.5" fill="#f9a8d4" />
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'layered-pie'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-44 w-44 rounded-full bg-cyan-400/12 blur-3xl"></div>
                                    <div class="absolute h-36 w-36 rounded-full bg-violet-500/10 blur-2xl"></div>
                                    <svg viewBox="0 0 240 240" class="h-56 w-56 drop-shadow-[0_18px_34px_rgba(0,0,0,0.58)]">
                                        <defs>
                                            <linearGradient id="clean-donut-a" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#67e8f9" />
                                                <stop offset="100%" stop-color="#2563eb" />
                                            </linearGradient>
                                            <linearGradient id="clean-donut-b" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#c084fc" />
                                                <stop offset="100%" stop-color="#7c3aed" />
                                            </linearGradient>
                                            <linearGradient id="clean-donut-c" x1="0%" y1="0%" x2="100%" y2="100%">
                                                <stop offset="0%" stop-color="#2dd4bf" />
                                                <stop offset="100%" stop-color="#0f766e" />
                                            </linearGradient>
                                            <filter id="clean-donut-shadow" x="-50%" y="-50%" width="200%" height="200%">
                                                <feDropShadow dx="0" dy="18" stdDeviation="14" flood-color="#020617" flood-opacity="0.56" />
                                            </filter>
                                        </defs>
                                        <g filter="url(#clean-donut-shadow)">
                                            <circle cx="120" cy="120" r="72" fill="none" stroke="rgba(148,163,184,0.12)" stroke-width="28" />
                                            <circle cx="120" cy="120" r="72" fill="none" stroke="url(#clean-donut-a)" stroke-width="28" stroke-linecap="round" stroke-dasharray="198 255" transform="rotate(-90 120 120)" />
                                            <circle cx="120" cy="120" r="72" fill="none" stroke="url(#clean-donut-b)" stroke-width="28" stroke-linecap="round" stroke-dasharray="128 325" stroke-dashoffset="-210" transform="rotate(-90 120 120)" />
                                            <circle cx="120" cy="120" r="72" fill="none" stroke="url(#clean-donut-c)" stroke-width="28" stroke-linecap="round" stroke-dasharray="92 361" stroke-dashoffset="-350" transform="rotate(-90 120 120)" />
                                        </g>
                                        <circle cx="120" cy="120" r="48" fill="#07111f" />
                                        <ellipse cx="120" cy="101" rx="28" ry="9" fill="rgba(255,255,255,0.12)" />
                                        <text x="120" y="116" text-anchor="middle" class="fill-slate-300 text-[10px] uppercase tracking-[0.3em]">primary</text>
                                        <text x="120" y="143" text-anchor="middle" class="fill-white text-[24px] font-semibold">44%</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'deep-shadow-pie'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-48 w-48 rounded-full bg-indigo-500/10 blur-3xl"></div>
                                    <svg viewBox="0 0 280 220" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <linearGradient id="dist-grid-main" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#67e8f9" />
                                                <stop offset="50%" stop-color="#8b5cf6" />
                                                <stop offset="100%" stop-color="#34d399" />
                                            </linearGradient>
                                            <filter id="dist-grid-shadow" x="-50%" y="-50%" width="200%" height="200%">
                                                <feDropShadow dx="0" dy="14" stdDeviation="10" flood-color="#020617" flood-opacity="0.48" />
                                            </filter>
                                        </defs>
                                        <g filter="url(#dist-grid-shadow)">
                                            <rect x="30" y="34" width="220" height="152" rx="26" fill="rgba(15,23,42,0.82)" stroke="rgba(255,255,255,0.08)" />
                                            <line x1="74" y1="52" x2="74" y2="168" stroke="rgba(148,163,184,0.1)" stroke-dasharray="3 6" />
                                            <line x1="134" y1="52" x2="134" y2="168" stroke="rgba(148,163,184,0.1)" stroke-dasharray="3 6" />
                                            <line x1="194" y1="52" x2="194" y2="168" stroke="rgba(148,163,184,0.1)" stroke-dasharray="3 6" />
                                            <rect x="48" y="62" width="44" height="88" rx="18" fill="#67e8f9" />
                                            <rect x="108" y="90" width="44" height="60" rx="18" fill="#8b5cf6" />
                                            <rect x="168" y="112" width="44" height="38" rx="18" fill="#34d399" />
                                            <rect x="48" y="50" width="44" height="12" rx="6" fill="rgba(255,255,255,0.18)" />
                                            <rect x="108" y="78" width="44" height="12" rx="6" fill="rgba(255,255,255,0.16)" />
                                            <rect x="168" y="100" width="44" height="12" rx="6" fill="rgba(255,255,255,0.14)" />
                                            <rect x="48" y="160" width="164" height="8" rx="4" fill="url(#dist-grid-main)" opacity="0.2" />
                                        </g>
                                        <text x="236" y="64" text-anchor="end" class="fill-white text-[22px] font-semibold">61%</text>
                                        <text x="236" y="84" text-anchor="end" class="fill-slate-400 text-[10px] uppercase tracking-[0.24em]">distribution</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'exploded-pie'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-44 w-44 rounded-full bg-emerald-400/10 blur-3xl"></div>
                                    <div class="absolute h-36 w-36 rounded-full bg-sky-400/10 blur-2xl"></div>
                                    <svg viewBox="0 0 280 220" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <filter id="seg-band-shadow" x="-50%" y="-50%" width="200%" height="200%">
                                                <feDropShadow dx="0" dy="16" stdDeviation="12" flood-color="#020617" flood-opacity="0.56" />
                                            </filter>
                                        </defs>
                                        <g filter="url(#seg-band-shadow)">
                                            <rect x="38" y="48" width="204" height="30" rx="15" fill="rgba(15,23,42,0.86)" />
                                            <rect x="38" y="48" width="82" height="30" rx="15" fill="#67e8f9" />
                                            <rect x="126" y="48" width="56" height="30" rx="15" fill="#8b5cf6" />
                                            <rect x="188" y="48" width="32" height="30" rx="15" fill="#34d399" />
                                            <rect x="226" y="48" width="16" height="30" rx="8" fill="#f59e0b" />
                                            <rect x="38" y="102" width="132" height="16" rx="8" fill="rgba(15,23,42,0.82)" />
                                            <rect x="38" y="102" width="82" height="16" rx="8" fill="#67e8f9" />
                                            <rect x="38" y="134" width="108" height="16" rx="8" fill="rgba(15,23,42,0.82)" />
                                            <rect x="38" y="134" width="56" height="16" rx="8" fill="#8b5cf6" />
                                            <rect x="38" y="166" width="90" height="16" rx="8" fill="rgba(15,23,42,0.82)" />
                                            <rect x="38" y="166" width="32" height="16" rx="8" fill="#34d399" />
                                        </g>
                                        <text x="244" y="66" text-anchor="end" class="fill-white text-[22px] font-semibold">42%</text>
                                        <text x="244" y="88" text-anchor="end" class="fill-slate-400 text-[10px] uppercase tracking-[0.24em]">lead share</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'radial-progress-stack'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <div class="absolute h-44 w-44 rounded-full bg-violet-500/10 blur-3xl"></div>
                                    <svg viewBox="0 0 240 240" class="h-56 w-56">
                                        <circle cx="120" cy="120" r="74" fill="none" stroke="rgba(148,163,184,0.12)" stroke-width="8" />
                                        <circle cx="120" cy="120" r="74" fill="none" stroke="#22d3ee" stroke-width="8" stroke-linecap="round" stroke-dasharray="332 133" transform="rotate(-90 120 120)" />
                                        <circle cx="120" cy="120" r="58" fill="none" stroke="rgba(148,163,184,0.12)" stroke-width="8" />
                                        <circle cx="120" cy="120" r="58" fill="none" stroke="#8b5cf6" stroke-width="8" stroke-linecap="round" stroke-dasharray="240 124" transform="rotate(-62 120 120)" />
                                        <circle cx="120" cy="120" r="42" fill="none" stroke="rgba(148,163,184,0.12)" stroke-width="8" />
                                        <circle cx="120" cy="120" r="42" fill="none" stroke="#34d399" stroke-width="8" stroke-linecap="round" stroke-dasharray="180 84" transform="rotate(-36 120 120)" />
                                        <circle cx="120" cy="120" r="22" fill="#07111f" />
                                        <text x="120" y="123" text-anchor="middle" class="fill-white text-[20px] font-semibold">3</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'half-ring-score'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <svg viewBox="0 0 280 190" class="h-48 w-full max-w-[18rem]">
                                        <defs>
                                            <linearGradient id="half-ring" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#0ea5e9" />
                                                <stop offset="50%" stop-color="#22d3ee" />
                                                <stop offset="100%" stop-color="#34d399" />
                                            </linearGradient>
                                        </defs>
                                        <path d="M 36 146 A 104 104 0 0 1 244 146" fill="none" stroke="rgba(148,163,184,0.14)" stroke-width="18" stroke-linecap="round" />
                                        <path d="M 36 146 A 104 104 0 0 1 226 94" fill="none" stroke="url(#half-ring)" stroke-width="18" stroke-linecap="round" />
                                        <circle cx="140" cy="146" r="8" fill="#e2e8f0" />
                                        <text x="140" y="106" text-anchor="middle" class="fill-slate-400 text-[12px] uppercase tracking-[0.32em]">score</text>
                                        <text x="140" y="140" text-anchor="middle" class="fill-white text-[38px] font-semibold">88</text>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'premium-vertical-bar'"
                                    class="relative flex h-full w-full items-end justify-center"
                                >
                                    <svg viewBox="0 0 280 210" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <linearGradient id="pvb-1" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#67e8f9" />
                                                <stop offset="100%" stop-color="#1d4ed8" />
                                            </linearGradient>
                                            <linearGradient id="pvb-2" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#c084fc" />
                                                <stop offset="100%" stop-color="#7c3aed" />
                                            </linearGradient>
                                            <linearGradient id="pvb-3" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" stop-color="#34d399" />
                                                <stop offset="100%" stop-color="#0f766e" />
                                            </linearGradient>
                                        </defs>
                                        <line x1="24" y1="182" x2="256" y2="182" stroke="rgba(148,163,184,0.18)" />
                                        <rect x="40" y="84" width="38" height="98" rx="12" fill="url(#pvb-1)" />
                                        <rect x="96" y="56" width="38" height="126" rx="12" fill="url(#pvb-2)" />
                                        <rect x="152" y="28" width="38" height="154" rx="12" fill="url(#pvb-3)" />
                                        <rect x="208" y="98" width="38" height="84" rx="12" fill="url(#pvb-1)" opacity="0.82" />
                                        <rect x="40" y="84" width="38" height="14" rx="10" fill="rgba(255,255,255,0.2)" />
                                        <rect x="96" y="56" width="38" height="14" rx="10" fill="rgba(255,255,255,0.2)" />
                                        <rect x="152" y="28" width="38" height="14" rx="10" fill="rgba(255,255,255,0.2)" />
                                        <rect x="208" y="98" width="38" height="14" rx="10" fill="rgba(255,255,255,0.2)" />
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'glass-bar'"
                                    class="relative flex h-full w-full items-end justify-center"
                                >
                                    <div class="absolute bottom-3 h-28 w-52 rounded-full bg-cyan-400/18 blur-3xl"></div>
                                    <svg viewBox="0 0 280 210" class="h-52 w-full max-w-[18rem]">
                                        <line x1="20" y1="182" x2="260" y2="182" stroke="rgba(148,163,184,0.18)" />
                                        <g>
                                            <rect x="36" y="76" width="34" height="106" rx="12" fill="rgba(103,232,249,0.38)" stroke="rgba(255,255,255,0.34)" />
                                            <rect x="42" y="84" width="10" height="82" rx="8" fill="rgba(255,255,255,0.34)" />
                                            <rect x="36" y="76" width="34" height="14" rx="10" fill="rgba(255,255,255,0.18)" />
                                        </g>
                                        <g>
                                            <rect x="86" y="44" width="34" height="138" rx="12" fill="rgba(192,132,252,0.38)" stroke="rgba(255,255,255,0.34)" />
                                            <rect x="92" y="52" width="10" height="104" rx="8" fill="rgba(255,255,255,0.34)" />
                                            <rect x="86" y="44" width="34" height="14" rx="10" fill="rgba(255,255,255,0.18)" />
                                        </g>
                                        <g>
                                            <rect x="136" y="62" width="34" height="120" rx="12" fill="rgba(45,212,191,0.36)" stroke="rgba(255,255,255,0.32)" />
                                            <rect x="142" y="70" width="10" height="88" rx="8" fill="rgba(255,255,255,0.32)" />
                                            <rect x="136" y="62" width="34" height="14" rx="10" fill="rgba(255,255,255,0.18)" />
                                        </g>
                                        <g>
                                            <rect x="186" y="96" width="34" height="86" rx="12" fill="rgba(245,158,11,0.38)" stroke="rgba(255,255,255,0.34)" />
                                            <rect x="192" y="104" width="10" height="60" rx="8" fill="rgba(255,255,255,0.34)" />
                                            <rect x="186" y="96" width="34" height="14" rx="10" fill="rgba(255,255,255,0.18)" />
                                        </g>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'stacked-bar'"
                                    class="relative flex h-full w-full items-center justify-center"
                                >
                                    <svg viewBox="0 0 300 210" class="h-48 w-full">
                                        <g>
                                            <text x="34" y="34" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">entry</text>
                                            <rect x="34" y="44" width="220" height="22" rx="11" fill="rgba(15,23,42,0.92)" />
                                            <rect x="34" y="44" width="104" height="22" rx="11" fill="#22d3ee" />
                                            <rect x="138" y="44" width="62" height="22" rx="0" fill="#818cf8" />
                                            <rect x="200" y="44" width="54" height="22" rx="11" fill="#34d399" />
                                        </g>
                                        <g>
                                            <text x="34" y="94" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">engaged</text>
                                            <rect x="34" y="104" width="196" height="22" rx="11" fill="rgba(15,23,42,0.92)" />
                                            <rect x="34" y="104" width="92" height="22" rx="11" fill="#67e8f9" />
                                            <rect x="126" y="104" width="56" height="22" rx="0" fill="#c084fc" />
                                            <rect x="182" y="104" width="48" height="22" rx="11" fill="#f59e0b" />
                                        </g>
                                        <g>
                                            <text x="34" y="154" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">closed</text>
                                            <rect x="34" y="164" width="158" height="22" rx="11" fill="rgba(15,23,42,0.92)" />
                                            <rect x="34" y="164" width="74" height="22" rx="11" fill="#2dd4bf" />
                                            <rect x="108" y="164" width="34" height="22" rx="0" fill="#8b5cf6" />
                                            <rect x="142" y="164" width="50" height="22" rx="11" fill="#38bdf8" />
                                        </g>
                                        <g class="opacity-85">
                                            <circle cx="270" cy="55" r="4" fill="#22d3ee" />
                                            <circle cx="270" cy="115" r="4" fill="#c084fc" />
                                            <circle cx="270" cy="175" r="4" fill="#34d399" />
                                        </g>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'horizontal-progress'"
                                    class="w-full space-y-4"
                                >
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs text-slate-300">
                                            <span>Buyer intent</span>
                                            <span>91%</span>
                                        </div>
                                        <div class="h-3 rounded-full bg-white/10 p-[2px]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-cyan-400 via-sky-400 to-blue-500" style="width: 91%"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs text-slate-300">
                                            <span>Listing return</span>
                                            <span>74%</span>
                                        </div>
                                        <div class="h-3 rounded-full bg-white/10 p-[2px]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-violet-400 via-fuchsia-400 to-pink-500" style="width: 74%"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs text-slate-300">
                                            <span>Consult lift</span>
                                            <span>58%</span>
                                        </div>
                                        <div class="h-3 rounded-full bg-white/10 p-[2px]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-400 via-teal-400 to-cyan-400" style="width: 58%"></div>
                                        </div>
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex items-center justify-between text-xs text-slate-300">
                                            <span>Drag reduction</span>
                                            <span>37%</span>
                                        </div>
                                        <div class="h-3 rounded-full bg-white/10 p-[2px]">
                                            <div class="h-full rounded-full bg-gradient-to-r from-amber-400 via-orange-400 to-rose-400" style="width: 37%"></div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="card.key === 'floating-column'"
                                    class="relative flex h-full w-full items-end justify-center"
                                >
                                    <svg viewBox="0 0 300 220" class="h-52 w-full max-w-[18rem]">
                                        <line x1="30" y1="192" x2="270" y2="192" stroke="rgba(148,163,184,0.18)" />
                                        <line x1="30" y1="66" x2="270" y2="66" stroke="rgba(148,163,184,0.12)" stroke-dasharray="4 6" />
                                        <g>
                                            <rect x="42" y="88" width="32" height="78" rx="16" fill="rgba(15,23,42,0.9)" />
                                            <rect x="42" y="104" width="32" height="34" rx="16" fill="#22d3ee" />
                                            <line x1="58" y1="88" x2="58" y2="166" stroke="rgba(255,255,255,0.14)" stroke-width="2" />
                                            <circle cx="58" cy="88" r="5" fill="#67e8f9" />
                                            <circle cx="58" cy="166" r="5" fill="#67e8f9" opacity="0.5" />
                                        </g>
                                        <g>
                                            <rect x="102" y="56" width="32" height="108" rx="16" fill="rgba(15,23,42,0.9)" />
                                            <rect x="102" y="86" width="32" height="40" rx="16" fill="#8b5cf6" />
                                            <line x1="118" y1="56" x2="118" y2="164" stroke="rgba(255,255,255,0.14)" stroke-width="2" />
                                            <circle cx="118" cy="56" r="5" fill="#c084fc" />
                                            <circle cx="118" cy="164" r="5" fill="#c084fc" opacity="0.5" />
                                        </g>
                                        <g>
                                            <rect x="162" y="76" width="32" height="96" rx="16" fill="rgba(15,23,42,0.9)" />
                                            <rect x="162" y="114" width="32" height="26" rx="16" fill="#34d399" />
                                            <line x1="178" y1="76" x2="178" y2="172" stroke="rgba(255,255,255,0.14)" stroke-width="2" />
                                            <circle cx="178" cy="76" r="5" fill="#6ee7b7" />
                                            <circle cx="178" cy="172" r="5" fill="#6ee7b7" opacity="0.5" />
                                        </g>
                                        <g>
                                            <rect x="222" y="102" width="32" height="82" rx="16" fill="rgba(15,23,42,0.9)" />
                                            <rect x="222" y="126" width="32" height="24" rx="16" fill="#f59e0b" />
                                            <line x1="238" y1="102" x2="238" y2="184" stroke="rgba(255,255,255,0.14)" stroke-width="2" />
                                            <circle cx="238" cy="102" r="5" fill="#fcd34d" />
                                            <circle cx="238" cy="184" r="5" fill="#fcd34d" opacity="0.5" />
                                        </g>
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'shadow-depth-bar'"
                                    class="relative flex h-full w-full items-end justify-center"
                                >
                                    <svg viewBox="0 0 300 220" class="h-52 w-full max-w-[18rem]">
                                        <defs>
                                            <linearGradient id="compare-pill-a" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#67e8f9" />
                                                <stop offset="100%" stop-color="#2563eb" />
                                            </linearGradient>
                                            <linearGradient id="compare-pill-b" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#c084fc" />
                                                <stop offset="100%" stop-color="#7c3aed" />
                                            </linearGradient>
                                            <linearGradient id="compare-pill-c" x1="0%" y1="0%" x2="100%" y2="0%">
                                                <stop offset="0%" stop-color="#34d399" />
                                                <stop offset="100%" stop-color="#0f766e" />
                                            </linearGradient>
                                        </defs>
                                        <g>
                                            <text x="34" y="44" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">search</text>
                                            <rect x="34" y="54" width="210" height="22" rx="11" fill="rgba(15,23,42,0.88)" />
                                            <rect x="34" y="54" width="168" height="22" rx="11" fill="url(#compare-pill-a)" />
                                        </g>
                                        <g>
                                            <text x="34" y="104" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">referral</text>
                                            <rect x="34" y="114" width="192" height="22" rx="11" fill="rgba(15,23,42,0.88)" />
                                            <rect x="34" y="114" width="124" height="22" rx="11" fill="url(#compare-pill-b)" />
                                        </g>
                                        <g>
                                            <text x="34" y="164" class="fill-slate-400 text-[11px] uppercase tracking-[0.22em]">direct</text>
                                            <rect x="34" y="174" width="176" height="22" rx="11" fill="rgba(15,23,42,0.88)" />
                                            <rect x="34" y="174" width="96" height="22" rx="11" fill="url(#compare-pill-c)" />
                                        </g>
                                        <line x1="254" y1="42" x2="254" y2="198" stroke="rgba(148,163,184,0.14)" stroke-dasharray="4 6" />
                                    </svg>
                                </div>

                                <div
                                    v-else-if="card.key === 'premium-data-table'"
                                    class="w-full rounded-[1.4rem] border border-white/8 bg-slate-950/50 p-3"
                                >
                                    <div class="rounded-[1rem] border border-white/8 overflow-hidden">
                                        <div class="grid grid-cols-[1.6fr,0.8fr,0.8fr] bg-slate-900/95 px-3 py-2 text-[11px] uppercase tracking-[0.22em] text-slate-400">
                                            <span>Entity</span>
                                            <span>Value</span>
                                            <span>Status</span>
                                        </div>
                                        <div
                                            v-for="(row, index) in premiumTableRows"
                                            :key="row.name"
                                            class="grid grid-cols-[1.6fr,0.8fr,0.8fr] items-center gap-2 px-3 py-3 text-sm"
                                            :class="index % 2 === 0 ? 'bg-white/[0.03]' : 'bg-white/[0.06]'"
                                        >
                                            <div class="space-y-2">
                                                <div class="font-medium text-white">{{ row.name }}</div>
                                                <div class="h-1.5 rounded-full bg-white/8">
                                                    <div class="h-full rounded-full" :class="row.tone" :style="{ width: row.width }"></div>
                                                </div>
                                            </div>
                                            <div class="text-slate-200">{{ row.value }}</div>
                                            <div>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] ring-1" :class="statusClass(row.status)">
                                                    {{ row.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="card.key === 'kpi-comparison-table'"
                                    class="w-full rounded-[1.4rem] border border-white/8 bg-slate-950/50 p-3"
                                >
                                    <div class="rounded-[1rem] border border-white/8 overflow-hidden">
                                        <div class="grid grid-cols-[1.35fr,0.7fr,0.7fr,0.7fr] bg-slate-900/95 px-3 py-2 text-[11px] uppercase tracking-[0.22em] text-slate-400">
                                            <span>KPI</span>
                                            <span>Now</span>
                                            <span>Delta</span>
                                            <span>Flag</span>
                                        </div>
                                        <div
                                            v-for="(row, index) in comparisonRows"
                                            :key="row.name"
                                            class="grid grid-cols-[1.35fr,0.7fr,0.7fr,0.7fr] items-center gap-2 px-3 py-3 text-sm"
                                            :class="index % 2 === 0 ? 'bg-white/[0.03]' : 'bg-white/[0.06]'"
                                        >
                                            <div class="font-medium text-white">{{ row.name }}</div>
                                            <div class="text-slate-200">{{ row.value }}</div>
                                            <div class="text-slate-300">{{ row.delta }}</div>
                                            <div>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] ring-1" :class="statusClass(row.status)">
                                                    {{ row.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="card.key === 'source-ranking-table'"
                                    class="w-full rounded-[1.4rem] border border-white/8 bg-slate-950/50 p-3"
                                >
                                    <div class="rounded-[1rem] border border-white/8 overflow-hidden">
                                        <div class="grid grid-cols-[0.45fr,1.35fr,0.7fr] bg-slate-900/95 px-3 py-2 text-[11px] uppercase tracking-[0.22em] text-slate-400">
                                            <span>Rank</span>
                                            <span>Source</span>
                                            <span>Share</span>
                                        </div>
                                        <div
                                            v-for="(row, index) in sourceRows"
                                            :key="row.name"
                                            class="grid grid-cols-[0.45fr,1.35fr,0.7fr] items-center gap-3 px-3 py-3 text-sm"
                                            :class="index % 2 === 0 ? 'bg-white/[0.03]' : 'bg-white/[0.06]'"
                                        >
                                            <div class="text-slate-400">0{{ index + 1 }}</div>
                                            <div class="space-y-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2.5 w-2.5 rounded-full" :class="row.tone"></span>
                                                    <span class="font-medium text-white">{{ row.name }}</span>
                                                </div>
                                                <div class="h-1.5 rounded-full bg-white/8">
                                                    <div class="h-full rounded-full" :class="row.tone" :style="{ width: row.width }"></div>
                                                </div>
                                            </div>
                                            <div class="space-y-1 text-right">
                                                <div class="text-slate-100">{{ row.value }}</div>
                                                <div class="text-[11px] text-slate-400">{{ row.delta }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="card.key === 'pie-table-hybrid'"
                                    class="grid w-full gap-3 md:grid-cols-[0.9fr,1.1fr]"
                                >
                                    <div class="flex items-center justify-center rounded-[1.25rem] border border-white/8 bg-slate-950/45 p-2">
                                        <svg viewBox="0 0 180 180" class="h-36 w-36">
                                            <circle cx="90" cy="90" r="54" fill="none" stroke="rgba(148,163,184,0.15)" stroke-width="24" />
                                            <circle cx="90" cy="90" r="54" fill="none" stroke="#22d3ee" stroke-width="24" stroke-dasharray="143 196" transform="rotate(-90 90 90)" />
                                            <circle cx="90" cy="90" r="54" fill="none" stroke="#8b5cf6" stroke-width="24" stroke-dasharray="91 248" stroke-dashoffset="-146" transform="rotate(-90 90 90)" />
                                            <circle cx="90" cy="90" r="54" fill="none" stroke="#34d399" stroke-width="24" stroke-dasharray="61 278" stroke-dashoffset="-241" transform="rotate(-90 90 90)" />
                                            <circle cx="90" cy="90" r="54" fill="none" stroke="#f59e0b" stroke-width="24" stroke-dasharray="45 294" stroke-dashoffset="-306" transform="rotate(-90 90 90)" />
                                            <circle cx="90" cy="90" r="20" fill="#07111f" />
                                        </svg>
                                    </div>
                                    <div class="rounded-[1.25rem] border border-white/8 bg-slate-950/45 p-3">
                                        <div
                                            v-for="row in pieHybridRows"
                                            :key="row.name"
                                            class="py-2.5 first:pt-0 last:pb-0"
                                        >
                                            <div class="flex items-center justify-between gap-3">
                                                <div class="flex items-center gap-2">
                                                    <span class="h-2.5 w-2.5 rounded-full" :class="row.tone"></span>
                                                    <span class="text-sm font-medium text-white">{{ row.name }}</span>
                                                </div>
                                                <span class="text-sm text-slate-200">{{ row.value }}</span>
                                            </div>
                                            <div class="mt-2 h-1.5 rounded-full bg-white/8">
                                                <div class="h-full rounded-full" :class="row.tone" :style="{ width: row.width }"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else-if="card.key === 'bar-table-hybrid'"
                                    class="w-full rounded-[1.4rem] border border-white/8 bg-slate-950/50 p-3"
                                >
                                    <div class="grid grid-cols-[1.2fr,1fr,0.65fr] bg-slate-900/95 px-3 py-2 text-[11px] uppercase tracking-[0.22em] text-slate-400 rounded-[0.9rem]">
                                        <span>Page</span>
                                        <span>Volume</span>
                                        <span>Status</span>
                                    </div>
                                    <div class="mt-2 space-y-2">
                                        <div
                                            v-for="row in barHybridRows"
                                            :key="row.name"
                                            class="grid grid-cols-[1.2fr,1fr,0.65fr] items-center gap-3 rounded-[0.95rem] bg-white/[0.04] px-3 py-3"
                                        >
                                            <div class="text-sm font-medium text-white">{{ row.name }}</div>
                                            <div class="space-y-1">
                                                <div class="h-2 rounded-full bg-white/8">
                                                    <div class="h-full rounded-full" :class="row.tone" :style="{ width: row.width }"></div>
                                                </div>
                                                <div class="text-[11px] text-slate-400">{{ row.value }}</div>
                                            </div>
                                            <div>
                                                <span class="inline-flex rounded-full px-2.5 py-1 text-[11px] ring-1" :class="statusClass(row.status)">
                                                    {{ row.status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    v-else
                                    class="grid w-full gap-3"
                                >
                                    <div
                                        v-for="report in reportCards"
                                        :key="report.title"
                                        class="rounded-[1.15rem] border border-white/8 bg-[linear-gradient(135deg,rgba(15,23,42,0.86),rgba(15,23,42,0.52))] p-3 shadow-[inset_0_1px_0_rgba(255,255,255,0.06)]"
                                    >
                                        <div class="flex items-start justify-between gap-3">
                                            <div>
                                                <div class="text-[11px] uppercase tracking-[0.22em] text-slate-400">
                                                    {{ report.title }}
                                                </div>
                                                <div class="mt-2 text-2xl font-semibold text-white">
                                                    {{ report.value }}
                                                </div>
                                                <div class="mt-1 text-sm text-slate-400">
                                                    {{ report.detail }}
                                                </div>
                                            </div>
                                            <div :class="`h-11 w-11 rounded-2xl bg-gradient-to-br ${report.tone}`"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="relative z-10 mt-4 border-t border-white/8 pt-4">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs uppercase tracking-[0.2em] text-slate-500">
                                        {{ card.status }}
                                    </p>
                                    <p class="text-xs font-medium text-slate-300">
                                        {{ card.value }}
                                    </p>
                                </div>
                            </div>
                        </article>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
