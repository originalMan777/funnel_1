<script setup lang="ts">
import { computed, ref } from 'vue';
import TrackedLink from '@/components/analytics/TrackedLink.vue';
import LeadAccentBadge from '@/components/public/lead/LeadAccentBadge.vue';
import LeadFloatingCard from '@/components/public/lead/LeadFloatingCard.vue';
import { useAnalyticsImpression } from '@/composables/useAnalyticsImpression';
import { trackEvent } from '@/lib/analytics';
import { resolveLeadIcon } from '@/lib/leadIcons';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

type ValuePoint = { icon_key: string; line: string };

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
            source: 'offer_lead_block',
        },
    }),
    { threshold: 0.4 },
);

const breakdown1 = computed(() => props.model.shortText || '');
const breakdown2 = computed(
    () => (props.model.content?.breakdown_line_2 as string) || '',
);

const valuePoints = computed<ValuePoint[]>(() => {
    const vp = (props.model.content?.value_points ?? []) as ValuePoint[];

    return Array.isArray(vp) ? vp.slice(0, 3) : [];
});

const ctaLine = computed(
    () =>
        (props.model.content?.cta_line as string) || 'Ready to lock in a plan?',
);
const reassurance = computed(
    () => (props.model.content?.reassurance_text as string) || '',
);

const iconFor = (key: string) => resolveLeadIcon(key);

const consultHref = computed(() => {
    if (props.previewMode) {
        return '#';
    }

    return route('consultation', {
        source: 'offer_lead_block',
        lead_box_id: props.model.leadBoxId,
        lead_slot_key: props.model.context?.slotKey,
        page_key: props.model.context?.pageKey,
    });
});
</script>

<template>
    <LeadFloatingCard :preview-mode="previewMode" surface-class="p-4 md:p-6">
        <LeadAccentBadge />

        <div class="pointer-events-none absolute inset-0">
            <div
                class="absolute top-8 -left-24 h-72 w-72 rounded-full bg-amber-200/35 blur-3xl"
            ></div>
            <div
                class="absolute -right-24 bottom-10 h-80 w-80 rounded-full bg-rose-200/30 blur-3xl"
            ></div>
        </div>

        <div
            ref="surfaceRef"
            class="relative z-10 grid gap-4 md:grid-cols-[3fr_2fr] md:items-stretch"
        >
            <!-- LEFT 60% -->
            <div
                class="flex flex-col justify-between gap-6 rounded-3xl bg-gradient-to-br from-stone-50 to-white p-5 ring-1 ring-black/5 md:p-6"
            >
                <div class="space-y-3">
                    <p
                        class="text-[11px] font-semibold tracking-[0.18em] text-stone-500 uppercase transition duration-500 ease-out"
                    >
                        Offer
                    </p>

                    <h3
                        class="text-2xl font-semibold tracking-tight text-gray-900 transition duration-500 ease-out md:text-3xl"
                    >
                        {{ model.title }}
                    </h3>

                    <div class="space-y-2">
                        <p
                            class="text-sm leading-relaxed font-medium text-gray-700 transition duration-500 ease-out"
                        >
                            {{
                                breakdown1 ||
                                'Add breakdown line 1 in the Offer editor.'
                            }}
                        </p>
                        <p
                            class="text-sm leading-relaxed text-gray-600 transition duration-500 ease-out"
                        >
                            {{
                                breakdown2 ||
                                'Add breakdown line 2 in the Offer editor.'
                            }}
                        </p>
                    </div>
                </div>

                <div class="rounded-2xl bg-white/70 p-4 ring-1 ring-black/5">
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div
                            v-for="(vp, idx) in valuePoints"
                            :key="idx"
                            class="flex items-start gap-3 rounded-2xl bg-white p-3 shadow-sm ring-1 ring-black/5 transition duration-500 ease-out"
                        >
                            <div
                                class="mt-0.5 grid h-9 w-9 place-items-center rounded-xl bg-stone-50 ring-1 ring-black/5"
                            >
                                <component
                                    v-if="iconFor(vp.icon_key)"
                                    :is="iconFor(vp.icon_key)"
                                    class="h-4 w-4 text-gray-900"
                                />
                                <div
                                    v-else
                                    class="h-4 w-4 rounded bg-gray-200"
                                ></div>
                            </div>
                            <p
                                class="text-sm leading-snug font-semibold text-gray-900"
                            >
                                {{ vp.line }}
                            </p>
                        </div>
                    </div>

                    <p
                        v-if="!valuePoints.length"
                        class="mt-2 text-sm text-gray-600"
                    >
                        Add 3 value points in the Offer Lead Box editor.
                    </p>
                </div>
            </div>

            <!-- RIGHT 40% -->
            <div
                class="relative overflow-hidden rounded-3xl bg-gray-900 p-5 text-white shadow-sm ring-1 ring-black/10 md:p-6"
            >
                <div class="pointer-events-none absolute inset-0 opacity-70">
                    <div
                        class="absolute top-8 -left-10 h-44 w-44 rounded-full bg-white/10 blur-3xl"
                    ></div>
                    <div
                        class="absolute -right-12 bottom-10 h-52 w-52 rounded-full bg-white/10 blur-3xl"
                    ></div>
                </div>

                <div
                    class="relative z-10 flex h-full flex-col justify-between gap-5"
                >
                    <div>
                        <p
                            class="text-sm leading-snug font-semibold transition duration-500 ease-out"
                        >
                            {{ ctaLine }}
                        </p>

                        <p
                            v-if="model.buttonText"
                            class="mt-2 text-xs font-semibold tracking-[0.18em] text-white/70 uppercase transition duration-500 ease-out"
                        >
                            Limited availability
                        </p>
                    </div>

                    <div class="space-y-3">
                        <TrackedLink
                            v-if="!previewMode"
                            cta-key="lead_box.offer.primary"
                            :href="consultHref"
                            :surface-key="`lead_box.${model.context.slotKey}`"
                            :lead-box-id="model.leadBoxId"
                            class="inline-flex w-full items-center justify-center rounded-xl bg-[#a59064] px-4 py-3 text-sm font-semibold text-[#102514] shadow-[0_16px_30px_rgba(165,144,100,0.26)] transition hover:bg-[#b39d72]"
                            @click="trackEvent({
                                eventKey: 'lead_box.click',
                                leadBoxId: model.leadBoxId,
                                surfaceKey: `lead_box.${model.context.slotKey}`,
                                properties: { source: 'offer_lead_block' },
                            })"
                        >
                            {{ model.buttonText || 'Book a consultation' }}
                        </TrackedLink>

                        <button
                            v-else
                            type="button"
                            class="inline-flex w-full cursor-not-allowed items-center justify-center rounded-xl bg-[#a59064]/85 px-4 py-3 text-sm font-semibold text-[#102514] opacity-70"
                            disabled
                        >
                            {{ model.buttonText || 'Book a consultation' }}
                        </button>

                        <p
                            v-if="reassurance"
                            class="text-[11px] font-semibold tracking-[0.18em] text-white/70 uppercase"
                        >
                            {{ reassurance }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </LeadFloatingCard>
</template>
