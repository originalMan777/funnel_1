<template>
  <div class="admin-layout flex h-screen w-screen overflow-hidden bg-white text-gray-900">
    <aside class="w-64 shrink-0 border-r border-gray-200 bg-white overflow-hidden">
      <Sidebar />
    </aside>

    <div class="flex min-w-0 flex-1 flex-col overflow-hidden bg-gray-100">
      <div class="flex min-w-0 flex-1 flex-col overflow-hidden p-2">
        <TopNavA class="overflow-hidden rounded-t-xl" />
        <TopNavB class="overflow-hidden rounded-b-xl border-x border-gray-200" />

        <div class="min-h-0 flex-1 pt-2">
          <CanonicalFrame class="h-full">
            <slot />
          </CanonicalFrame>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onBeforeUnmount, onMounted } from 'vue'
import Sidebar from '@/layouts/canonical/LeftSidebar.vue'
import TopNavA from '@/layouts/canonical/TopNavA.vue'
import TopNavB from '@/layouts/canonical/TopNavB.vue'
import CanonicalFrame from '@/layouts/CanonicalFrame.vue'

function handlePlay(event: Event) {
  const target = event.target as EventTarget | null

  if (!(target instanceof HTMLAudioElement)) return

  const audios = Array.from(document.querySelectorAll('audio')) as HTMLAudioElement[]

  for (const audio of audios) {
    if (audio !== target && !audio.paused) {
      audio.pause()
    }
  }
}

onMounted(() => {
  document.addEventListener('play', handlePlay, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('play', handlePlay, true)
})
</script>
