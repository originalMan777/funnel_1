<script setup lang="ts">
import { computed, ref } from 'vue';
import TrackedLink from '@/components/analytics/TrackedLink.vue';
import LeadAccentBadge from '@/components/public/lead/LeadAccentBadge.vue';
import { useAnalyticsImpression } from '@/composables/useAnalyticsImpression';
import { trackEvent } from '@/lib/analytics';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

const props = defineProps<{
    model: LeadBlockRenderModel;
    previewMode?: boolean;
}>();

const surfaceRef = ref<Element | null>(null);

useAnalyticsImpression(
    surfaceRef,
    () => ({
        eventKey: 'lead_box.impression',
        leadBoxId: props.model.leadBoxId,
        surfaceKey: `lead_box.${props.model.context.slotKey}`,
        properties: {
            source: 'service_lead_block',
        },
    }),
    { threshold: 0.4 },
);

const eyebrow = computed(() => {
    return (props.model.content?.eyebrow as string) || 'Service';
});

const reassurance = computed(() => {
    return (props.model.content?.reassurance_text as string) || '';
});

const consultHref = computed(() => {
    if (props.previewMode) {
        return '#';
    }

    return route('consultation', {
        source: 'service_lead_block',
        lead_box_id: props.model.leadBoxId,
        lead_slot_key: props.model.context?.slotKey,
        page_key: props.model.context?.pageKey,
    });
});
</script>

<template>
    <section
        ref="surfaceRef"
        class="relative overflow-hidden rounded-[32px] bg-white px-6 py-10 shadow-[0_30px_80px_rgba(15,23,42,0.10)] ring-1 ring-black/5 md:px-10 md:py-14"
    >
        <LeadAccentBadge />

        <div class="pointer-events-none absolute inset-0">
            <div class="absolute inset-x-10 top-0 h-px bg-black/8"></div>
            <div
                class="absolute bottom-6 left-10 h-3 w-14 rounded-full bg-slate-200/70"
            ></div>
            <div
                class="absolute right-10 bottom-6 h-3 w-14 rounded-full bg-slate-200/70"
            ></div>
            <div
                class="absolute top-1/2 left-1/2 h-44 w-44 -translate-x-1/2 -translate-y-1/2 rounded-full bg-slate-200/35 blur-3xl"
            ></div>
        </div>

        <div
            class="relative z-10 mx-auto flex max-w-3xl flex-col items-center text-center"
        >
            <p
                class="text-[11px] font-semibold tracking-[0.22em] text-gray-500 uppercase"
            >
                {{ eyebrow }}
            </p>

            <h3
                class="mt-3 text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
            >
                {{ model.title }}
            </h3>

            <p
                v-if="model.shortText"
                class="mt-4 max-w-2xl text-base leading-relaxed text-gray-600 md:text-lg"
            >
                {{ model.shortText }}
            </p>

            <div class="mt-8">
                <TrackedLink
                    v-if="!previewMode"
                    cta-key="lead_box.service.primary"
                    :href="consultHref"
                    :surface-key="`lead_box.${model.context.slotKey}`"
                    :lead-box-id="model.leadBoxId"
                    class="inline-flex items-center justify-center rounded-2xl bg-[#1d3f1f] px-6 py-3.5 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(29,63,31,0.18)] transition hover:bg-[#173319]"
                    @click="trackEvent({
                        eventKey: 'lead_box.click',
                        leadBoxId: model.leadBoxId,
                        surfaceKey: `lead_box.${model.context.slotKey}`,
                        properties: { source: 'service_lead_block' },
                    })"
                >
                    {{ model.buttonText || 'Book a consultation' }}
                </TrackedLink>

                <button
                    v-else
                    type="button"
                    class="inline-flex cursor-not-allowed items-center justify-center rounded-2xl bg-[#1d3f1f] px-6 py-3.5 text-sm font-semibold text-white opacity-70"
                    disabled
                >
                    {{ model.buttonText || 'Book a consultation' }}
                </button>
            </div>

            <p
                v-if="reassurance"
                class="mt-4 text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
            >
                {{ reassurance }}
            </p>
        </div>
    </section>
</template>
