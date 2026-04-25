<script setup lang="ts">
defineProps<{
  title: string
  detail: string
  evidence?: Array<{ label: string; value: string | number | null }>
}>()
</script>

<template>
  <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Insight</p>
    <h3 class="mt-2 text-base font-semibold text-slate-900">{{ title }}</h3>
    <p class="mt-3 text-sm leading-6 text-slate-600">{{ detail }}</p>

    <dl v-if="evidence?.length" class="mt-4 grid gap-2 sm:grid-cols-2">
      <div
        v-for="item in evidence"
        :key="`${title}-${item.label}`"
        class="rounded-2xl bg-slate-50 px-3 py-2"
      >
        <dt class="text-[11px] uppercase tracking-[0.18em] text-slate-500">{{ item.label }}</dt>
        <dd class="mt-1 text-sm font-medium text-slate-900">{{ item.value ?? '—' }}</dd>
      </div>
    </dl>
  </article>
</template>
