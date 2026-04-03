<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    previewMode?: boolean;
    surfaceClass?: string;
    contained?: boolean;
}>();

const floatClass = computed(() =>
    props.contained ? 'lead-float lead-float-contained' : 'lead-float'
);

const surfaceClassName = computed(() =>
    props.contained ? 'lead-surface lead-surface-contained' : 'lead-surface'
);
</script>

<template>
    <div :class="floatClass">
        <div class="lead-container">
            <div
                v-if="!props.contained"
                class="lead-shadow"
                aria-hidden="true"
            ></div>

            <div :class="[surfaceClassName, props.surfaceClass]">
                <div class="lead-top-edge" aria-hidden="true"></div>
                <slot />
            </div>

            <template v-if="!props.contained">
                <div
                    class="lead-badge lead-badge-left"
                    aria-hidden="true"
                ></div>
                <div
                    class="lead-badge lead-badge-right"
                    aria-hidden="true"
                ></div>
            </template>
        </div>
    </div>
</template>

<style scoped>
.lead-float {
    position: relative;
    width: 100%;
    overflow: visible;
}

.lead-float-contained {
    overflow: hidden;
}

.lead-container {
    position: relative;
    width: 100%;
    max-width: 100%;
}

.lead-shadow {
    position: absolute;
    inset: 0;
    border-radius: 1.5rem;
    background: rgba(15, 23, 42, 0.22);
    filter: blur(30px);
    transform: translate3d(14px, 18px, 0) scale(0.98);
    opacity: 0.38;
    pointer-events: none;
}

.lead-surface {
    position: relative;
    z-index: 1;
    width: 100%;
    border-radius: 1.5rem;
    background: rgba(255, 255, 255, 0.96);
    overflow: hidden;
}

.lead-surface-contained {
    box-shadow: 0 16px 36px rgba(15, 23, 42, 0.08);
}

.lead-top-edge {
    position: absolute;
    inset: 0 0 auto 0;
    height: 26px;
    background: linear-gradient(
        to bottom,
        rgba(255, 255, 255, 0.7),
        transparent
    );
    pointer-events: none;
}

.lead-badge {
    position: absolute;
    bottom: -10px;
    height: 12px;
    border-radius: 9999px;
    background: linear-gradient(
        135deg,
        rgba(226, 232, 240, 0.9),
        rgba(241, 245, 249, 0.25)
    );
    box-shadow: 0 10px 18px -14px rgba(15, 23, 42, 0.35);
    opacity: 0.9;
    pointer-events: none;
}

.lead-badge-left {
    left: 28px;
    width: 42px;
}

.lead-badge-right {
    right: 28px;
    width: 26px;
}
</style>
