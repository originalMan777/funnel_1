<script setup lang="ts">
type DonutSegment = {
    label: string;
    value: number;
    color?: string;
};

withDefaults(
    defineProps<{
        label?: string;
        data?: DonutSegment[];
    }>(),
    {
        label: 'Traffic Sources',
        data: () => [
            { label: 'Google', value: 50, color: '#84cc16' },
            { label: 'Facebook', value: 30, color: '#eab308' },
            { label: 'Direct', value: 20, color: '#dc2626' },
        ],
    },
);
</script>

<template>
    <div>
        <div class="flex items-center justify-center mt-10">
            <svg viewBox="0 0 200 200" class="w-64 h-64">
                <g transform="rotate(-90 100 100)">
                    <circle
                        cx="100"
                        cy="100"
                        r="70"
                        stroke="#020617"
                        stroke-width="36"
                        fill="none"
                    />

                    <circle
                        cx="100"
                        cy="100"
                        r="70"
                        stroke="#84cc16"
                        stroke-width="36"
                        fill="none"
                        stroke-dasharray="220 440"
                    />

                    <circle
                        cx="100"
                        cy="100"
                        r="70"
                        stroke="#eab308"
                        stroke-width="36"
                        fill="none"
                        stroke-dasharray="132 440"
                        stroke-dashoffset="-220"
                    />

                    <circle
                        cx="100"
                        cy="100"
                        r="70"
                        stroke="#dc2626"
                        stroke-width="36"
                        fill="none"
                        stroke-dasharray="88 440"
                        stroke-dashoffset="-352"
                    />
                </g>

                <text x="100" y="105" text-anchor="middle" class="fill-white text-xl font-bold">
                    100%
                </text>
            </svg>
        </div>

        <div class="mt-8 space-y-2 text-sm">
            <div
                v-for="segment in data"
                :key="segment.label"
                class="flex justify-between"
                :style="{ color: segment.color }"
            >
                <span>{{ segment.label }}</span>
                <span>{{ segment.value }}%</span>
            </div>
        </div>
    </div>
</template>
