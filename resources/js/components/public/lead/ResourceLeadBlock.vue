<script setup lang="ts">
import { computed, ref } from 'vue';
import TrackedButton from '@/components/analytics/TrackedButton.vue';
import LeadAccentBadge from '@/components/public/lead/LeadAccentBadge.vue';
import { useAnalyticsImpression } from '@/composables/useAnalyticsImpression';
import { useLeadCaptureForm } from '@/composables/useLeadCaptureForm';
import { trackEvent } from '@/lib/analytics';
import { resolveLeadIcon } from '@/lib/leadIcons';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

const props = defineProps<{
    model: LeadBlockRenderModel;
    previewMode?: boolean;
}>();

const surfaceRef = ref<Element | null>(null);

const { form, isFormOpen, isSuccess, canSubmit, open, close, submit } =
    useLeadCaptureForm(props.model);

useAnalyticsImpression(
    surfaceRef,
    () => ({
        eventKey: 'lead_box.impression',
        leadBoxId: props.model.leadBoxId,
        surfaceKey: `lead_box.${props.model.context.slotKey}`,
        properties: {
            source: 'resource_lead_block',
        },
    }),
    { threshold: 0.4 },
);

const iconComponent = computed(() => resolveLeadIcon(props.model.iconKey));

const success = computed(() => isSuccess.value);

const eyebrow = computed(() => {
    return (props.model.content?.eyebrow as string) || 'Resource';
});

const microcopy = computed(() => {
    return (
        (props.model.content?.microcopy as string) ||
        'No spam. Unsubscribe anytime.'
    );
});

const resourceTitle = computed(() => {
    return (props.model.content?.resource_title as string) || props.model.title;
});

const start = () => {
    if (props.previewMode) {
        return;
    }

    trackEvent({
        eventKey: 'lead_box.click',
        leadBoxId: props.model.leadBoxId,
        surfaceKey: `lead_box.${props.model.context.slotKey}`,
        properties: {
            source: 'resource_lead_block',
        },
    });

    open();
};

const doSubmit = () => {
    if (props.previewMode) {
        return;
    }

    submit();
};

const closeForm = () => {
    if (props.previewMode) {
        return;
    }

    close();
};
</script>

<template>
    <section
        ref="surfaceRef"
        class="relative overflow-hidden rounded-[32px] bg-white px-6 py-8 shadow-[0_30px_80px_rgba(15,23,42,0.10)] ring-1 ring-black/5 md:px-8 md:py-10"
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
                class="absolute top-10 right-10 h-44 w-44 rounded-full bg-slate-200/25 blur-3xl"
            ></div>
        </div>

       <div
  class="relative z-10 grid gap-6 md:grid-cols-2 md:items-center"
>
            <div class="space-y-6">
                <div class="space-y-3">
                    <p
                        class="text-[11px] font-semibold tracking-[0.22em] text-gray-500 uppercase"
                    >
                        {{ eyebrow }}
                    </p>

                    <h3
                        class="text-3xl font-semibold tracking-tight text-gray-900 md:text-4xl"
                    >
                        {{ model.title }}
                    </h3>

                    <p
                        v-if="model.shortText"
                        class="max-w-2xl text-base leading-relaxed text-gray-600 md:text-lg"
                    >
                        {{ model.shortText }}
                    </p>
                </div>

                <div class="flex items-center gap-4">
                    <div
                        class="grid h-16 w-16 shrink-0 place-items-center rounded-2xl bg-gray-50 shadow-sm ring-1 ring-black/5"
                    >
                        <component
                            v-if="iconComponent"
                            :is="iconComponent"
                            class="h-7 w-7 text-gray-900"
                        />
                        <div v-else class="h-7 w-7 rounded bg-gray-200"></div>
                    </div>

                    <div class="min-w-0">
                        <p
                            class="text-sm font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            {{ resourceTitle }}
                        </p>
                        <p class="mt-1 text-sm leading-relaxed text-gray-600">
                            Quick, simple, and easy to request.
                        </p>
                    </div>
                </div>
            </div>

            <div class="md:w-full md:max-w-md md:justify-self-end">
                <div
                    v-if="success"
                    class="rounded-2xl bg-gray-50 p-6 ring-1 ring-black/5"
                >
                    <p class="text-sm font-semibold text-gray-900">
                        You're all set.
                    </p>
                    <p class="mt-2 text-sm leading-relaxed text-gray-600">
                        Thanks — we’ll follow up shortly with your resource.
                    </p>
                </div>

                <div v-else>
                    <TrackedButton
                        v-if="!isFormOpen"
                        cta-key="lead_box.resource.primary"
                        :surface-key="`lead_box.${model.context.slotKey}`"
                        :lead-box-id="model.leadBoxId"
                        class="inline-flex items-center justify-center rounded-2xl bg-[#1d3f1f] px-6 py-3.5 text-sm font-semibold text-white shadow-[0_16px_32px_rgba(29,63,31,0.18)] transition hover:bg-[#173319]"
                        @click="start"
                    >
                        {{ model.buttonText || 'Get the resource' }}
                    </TrackedButton>

                    <div
                        v-else
                        class="rounded-2xl bg-gray-50 p-6 ring-1 ring-black/5"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <p class="text-sm font-semibold text-gray-900">
                                Where should we send it?
                            </p>

                            <button
                                type="button"
                                class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase hover:text-gray-700"
                                @click="closeForm"
                            >
                                Close
                            </button>
                        </div>

                        <form
                            class="mt-4 grid gap-4"
                            @submit.prevent="doSubmit"
                        >
                            <div>
                                <label
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    First name
                                </label>
                                <input
                                    v-model="form.first_name"
                                    type="text"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 ring-0 outline-none focus:border-gray-400"
                                    :disabled="previewMode"
                                />
                                <p
                                    v-if="form.errors.first_name"
                                    class="mt-2 text-xs text-red-600"
                                >
                                    {{ form.errors.first_name }}
                                </p>
                            </div>

                            <div>
                                <label
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Email
                                </label>
                                <input
                                    v-model="form.email"
                                    type="email"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900 ring-0 outline-none focus:border-gray-400"
                                    :disabled="previewMode"
                                />
                                <p
                                    v-if="form.errors.email"
                                    class="mt-2 text-xs text-red-600"
                                >
                                    {{ form.errors.email }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 pt-1">
                                <button
                                    type="submit"
                                    class="inline-flex items-center justify-center rounded-xl bg-[#1d3f1f] px-5 py-3 text-sm font-semibold text-white shadow-[0_14px_28px_rgba(29,63,31,0.16)] transition hover:bg-[#173319] disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="previewMode || !canSubmit"
                                >
                                    {{
                                        form.processing
                                            ? 'Submitting…'
                                            : 'Send it to me'
                                    }}
                                </button>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-[#1d3f1f]/12 bg-[#a59064]/10 px-4 py-3 text-sm font-semibold text-[#1d3f1f] transition hover:bg-[#a59064]/16"
                                    @click="closeForm"
                                >
                                    Maybe later
                                </button>
                            </div>

                            <p class="text-sm text-gray-600">
                                {{ microcopy }}
                            </p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
</template>
