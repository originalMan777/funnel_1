<script setup lang="ts">
defineProps<{
    label: string;
    value: string | number;
    meta?: string | null;
    selected?: boolean;
}>();

defineEmits<{
    select: [];
}>();
</script>

<template>
    <button
        type="button"
        class="w-full rounded-2xl border p-4 text-left transition"
        :class="
            selected
                ? 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-300/50'
                : 'border-slate-200 bg-white text-slate-900 hover:border-slate-300 hover:bg-slate-50'
        "
        @click="$emit('select')"
    >
        <p
            class="text-xs font-semibold tracking-[0.18em] uppercase"
            :class="selected ? 'text-slate-300' : 'text-slate-500'"
        >
            {{ label }}
        </p>
        <p class="mt-2 text-2xl font-semibold tracking-tight">
            {{ value }}
        </p>
        <p
            v-if="meta"
            class="mt-2 text-sm leading-5"
            :class="selected ? 'text-slate-300' : 'text-slate-600'"
        >
            {{ meta }}
        </p>
    </button>
</template>
