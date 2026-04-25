<script setup lang="ts">
defineProps<{
  value: string
  tone?: 'neutral' | 'good' | 'warn'
}>()
</script>

<template>
  <span
    class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold"
    :class="{
      'bg-slate-100 text-slate-700': tone === 'neutral' || !tone,
      'bg-emerald-100 text-emerald-700': tone === 'good',
      'bg-amber-100 text-amber-700': tone === 'warn',
    }"
  >
    {{ value }}
  </span>
</template>
