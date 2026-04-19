<script setup lang="ts">
import { computed, onMounted, onBeforeUnmount } from 'vue';
import { usePage } from '@inertiajs/vue3';
import FrontTopNavA from '@/layouts/front/FrontTopNavA.vue';
import FrontTopNavB from '@/layouts/front/FrontTopNavB.vue';
import FrontFooter from '@/layouts/front/FrontFooter.vue';
import PublicPopupModal from '@/components/public/PublicPopupModal.vue';

const page = usePage<any>();
const siteContent = computed(() => page.props.siteContent ?? {});
const developmentBanner = computed(
    () => siteContent.value?.layout?.development_banner
        ?? 'This website is currently under development. Some pages and features are still being finalized.',
);

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
    <div class="min-h-screen overflow-x-hidden bg-gray-50 text-gray-900">
        <div class="border-b border-amber-200 bg-amber-50">
            <div class="mx-auto max-w-7xl px-4 py-2 text-center text-sm font-medium text-amber-900">
                {{ developmentBanner }}
            </div>
        </div>

        <FrontTopNavA />
        <FrontTopNavB />

        <main class="w-full">
            <slot />
        </main>

        <FrontFooter />
        <PublicPopupModal />
    </div>
</template>
