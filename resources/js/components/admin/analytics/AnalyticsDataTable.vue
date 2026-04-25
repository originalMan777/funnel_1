<script setup lang="ts">
defineProps<{
  emptyText?: string
  colCount: number
  showEmpty?: boolean
}>()
</script>

<template>
  <div class="overflow-hidden rounded-[1.75rem] border border-slate-200 bg-white shadow-sm shadow-slate-200/50">
    <div v-if="$slots.description" class="border-b border-slate-200 px-4 py-3 text-sm text-slate-600">
      <slot name="description" />
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-slate-200">
        <thead class="bg-slate-50">
          <slot name="head" />
        </thead>
        <tbody class="divide-y divide-slate-200">
          <slot />
          <tr v-if="showEmpty && $slots.empty">
            <slot name="empty" />
          </tr>
          <tr v-else-if="showEmpty && emptyText">
            <td :colspan="colCount" class="px-4 py-6 text-sm text-slate-600">
              {{ emptyText }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
