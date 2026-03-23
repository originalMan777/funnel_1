<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps<{
    previewMode?: boolean;
    surfaceClass?: string;
}>();

const root = ref<HTMLElement | null>(null);
const isVisible = ref(false);

let observer: IntersectionObserver | null = null;

onMounted(() => {
    if (props.previewMode) {
        isVisible.value = true;
        return;
    }

    if (!root.value || typeof IntersectionObserver === 'undefined') {
        isVisible.value = true;
        return;
    }

    observer = new IntersectionObserver(
        (entries) => {
            if (entries.some((e) => e.isIntersecting)) {
                isVisible.value = true;
                observer?.disconnect();
                observer = null;
            }
        },
        { threshold: 0.22 }
    );

    observer.observe(root.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
    observer = null;
});
</script>

<template>
    <div ref="root" class="lead-float" :class="isVisible ? 'is-visible' : 'is-hidden'">
        <!-- Break out of narrower page containers; align to hero width -->
        <div class="lead-breakout">
            <div class="lead-container">
                <div class="lead-shadow" aria-hidden="true"></div>

                <div class="lead-motion">
                    <div class="lead-surface" :class="props.surfaceClass">
                        <div class="lead-top-edge" aria-hidden="true"></div>
                        <slot />
                    </div>

                    <div class="lead-badge lead-badge-left" aria-hidden="true"></div>
                    <div class="lead-badge lead-badge-right" aria-hidden="true"></div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.lead-float {
    position: relative;
    overflow: visible;
}

/* Full-bleed breakout wrapper so lead blocks can use hero width consistently */
.lead-breakout {
    margin-left: calc(50% - 50vw);
    margin-right: calc(50% - 50vw);
}

.lead-container {
    position: relative;
    width: 100%;
    max-width: 80rem; /* max-w-7xl */
    margin: 0 auto;
    padding-left: 1.5rem; /* px-6 */
    padding-right: 1.5rem;
}

@media (min-width: 768px) {
    .lead-container {
        padding-left: 2.5rem; /* md:px-10 */
        padding-right: 2.5rem;
    }
}

.lead-shadow {
    position: absolute;
    inset: 0;
    border-radius: 1.5rem; /* rounded-3xl */
    background: rgba(15, 23, 42, 0.22);
    filter: blur(30px);
    transform: translate3d(14px, 18px, 0) scale(0.98);
    opacity: 0.38;
    pointer-events: none;
}

.lead-motion {
    position: relative;
    z-index: 1;
    transform-origin: 40% 65%;
    will-change: transform, opacity;
}

.lead-surface {
    position: relative;
    border-radius: 1.5rem; /* rounded-3xl */
    background: rgba(255, 255, 255, 0.96);
    overflow: hidden;
}

.lead-top-edge {
    position: absolute;
    inset: 0 0 auto 0;
    height: 26px;
    background: linear-gradient(to bottom, rgba(255, 255, 255, 0.7), transparent);
    pointer-events: none;
}

.lead-badge {
    position: absolute;
    bottom: -10px;
    height: 12px;
    border-radius: 9999px;
    background: linear-gradient(135deg, rgba(226, 232, 240, 0.9), rgba(241, 245, 249, 0.25));
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

.is-hidden .lead-motion {
    opacity: 0;
    transform: translate3d(10px, 12px, 0) rotateX(5deg) rotateY(-6deg) scale(0.985);
}

.is-hidden .lead-shadow {
    opacity: 0;
    transform: translate3d(18px, 24px, 0) scale(0.96);
}

/* On reveal, show immediately, then wait, then do 2 slow diagonal settles */
.is-visible .lead-motion {
    opacity: 1;
    transform: translate3d(8px, 10px, 0) rotateX(4deg) rotateY(-5deg) scale(0.992);
    animation: leadCardReveal 1600ms cubic-bezier(0.16, 1, 0.3, 1) 820ms both;
}

.is-visible .lead-shadow {
    opacity: 0.32;
    transform: translate3d(18px, 24px, 0) scale(0.97);
    animation: leadShadowReveal 1600ms cubic-bezier(0.16, 1, 0.3, 1) 820ms both;
}

/* Lighter hover settle (smaller than reveal) */
.is-visible .lead-container:hover .lead-motion {
    animation: leadCardHover 900ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

.is-visible .lead-container:hover .lead-shadow {
    animation: leadShadowHover 900ms cubic-bezier(0.16, 1, 0.3, 1) both;
}

@keyframes leadCardReveal {
    0% {
        opacity: 1;
        transform: translate3d(8px, 10px, 0) rotateX(4deg) rotateY(-5deg) scale(0.992);
    }
    62% {
        transform: translate3d(-8px, -7px, 0) rotateX(2deg) rotateY(-2deg) scale(1);
    }
    84% {
        transform: translate3d(3px, 2px, 0) rotateX(0.9deg) rotateY(-0.9deg) scale(1);
    }
    94% {
        transform: translate3d(-1px, -1px, 0) rotateX(0.35deg) rotateY(-0.35deg) scale(1);
    }
    100% {
        opacity: 1;
        transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(0deg) scale(1);
    }
}

@keyframes leadShadowReveal {
    0% {
        opacity: 0.32;
        transform: translate3d(18px, 24px, 0) scale(0.97);
    }
    62% {
        opacity: 0.58;
        transform: translate3d(28px, 34px, 0) scale(1);
    }
    84% {
        opacity: 0.42;
        transform: translate3d(16px, 20px, 0) scale(0.99);
    }
    94% {
        opacity: 0.4;
        transform: translate3d(15px, 19px, 0) scale(0.985);
    }
    100% {
        opacity: 0.38;
        transform: translate3d(14px, 18px, 0) scale(0.98);
    }
}

@keyframes leadCardHover {
    0% {
        transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(0deg) scale(1);
    }
    55% {
        transform: translate3d(-4px, -3px, 0) rotateX(1deg) rotateY(-1deg) scale(1);
    }
    82% {
        transform: translate3d(2px, 1px, 0) rotateX(0.35deg) rotateY(-0.35deg) scale(1);
    }
    100% {
        transform: translate3d(0, 0, 0) rotateX(0deg) rotateY(0deg) scale(1);
    }
}

@keyframes leadShadowHover {
    0% {
        transform: translate3d(14px, 18px, 0) scale(0.98);
        opacity: 0.38;
    }
    55% {
        transform: translate3d(20px, 26px, 0) scale(0.99);
        opacity: 0.46;
    }
    82% {
        transform: translate3d(16px, 20px, 0) scale(0.985);
        opacity: 0.41;
    }
    100% {
        transform: translate3d(14px, 18px, 0) scale(0.98);
        opacity: 0.38;
    }
}

@media (prefers-reduced-motion: reduce) {
    .lead-motion,
    .lead-shadow {
        animation: none !important;
        opacity: 1 !important;
        transform: none !important;
    }
}
</style>
