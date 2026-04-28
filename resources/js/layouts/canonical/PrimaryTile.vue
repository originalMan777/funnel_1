<script setup lang="ts">
import { computed, provide, ref } from 'vue'
import { usePage } from '@inertiajs/vue3'
import ContextualRightSidebar from '@/layouts/canonical/ContextualRightSidebar.vue'
import StateBar from '@/layouts/canonical/StateBar.vue'
import TitleBar from '@/layouts/canonical/TitleBar.vue'

type PrimaryTileActionFn = (() => void) | null

const page = usePage<any>()

const titleBarEnabled = computed(() => page.props?.ui?.titleBar?.enabled !== false)
const rightSidebarEnabled = computed(() => page.props?.ui?.rightSidebar?.enabled !== false)
const stateBarEnabled = computed(() => page.props?.ui?.stateBar?.enabled !== false)

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

<template>
  <div class="primary-tile relative flex min-h-0 w-full flex-1 flex-col border-b border-gray-200">
    <TitleBar v-if="titleBarEnabled" />

    <div class="flex min-h-0 flex-1">
      <div class="min-w-0 flex-1 overflow-y-auto">
        <slot />
      </div>

      <ContextualRightSidebar v-if="rightSidebarEnabled" />
    </div>

    <StateBar v-if="stateBarEnabled" />
  </div>
</template>
