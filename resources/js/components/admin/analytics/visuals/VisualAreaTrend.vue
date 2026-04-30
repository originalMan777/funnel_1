<script setup lang="ts">
const props = withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
        delta?: string;
        direction?: 'up' | 'down' | 'neutral';
        points?: number[];
        color?: string;
    }>(),
    {
        label: 'Lead Activity',
        value: '1,842',
        meta: '30-day activity volume',
        delta: '+18%',
        direction: 'up',
        points: () => [22, 28, 24, 35, 42, 38, 51, 57, 49, 68, 72, 86],
        color: '#2563eb',
    },
);

const width = 520;
const height = 230;
const padding = 12;

const minPoint = Math.min(...props.points);
const maxPoint = Math.max(...props.points);
const range = Math.max(maxPoint - minPoint, 1);

const coordinates = props.points.map((point, index) => {
    const x = padding + (index / (props.points.length - 1)) * (width - padding * 2);
    const y = height - padding - ((point - minPoint) / range) * (height - padding * 2);

    return { x, y, value: point };
});

const path = coordinates
    .map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`)
    .join(' ');

const areaPath = `${path} L ${coordinates[coordinates.length - 1].x} ${height - padding} L ${coordinates[0].x} ${height - padding} Z`;

const endPoint = coordinates[coordinates.length - 1];

const signalColor =
    props.direction === 'down'
        ? '#f87171'
        : props.direction === 'neutral'
            ? '#fde047'
            : props.color;

const deltaClass =
    props.direction === 'down'
        ? 'border-red-300/45 bg-red-400/10 text-red-300'
        : props.direction === 'neutral'
            ? 'border-yellow-300/45 bg-yellow-400/10 text-yellow-300'
            : 'border-green-300/45 bg-green-400/10 text-green-300';
</script>

<template>
    <div class="w-full max-w-6xl mx-auto rounded-2xl border border-white/10 bg-gradient-to-br from-[#101a33] via-[#071026] to-[#020617] p-8 shadow-2xl shadow-black/40">
        <div class="relative overflow-hidden rounded-xl border border-white/15 bg-white/[0.04] p-6">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-blue-500/15 blur-3xl" />
            <div class="pointer-events-none absolute -left-20 -bottom-20 h-64 w-64 rounded-full bg-cyan-400/10 blur-3xl" />

            <div class="relative mb-6 flex items-start justify-between gap-6">
                <div>
                    <div class="text-xs font-semibold uppercase tracking-[0.24em] text-white/45">
                        {{ label }}
                    </div>

                    <div class="mt-2 text-xs text-white/35">
                        {{ meta }}
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="rounded-md border px-2.5 py-1 text-xs font-semibold"
                        :class="deltaClass"
                    >
                        {{ delta }}
                    </div>

                    <div class="border border-white bg-white px-3 py-1 text-xs font-semibold text-slate-950">
                        AREA
                    </div>
                </div>
            </div>

            <div class="relative rounded-2xl border border-white/15 bg-black/25 p-5">
                <div class="mb-4 flex items-end justify-between">
                    <div>
                        <div class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/30">
                            Volume
                        </div>

                        <div
                            class="mt-1 text-3xl font-semibold"
                            :style="{ color: signalColor }"
                        >
                            {{ value }}
                        </div>
                    </div>

                    <div class="text-right">
                        <div class="text-[10px] font-semibold uppercase tracking-[0.2em] text-white/30">
                            Direction
                        </div>

                        <div
                            class="mt-1 text-lg font-semibold"
                            :style="{ color: signalColor }"
                        >
                            {{ direction === 'down' ? 'Falling' : direction === 'neutral' ? 'Stable' : 'Rising' }}
                        </div>
                    </div>
                </div>

                <svg
                    :viewBox="`0 0 ${width} ${height}`"
                    class="h-[230px] w-full overflow-visible"
                    preserveAspectRatio="none"
                >
                    <defs>
                        <linearGradient id="areaTrendFillStrong" x1="0%" y1="0%" x2="0%" y2="100%">
                            <stop offset="0%" :stop-color="signalColor" stop-opacity="0.78" />
                            <stop offset="45%" :stop-color="signalColor" stop-opacity="0.36" />
                            <stop offset="100%" :stop-color="signalColor" stop-opacity="0.05" />
                        </linearGradient>

                        <linearGradient id="areaTrendStrokeSoft" x1="0%" y1="0%" x2="100%" y2="0%">
                            <stop offset="0%" :stop-color="signalColor" stop-opacity="0.45" />
                            <stop offset="70%" :stop-color="signalColor" stop-opacity="0.88" />
                            <stop offset="100%" stop-color="#ffffff" stop-opacity="0.85" />
                        </linearGradient>

                        <filter id="areaTrendGlowStrong" x="-50%" y="-50%" width="200%" height="200%">
                            <feGaussianBlur stdDeviation="5" result="blur" />
                            <feMerge>
                                <feMergeNode in="blur" />
                                <feMergeNode in="SourceGraphic" />
                            </feMerge>
                        </filter>
                    </defs>

                    <line
                        :x1="padding"
                        :x2="width - padding"
                        :y1="height / 2"
                        :y2="height / 2"
                        stroke="rgba(255,255,255,0.12)"
                        stroke-width="1"
                        stroke-dasharray="10 10"
                    />

                    <path
                        :d="areaPath"
                        fill="url(#areaTrendFillStrong)"
                    />

                    <path
                        :d="path"
                        fill="none"
                        :stroke="signalColor"
                        stroke-width="14"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        opacity="0.12"
                        filter="url(#areaTrendGlowStrong)"
                    />

                    <path
                        :d="path"
                        fill="none"
                        stroke="url(#areaTrendStrokeSoft)"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    />

                    <circle
                        :cx="endPoint.x"
                        :cy="endPoint.y"
                        r="12"
                        :fill="signalColor"
                        opacity="0.22"
                    />

                    <circle
                        :cx="endPoint.x"
                        :cy="endPoint.y"
                        r="5"
                        fill="#020617"
                        :stroke="signalColor"
                        stroke-width="3"
                    />
                </svg>

                <div class="mt-3 flex justify-between text-[10px] font-semibold uppercase tracking-[0.18em] text-white/30">
                    <span>Start</span>
                    <span>Momentum</span>
                    <span>Now</span>
                </div>
            </div>
        </div>
    </div>
</template>
