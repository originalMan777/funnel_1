<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type AttentionItem = {
    id: number | string;
    name: string;
    reason: string;
    source: string;
    level: 'High' | 'Medium' | 'Watch';
    href: string;
    age: string;
};

defineProps<{
    items: AttentionItem[];
}>();

const levelClasses = {
    High: 'bg-rose-50 text-rose-700 border-rose-200',
    Medium: 'bg-amber-50 text-amber-700 border-amber-200',
    Watch: 'bg-sky-50 text-sky-700 border-sky-200',
};
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Needs Attention</p>
                <h2 class="mt-1 text-xl font-black tracking-tight text-slate-950">Warm lead pressure</h2>
            </div>
            <Link
                :href="route('admin.acquisition.contacts.index')"
                class="rounded-full border border-slate-200 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-slate-700 transition hover:bg-slate-950 hover:text-white"
            >
                View all
            </Link>
        </div>

        <div class="mt-5 space-y-3">
            <Link
                v-for="item in items"
                :key="item.id"
                :href="item.href"
                class="group flex items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 transition hover:-translate-y-0.5 hover:bg-white hover:shadow-sm"
            >
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <p class="truncate text-sm font-black text-slate-950">{{ item.name }}</p>
                        <span class="rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-wide" :class="levelClasses[item.level]">
                            {{ item.level }}
                        </span>
                    </div>
                    <p class="mt-1 text-sm leading-5 text-slate-600">{{ item.reason }}</p>
                    <p class="mt-2 text-xs font-semibold uppercase tracking-wide text-slate-400">{{ item.source }} · {{ item.age }}</p>
                </div>
                <span class="shrink-0 text-slate-300 transition group-hover:translate-x-1 group-hover:text-slate-950">→</span>
            </Link>
        </div>
    </section>
</template>
