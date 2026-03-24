<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue';
import FrontTopNavA from '@/layouts/front/FrontTopNavA.vue';
import FrontTopNavB from '@/layouts/front/FrontTopNavB.vue';
import FrontFooter from '@/layouts/front/FrontFooter.vue';
import PublicPopupModal from '@/components/public/PublicPopupModal.vue';

function handlePlay(e: Event) {
    const target = e.target as unknown;

    if (!(target instanceof HTMLMediaElement)) return;

    const medias = Array.from(
        document.querySelectorAll('audio, video'),
    ) as HTMLMediaElement[];
    for (const m of medias) {
        if (m !== target && !m.paused) {
            m.pause();
        }
    }
}

onMounted(() => {
    document.addEventListener('play', handlePlay, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('play', handlePlay, true);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50 text-gray-900">
        <FrontTopNavA />
        <FrontTopNavB />

        <main class="mx-auto max-w-7xl px-4 py-10">
            <slot />
        </main>

        <FrontFooter />
        <PublicPopupModal />
    </div>
</template>
