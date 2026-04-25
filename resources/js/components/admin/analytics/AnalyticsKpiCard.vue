<script setup lang="ts">
import { computed } from 'vue'

const props = withDefaults(
  defineProps<{
    label: string
    value: string | number
    hint?: string | null
    eyebrow?: string | null
    tone?: 'neutral' | 'emerald' | 'amber' | 'sky'
    selected?: boolean
  }>(),
  {
    hint: null,
    eyebrow: null,
    tone: 'neutral',
    selected: false,
  },
)

const toneClasses = computed(() => {
  if (props.selected) {
    return 'border-slate-900 bg-slate-900 text-white shadow-lg shadow-slate-300/50'
  }

  switch (props.tone) {
    case 'emerald':
      return 'border-emerald-200 bg-emerald-50/80 text-emerald-950'
    case 'amber':
      return 'border-amber-200 bg-amber-50/90 text-amber-950'
    case 'sky':
      return 'border-sky-200 bg-sky-50/90 text-sky-950'
    default:
      return 'border-slate-200 bg-white text-slate-950'
  }
})
</script>

<template>
  <article class="rounded-[1.5rem] border p-5 transition" :class="toneClasses">
    <p v-if="eyebrow" class="text-[11px] font-semibold uppercase tracking-[0.22em]" :class="selected ? 'text-slate-300' : 'text-slate-500'">
      {{ eyebrow }}
    </p>
    <p class="mt-1 text-sm" :class="selected ? 'text-slate-300' : 'text-slate-500'">
      {{ label }}
    </p>
    <p class="mt-3 text-3xl font-semibold tracking-tight">
      {{ value }}
    </p>
    <p v-if="hint" class="mt-3 text-sm leading-6" :class="selected ? 'text-slate-300' : 'text-slate-600'">
      {{ hint }}
    </p>
    <slot />
  </article>
</template>
