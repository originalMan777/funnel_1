<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import ResourceWorkspace from '@/pages/Admin/LeadBoxes/Resource/Workspace.vue';
import ServiceWorkspace from '@/pages/Admin/LeadBoxes/Service/Workspace.vue';
import OfferWorkspace from '@/pages/Admin/LeadBoxes/Offer/Workspace.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

type LeadBoxType = 'resource' | 'service' | 'offer';

const props = defineProps<{
    statuses: string[];
    icons: string[];
    visualPresets: string[];
}>();

const selectedType = ref<LeadBoxType>('resource');

const resourceForm = useForm({
    status: 'draft',
    internal_name: '',
    title: '',
    short_text: '',
    button_text: 'Get the resource',
    icon_key: 'book-open',
    visual_preset: 'default',
});

const serviceForm = useForm({
    status: 'draft',
    internal_name: '',
    title: '',
    short_text: '',
    button_text: 'Request a call',
    cta_line: 'Quick question? Let\'s get you answers.',
    reassurance_text: 'No pressure. No spam.',
    value_points: [
        { icon_key: 'shield-check', line: 'Clear guidance' },
        { icon_key: 'clock', line: 'Fast response' },
        { icon_key: 'message-square', line: 'Practical next steps' },
    ],
});

const offerForm = useForm({
    status: props.statuses?.[0] ?? 'draft',
    internal_name: '',
    title: '',
    breakdown_line_1: '',
    breakdown_line_2: '',
    button_text: '',
    cta_line: '',
    reassurance_text: '',
    value_points: [
        { icon_key: props.icons?.[0] ?? 'shield-check', line: '' },
        { icon_key: props.icons?.[1] ?? 'clock', line: '' },
        { icon_key: props.icons?.[2] ?? 'message-square', line: '' },
    ],
});

const submitResource = () => {
    resourceForm.post(route('admin.lead-boxes.resource.store'), {
        preserveScroll: true,
    });
};

const submitService = () => {
    serviceForm.post(route('admin.lead-boxes.service.store'), {
        preserveScroll: true,
    });
};

const submitOffer = () => {
    offerForm.post(route('admin.lead-boxes.offer.store'), {
        preserveScroll: true,
    });
};

const resourcePreviewModel = computed<LeadBlockRenderModel>(() => ({
    leadBoxId: 0,
    type: 'resource',
    title: resourceForm.title,
    shortText: resourceForm.short_text || null,
    buttonText: resourceForm.button_text || null,
    iconKey: resourceForm.icon_key || null,
    content: { visual_preset: resourceForm.visual_preset },
    context: { slotKey: 'home_intro', pageKey: 'home' },
}));

const servicePreviewModel = computed<LeadBlockRenderModel>(() => ({
    leadBoxId: 0,
    type: 'service',
    title: serviceForm.title,
    shortText: serviceForm.short_text || null,
    buttonText: serviceForm.button_text || null,
    iconKey: null,
    content: {
        cta_line: serviceForm.cta_line,
        reassurance_text: serviceForm.reassurance_text || null,
        value_points: serviceForm.value_points,
    },
    context: { slotKey: 'home_mid', pageKey: 'home' },
}));
</script>

<template>
    <Head title="Create Lead Box" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                Lead Boxes
                            </p>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                                Create Lead Box
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Choose a lead box type, then complete the matching workspace below.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.index')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Back to Lead Boxes
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="border-b border-gray-200 px-6 py-5">
                    <div class="flex flex-wrap gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm transition"
                            :class="selectedType === 'resource'
                                ? 'bg-gray-900 text-white'
                                : 'border border-gray-200 bg-white text-gray-900 hover:bg-gray-50'"
                            @click="selectedType = 'resource'"
                        >
                            Resource
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm transition"
                            :class="selectedType === 'service'
                                ? 'bg-gray-900 text-white'
                                : 'border border-gray-200 bg-white text-gray-900 hover:bg-gray-50'"
                            @click="selectedType = 'service'"
                        >
                            Service
                        </button>

                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold shadow-sm transition"
                            :class="selectedType === 'offer'
                                ? 'bg-gray-900 text-white'
                                : 'border border-gray-200 bg-white text-gray-900 hover:bg-gray-50'"
                            @click="selectedType = 'offer'"
                        >
                            Offer
                        </button>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <ResourceWorkspace
                        v-if="selectedType === 'resource'"
                        :form="resourceForm"
                        :statuses="props.statuses"
                        :visual-presets="props.visualPresets"
                        :preview-model="resourcePreviewModel"
                        :submit="submitResource"
                    />

                    <ServiceWorkspace
                        v-else-if="selectedType === 'service'"
                        :form="serviceForm"
                        :statuses="props.statuses"
                        :icons="props.icons"
                        :preview-model="servicePreviewModel"
                        :submit="submitService"
                    />

                    <OfferWorkspace
                        v-else-if="selectedType === 'offer'"
                        :is-edit="false"
                        :form="offerForm"
                        :statuses="props.statuses"
                        :icons="props.icons"
                        :submit="submitOffer"
                    />

                    <div
                        v-else
                        class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 px-6 py-10 text-sm text-gray-600"
                    >
                        This type is not wired in this pass.
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
