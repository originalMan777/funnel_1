<script setup lang="ts">
withDefaults(
    defineProps<{
        label?: string;
        value?: string | number;
        meta?: string;
    }>(),
    {
        label: 'Signal Strength',
        value: '0',
        meta: 'Indicator line crosses the ring',
    },
);
</script>

<template>
    <div class="mt-8 border border-amber-300/25 bg-[linear-gradient(145deg,#17120a,#050505)] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.72)]">
        <div class="mb-6 flex items-center justify-between gap-4">
            <div>
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-amber-200/75">
                    {{ label }}
                </p>
                <p class="mt-1 text-xs font-medium text-stone-500">
                    {{ meta }}
                </p>
            </div>

            <p class="text-4xl font-black tracking-[-0.06em] text-stone-100">
                {{ value }}
            </p>
        </div>

        <div class="border border-white/10 bg-[#080808] p-6">
            <div class="relative mx-auto mt-10 h-28 max-w-[22rem]">
                <div class="absolute left-0 right-0 top-1/2 h-5 -translate-y-1/2 bg-stone-800">
                    <div class="h-full w-full bg-gradient-to-r from-red-800 via-amber-500 to-lime-400"></div>
                </div>

                <div class="absolute left-[72%] top-1/2 h-24 w-3 -translate-x-1/2 -translate-y-1/2 bg-lime-300 shadow-[0_0_18px_rgba(190,242,100,0.55)]"></div>

                <div class="absolute left-[72%] top-1/2 h-32 w-px -translate-x-1/2 -translate-y-1/2 bg-lime-200/40"></div>

                <div class="absolute left-0 top-[calc(50%+2rem)] text-[13px] font-bold uppercase tracking-[0.22em] text-red-300">
                    Weak
                </div>

                <div class="absolute right-0 top-[calc(50%+2rem)] text-[13px] font-bold uppercase tracking-[0.22em] text-lime-300">
                    Strong
                </div>
            </div>

            <div class="mt-10 border-t border-white/10 pt-4 text-center">
                <p class="text-[11px] font-bold uppercase tracking-[0.24em] text-stone-400">
                    Straight strength track / hard marker
                </p>
            </div>
        </div>
    </div>
</template>
