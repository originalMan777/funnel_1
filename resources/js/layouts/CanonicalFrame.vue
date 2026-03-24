<script setup lang="ts">
import { ref } from 'vue';
import PrimaryTile from '@/layouts/canonical/PrimaryTile.vue';
import QBar from '@/layouts/canonical/QBar.vue';

const qbarOpen = ref(false);
</script>

<template>
    <div class="canonical-frame flex h-full w-full flex-col bg-transparent">
        <div class="flex min-h-0 flex-1 flex-col gap-2">
            <div
                class="min-h-0 overflow-auto rounded-xl border border-gray-200 bg-white shadow-sm"
                :style="{ flex: qbarOpen ? '0 0 78%' : '1 1 auto' }"
            >
                <PrimaryTile class="h-full min-h-0">
                    <slot />
                </PrimaryTile>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-all duration-200"
                :style="{ flex: qbarOpen ? '0 0 22%' : '0 0 56px' }"
            >
                <QBar :open="qbarOpen" @toggle="qbarOpen = !qbarOpen" />
            </div>
        </div>
    </div>
</template>
