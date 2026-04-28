<script setup>
import { computed } from 'vue'
import { Head, Link, router, useForm } from '@inertiajs/vue3'
import AdminLayout from '@/AppLayouts/AdminLayout.vue'
import Workspace from '@/pages/Admin/LeadBoxes/Offer/Workspace.vue'

const props = defineProps({
    mode: {
        type: String,
        required: true,
    },
    leadBox: {
        type: Object,
        default: null,
    },
    statuses: {
        type: Array,
        required: true,
    },
    icons: {
        type: Array,
        required: true,
    },
})

const isEdit = computed(() => props.mode === 'edit' && !!props.leadBox)

const defaultValuePoints = [
    { icon_key: props.icons?.[0] ?? 'shield-check', line: '' },
    { icon_key: props.icons?.[1] ?? 'clock', line: '' },
    { icon_key: props.icons?.[2] ?? 'message-square', line: '' },
]

const form = useForm({
    status: props.leadBox?.status ?? props.statuses?.[0] ?? 'draft',
    internal_name: props.leadBox?.internal_name ?? '',
    title: props.leadBox?.title ?? '',
    breakdown_line_1: props.leadBox?.breakdown_line_1 ?? '',
    breakdown_line_2: props.leadBox?.breakdown_line_2 ?? '',
    button_text: props.leadBox?.button_text ?? '',
    cta_line: props.leadBox?.cta_line ?? '',
    reassurance_text: props.leadBox?.reassurance_text ?? '',
    value_points: props.leadBox?.value_points?.length
        ? props.leadBox.value_points.map((point) => ({
              icon_key: point.icon_key ?? (props.icons?.[0] ?? 'shield-check'),
              line: point.line ?? '',
          }))
        : defaultValuePoints,
})

function duplicateLeadBox() {
    if (!isEdit.value || !props.leadBox) {
        return
    }

    if (!window.confirm('Duplicate this lead box as a draft?')) {
        return
    }

    router.post(route('admin.lead-boxes.duplicate', props.leadBox.id), {}, { preserveScroll: true })
}

function submit() {
    if (isEdit.value) {
        form.put(route('admin.lead-boxes.offer.update', props.leadBox.id), {
            preserveScroll: true,
        })
        return
    }

    form.post(route('admin.lead-boxes.offer.store'), {
        preserveScroll: true,
    })
}
</script>

<template>
    <Head :title="isEdit ? 'Edit Offer Lead Box' : 'Create Offer Lead Box'" />

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
                                {{ isEdit ? 'Edit Offer Lead Box' : 'Create Offer Lead Box' }}
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Offer lead blocks present a concise value breakdown with a direct next-step CTA.
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
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Service
                            </Link>

                            <Link
                                :href="route('admin.lead-boxes.offer.create')"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm"
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
                        :is-edit="isEdit"
                        :form="form"
                        :statuses="statuses"
                        :icons="icons"
                        :submit="submit"
                    />
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
