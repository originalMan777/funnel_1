<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import { visualSandboxRegistry } from '@/components/admin/analytics/visuals/visualSandboxRegistry';

const visualOptions = visualSandboxRegistry;

const selectedVisualKey = ref(visualOptions[0].key);

const selectedVisual = computed(
    () =>
        visualOptions.find((visual) => visual.key === selectedVisualKey.value) ??
        visualOptions[0],
);
</script>

<template>
    <Head title="Visual Workbench" />

    <AdminLayout>
        <div class="min-h-screen bg-[#f4efe7] px-4 py-6 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-7xl space-y-6">
                <section class="rounded-[1.75rem] border border-stone-300 bg-white px-6 py-8 shadow-sm sm:px-8">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-500">
                        Analytics Sandbox
                    </p>

                    <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-950 sm:text-4xl">
                        Visual Workbench
                    </h1>

                    <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-600 sm:text-base">
                        Active testing room for one analytics visual at a time.
                    </p>
                </section>

                <section class="grid gap-5 lg:grid-cols-[18rem_1fr]">
                    <aside class="rounded-[1.25rem] border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="px-2 text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">
                            Visual Selector
                        </p>

                        <div class="mt-4 space-y-2">
                            <button
                                v-for="visual in visualOptions"
                                :key="visual.key"
                                type="button"
                                class="w-full rounded-xl border px-3 py-3 text-left transition"
                                :class="
                                    selectedVisual.key === visual.key
                                        ? 'border-slate-950 bg-slate-950 text-white shadow-sm'
                                        : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'
                                "
                                @click="selectedVisualKey = visual.key"
                            >
                                <div class="flex items-center justify-between">
    <span class="block text-sm font-semibold">
        {{ visual.name }}
    </span>

    <span
        class="h-2.5 w-2.5 rounded-full"
        :class="[
            visual.status === 'Accepted'
                ? 'bg-green-400'
                : 'bg-yellow-400'
        ]"
    />
</div>
                            </button>
                        </div>
                    </aside>

                    <div class="grid gap-5 xl:grid-cols-[1fr_18rem]">
                        <section class="overflow-hidden rounded-[1.5rem] border border-slate-800 bg-slate-950 shadow-2xl">
                            <div class="border-b border-white/10 px-6 py-5">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-cyan-300">
                                    Large Preview
                                </p>

                                <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                                    {{ selectedVisual.name }}
                                </h2>
                            </div>

                            <div class="min-h-[36rem] bg-slate-950 p-6">
                                <component
                                    :is="selectedVisual.component"
                                    :data="selectedVisual.data"
                                    :foot="selectedVisual.foot"
                                    :label="selectedVisual.label"
                                    :meta="selectedVisual.meta"
                                    :value="selectedVisual.value"
                                />
                            </div>
                        </section>

                        <aside class="rounded-[1.25rem] border border-slate-200 bg-white p-5 shadow-sm">
                            <p class="text-[11px] font-bold uppercase tracking-[0.22em] text-slate-500">
                                Notes
                            </p>

                            <h3 class="mt-3 text-xl font-semibold tracking-tight text-slate-950">
                                {{ selectedVisual.name }}
                            </h3>

                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                    Status
                                </p>
                                <p class="mt-1 text-sm font-semibold text-slate-950">
                                    {{ selectedVisual.status }}
                                </p>
                            </div>

                            <div class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-slate-500">
                                    Purpose
                                </p>
                                <p class="mt-1 text-sm leading-6 text-slate-700">
                                    {{ selectedVisual.purpose }}
                                </p>
                            </div>
                        </aside>
                    </div>
                </section>
            </div>
        </div>
    </AdminLayout>
</template>
