<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'

const props = defineProps({
  title: {
    type: String,
    default: '',
  },
})

const page = usePage<any>()

const titleBarConfig = computed(() => page.props?.ui?.titleBar ?? {})
const resolvedTitle = computed(() => props.title || titleBarConfig.value?.title || page.props?.pageTitle || '')
const visible = computed(() => titleBarConfig.value?.enabled !== false && resolvedTitle.value.trim().length > 0)
</script>

<template>
  <header v-if="visible" class="title-bar flex items-center justify-between border-b border-gray-200 bg-white px-4 py-2">
    <h1 class="text-xl font-semibold text-gray-900">
      {{ resolvedTitle }}
    </h1>

    <div class="flex items-center space-x-2">
      <slot name="actions" />
    </div>
  </header>
</template>
