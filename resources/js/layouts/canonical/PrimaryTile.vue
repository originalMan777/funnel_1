<template>
  <div class="primary-tile relative flex flex-col flex-1 min-h-0 w-full border-b border-gray-200">
    <!-- TitleBar at top -->
    <TitleBar />

    <div class="flex flex-1 min-h-0">
      <!-- Main content slot on the left -->
      <div class="flex-1 min-w-0 overflow-y-auto">
        <slot />
      </div>

      <!-- Conceptual right sidebar, collapsible -->
      <ContextualRightSidebar />
    </div>

    <!-- StateBar at bottom -->
    <StateBar />
  </div>
</template>

<script setup lang="ts">
import { provide, ref } from 'vue'
import ContextualRightSidebar from '@/layouts/canonical/ContextualRightSidebar.vue'
import StateBar from '@/layouts/canonical/StateBar.vue'
import TitleBar from '@/layouts/canonical/TitleBar.vue'

type PrimaryTileActionFn = (() => void) | null

const primaryTileActions = {
  save: ref<PrimaryTileActionFn>(null),
  publish: ref<PrimaryTileActionFn>(null),
  unpublish: ref<PrimaryTileActionFn>(null),
  canSave: ref(false),
  canPublish: ref(false),
  isPublished: ref(false),
  isBusy: ref(false),
}

provide('primaryTileActions', primaryTileActions)
</script>
