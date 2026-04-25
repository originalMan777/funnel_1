<script setup lang="ts">
defineProps<{
  label: string
  value: string
  context?: string | null
}>()
</script>

<template>
  <article class="rounded-[1.5rem] border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/50">
    <p class="text-sm text-slate-500">{{ label }}</p>
    <p class="mt-3 text-3xl font-semibold tracking-tight text-slate-950">{{ value }}</p>
    <p v-if="context" class="mt-3 text-sm leading-6 text-slate-600">{{ context }}</p>
  </article>
</template>
