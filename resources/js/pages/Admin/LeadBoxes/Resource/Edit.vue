<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import Workspace from '@/pages/Admin/LeadBoxes/Resource/Workspace.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

type LeadBoxPayload = {
    id: number;
    type: 'resource';
    status: 'draft' | 'active' | 'inactive';
    internal_name: string;
    title: string;
    short_text: string | null;
    button_text: string | null;
    icon_key: string | null;
    visual_preset: string;
};

const props = defineProps<{
    mode: 'create' | 'edit';
    leadBox: LeadBoxPayload | null;
    statuses: string[];
    visualPresets: string[];
}>();

const form = useForm({
    status: props.leadBox?.status ?? 'draft',
    internal_name: props.leadBox?.internal_name ?? '',
    title: props.leadBox?.title ?? '',
    short_text: props.leadBox?.short_text ?? '',
    button_text: props.leadBox?.button_text ?? 'Get the resource',
    icon_key: props.leadBox?.icon_key ?? 'book-open',
    visual_preset: props.leadBox?.visual_preset ?? 'default',
});

const isEdit = computed(() => props.mode === 'edit' && !!props.leadBox);

const duplicateLeadBox = () => {
    if (!isEdit.value || !props.leadBox) {
        return;
    }

    if (!window.confirm('Duplicate this lead box as a draft?')) {
        return;
    }

    router.post(route('admin.lead-boxes.duplicate', props.leadBox.id), {}, { preserveScroll: true });
};

const submit = () => {
    if (isEdit.value) {
        form.put(route('admin.lead-boxes.resource.update', props.leadBox!.id), {
            preserveScroll: true,
        });
        return;
    }

    form.post(route('admin.lead-boxes.resource.store'), {
        preserveScroll: true,
    });
};

const previewModel = computed<LeadBlockRenderModel>(() => ({
    leadBoxId: props.leadBox?.id ?? 0,
    type: 'resource',
    title: form.title,
    shortText: form.short_text || null,
    buttonText: form.button_text || null,
    iconKey: form.icon_key || null,
    content: { visual_preset: form.visual_preset },
    context: { slotKey: 'home_intro', pageKey: 'home' },
}));
</script>

<template>
    <Head :title="isEdit ? 'Edit Resource Lead Box' : 'Create Resource Lead Box'" />

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
                                {{ isEdit ? 'Edit Resource Lead Box' : 'Create Resource Lead Box' }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Resource lead blocks capture first name + email with a light-friction flow.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.resource.create')"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm"
                            >
                                Resource
                            </Link>
                            <Link
                                :href="route('admin.lead-boxes.service.create')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Service
                            </Link>
                            <Link
                                :href="route('admin.lead-boxes.offer.create')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Offer
                            </Link>
                            <button
                                v-if="isEdit"
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                @click="duplicateLeadBox"
                            >
                                Duplicate
                            </button>
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
                        :visual-presets="props.visualPresets"
                        :preview-model="previewModel"
                        :submit="submit"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
