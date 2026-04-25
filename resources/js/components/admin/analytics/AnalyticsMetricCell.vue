<script setup lang="ts">
defineProps<{
  label?: string | null
  value: string
  meta?: string | null
  align?: 'left' | 'right'
}>()
</script>

<template>
  <div :class="align === 'right' ? 'text-right' : 'text-left'">
    <p v-if="label" class="text-xs uppercase tracking-[0.18em] text-slate-500">{{ label }}</p>
    <p class="text-sm font-medium text-slate-900">{{ value }}</p>
    <p v-if="meta" class="text-sm text-slate-500">{{ meta }}</p>
  </div>
</template>
