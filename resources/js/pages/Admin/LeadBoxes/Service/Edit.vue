<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import Workspace from '@/pages/Admin/LeadBoxes/Service/Workspace.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

type ValuePoint = { icon_key: string; line: string };

type LeadBoxPayload = {
    id: number;
    type: 'service';
    status: 'draft' | 'active' | 'inactive';
    internal_name: string;
    title: string;
    short_text: string | null;
    button_text: string | null;
    cta_line: string;
    reassurance_text: string | null;
    value_points: ValuePoint[];
};

const props = defineProps<{
    mode: 'create' | 'edit';
    leadBox: LeadBoxPayload | null;
    statuses: string[];
    icons: string[];
}>();

const form = useForm({
    status: props.leadBox?.status ?? 'draft',
    internal_name: props.leadBox?.internal_name ?? '',
    title: props.leadBox?.title ?? '',
    short_text: props.leadBox?.short_text ?? '',
    button_text: props.leadBox?.button_text ?? 'Request a call',
    cta_line: props.leadBox?.cta_line ?? 'Quick question? Let\'s get you answers.',
    reassurance_text: props.leadBox?.reassurance_text ?? 'No pressure. No spam.',
    value_points: (props.leadBox?.value_points ?? [
        { icon_key: 'shield-check', line: 'Clear guidance' },
        { icon_key: 'clock', line: 'Fast response' },
        { icon_key: 'message-square', line: 'Practical next steps' },
    ]) as ValuePoint[],
});

const isEdit = computed(() => props.mode === 'edit' && !!props.leadBox);

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.lead-boxes.service.update', props.leadBox!.id), { preserveScroll: true });
        return;
    }

    form.post(route('admin.lead-boxes.service.store'), { preserveScroll: true });
};

const previewModel = computed<LeadBlockRenderModel>(() => ({
    leadBoxId: props.leadBox?.id ?? 0,
    type: 'service',
    title: form.title,
    shortText: form.short_text || null,
    buttonText: form.button_text || null,
    iconKey: null,
    content: {
        cta_line: form.cta_line,
        reassurance_text: form.reassurance_text || null,
        value_points: form.value_points,
    },
    context: { slotKey: 'home_mid', pageKey: 'home' },
}));
</script>

<template>
    <Head :title="isEdit ? 'Edit Service Lead Box' : 'Create Service Lead Box'" />

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
                                {{ isEdit ? 'Edit Service Lead Box' : 'Create Service Lead Box' }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Service lead blocks promote direct inquiry with a persuasive 35 / 30 / 35 layout and a full form.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.resource.create')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Resource
                            </Link>
                            <Link
                                :href="route('admin.lead-boxes.service.create')"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm"
                            >
                                Service
                            </Link>
                            <Link
                                :href="route('admin.lead-boxes.offer.create')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Offer
                            </Link>
                            <Link
                                :href="route('admin.lead-boxes.index')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Back to Lead Boxes
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <Workspace
                        :form="form"
                        :statuses="props.statuses"
                        :icons="props.icons"
                        :preview-model="previewModel"
                        :submit="submit"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
