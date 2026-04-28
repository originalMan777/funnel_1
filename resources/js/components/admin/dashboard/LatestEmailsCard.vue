<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type EmailItem = {
    id: number | string;
    from: string;
    subject: string;
    time: string;
    unread?: boolean;
};

defineProps<{
    emails: EmailItem[];
}>();
</script>

<template>
    <section class="rounded-3xl border border-slate-200 bg-slate-950 p-5 text-white shadow-sm">
        <div class="flex items-start justify-between gap-4">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-400">Email Pulse</p>
                <h2 class="mt-1 text-xl font-black tracking-tight">Latest 5 emails</h2>
            </div>
            <Link
                :href="route('admin.communications.index')"
                class="rounded-full border border-white/15 px-3 py-1.5 text-xs font-black uppercase tracking-wide text-slate-200 transition hover:bg-white hover:text-slate-950"
            >
                Open
            </Link>
        </div>

        <div class="mt-5 space-y-3">
            <Link
                v-for="email in emails"
                :key="email.id"
                :href="route('admin.communications.index')"
                class="block rounded-2xl border border-white/10 bg-white/5 p-3 transition hover:bg-white/10"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <span v-if="email.unread" class="h-2 w-2 rounded-full bg-emerald-400"></span>
                            <p class="truncate text-sm font-black">{{ email.subject }}</p>
                        </div>
                        <p class="mt-1 truncate text-xs text-slate-300">{{ email.from }}</p>
                    </div>
                    <span class="shrink-0 text-xs font-bold text-slate-400">{{ email.time }}</span>
                </div>
            </Link>
        </div>
    </section>
</template>
