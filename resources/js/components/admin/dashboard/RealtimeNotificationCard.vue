<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

type NotificationCard = {
    label: string;
    value: string | number;
    meta: string;
    badge: string;
    href: string;
    tone: 'lead' | 'message' | 'email' | 'assessment';
    detail: string;
    isNew?: boolean;
};

const props = defineProps<{
    card: NotificationCard;
}>();

const isPressed = ref(false);

const toneClasses = {
    lead: 'from-emerald-50 to-white border-emerald-200',
    message: 'from-violet-50 to-white border-violet-200',
    email: 'from-sky-50 to-white border-sky-200',
    assessment: 'from-amber-50 to-white border-amber-200',
};

const badgeClasses = {
    lead: 'bg-emerald-100 text-emerald-800',
    message: 'bg-violet-100 text-violet-800',
    email: 'bg-sky-100 text-sky-800',
    assessment: 'bg-amber-100 text-amber-800',
};

const press = () => {
    isPressed.value = true;
};

const release = () => {
    if (!isPressed.value) {
        return;
    }

    isPressed.value = false;
    router.visit(props.card.href);
};

const cancelPress = () => {
    isPressed.value = false;
};

const handleKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    press();
};

const handleKeyup = (event: KeyboardEvent) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }

    event.preventDefault();
    release();
};
</script>

<template>
    <div
        role="link"
        tabindex="0"
        class="group relative min-h-[168px] cursor-pointer select-none overflow-hidden rounded-3xl border bg-gradient-to-br p-5 shadow-sm outline-none transition duration-150 hover:-translate-y-0.5 hover:shadow-md focus-visible:ring-2 focus-visible:ring-slate-950/20"
        :class="[
            toneClasses[card.tone],
            card.isNew ? 'ring-2 ring-slate-950/10' : '',
            isPressed ? 'translate-y-1 scale-[0.985] shadow-inner hover:translate-y-1 hover:shadow-inner' : '',
        ]"
        @pointerdown="press"
        @pointerup="release"
        @pointerleave="cancelPress"
        @pointercancel="cancelPress"
        @keydown="handleKeydown"
        @keyup="handleKeyup"
    >
        <span v-if="card.isNew" class="absolute right-4 top-4 flex h-3 w-3">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
            <span class="relative inline-flex h-3 w-3 rounded-full bg-rose-500"></span>
        </span>

        <span
            class="pointer-events-none absolute inset-0 rounded-3xl bg-slate-950/0 transition duration-150"
            :class="isPressed ? 'bg-slate-950/10' : ''"
        ></span>

        <div class="relative flex h-full flex-col justify-between gap-4">
            <div>
                <div class="flex items-center justify-between gap-3 pr-5">
                    <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-500">{{ card.label }}</p>
                    <span class="rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wide" :class="badgeClasses[card.tone]">
                        {{ card.badge }}
                    </span>
                </div>

                <div class="mt-4 flex items-end gap-3">
                    <p class="text-4xl font-black tracking-tight text-slate-950">{{ card.value }}</p>
                    <p class="pb-1 text-xs font-bold uppercase tracking-wide text-slate-500">{{ card.meta }}</p>
                </div>

                <p class="mt-3 text-sm leading-5 text-slate-600">{{ card.detail }}</p>
            </div>

            <div class="flex items-center justify-between text-xs font-black uppercase tracking-wide text-slate-500">
                <span>Open queue</span>
                <span class="transition" :class="isPressed ? 'translate-x-0 text-slate-950' : 'group-hover:translate-x-1 group-hover:text-slate-950'">→</span>
            </div>
        </div>
    </div>
</template>
