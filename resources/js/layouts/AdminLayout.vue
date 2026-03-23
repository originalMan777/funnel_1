<template>
  <div class="admin-layout flex h-screen w-screen overflow-hidden bg-white text-gray-900">
    <!-- Sidebar (always visible) -->
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white overflow-hidden">
      <Sidebar />
    </aside>

    <!-- Main Content -->
    <div class="flex flex-col flex-1 overflow-hidden">
      <!-- Top Nav A -->
      <TopNavA />

      <!-- Top Nav B -->
      <TopNavB />

      <!-- Canonical content -->
    <div class="flex-1 min-h-0 p-2 pb-2">
        <CanonicalFrame class="h-full">
          <slot />
        </CanonicalFrame>
      </div>


    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue'
import Sidebar from '@/layouts/canonical/LeftSidebar.vue'
import TopNavA from '@/layouts/canonical/TopNavA.vue'
import TopNavB from '@/layouts/canonical/TopNavB.vue'
import CanonicalFrame from '@/layouts/CanonicalFrame.vue'


function handlePlay(e: Event) {
  const target = e.target as HTMLElement | null
  if (!target) return

  // Only enforce for audio (you can expand to video if you want)
  if (!(target instanceof HTMLAudioElement)) return

  const audios = Array.from(document.querySelectorAll('audio')) as HTMLAudioElement[]
  for (const a of audios) {
    if (a !== target && !a.paused) {
      a.pause()
    }
  }
}

onMounted(() => {
  // capture = true is important so we catch play events early
  document.addEventListener('play', handlePlay, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('play', handlePlay, true)
})

</script>


