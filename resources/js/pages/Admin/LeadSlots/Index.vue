<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type SlotPayload = {
    id: number;
    key: string;
    label: string;
    is_enabled: boolean;
    required_type: 'resource' | 'service' | 'offer';
    assignment_lead_box_id: number | null;
    assignment_acquisition_id: number | null;
    assignment_service_id: number | null;
    assignment_acquisition_path_id: number | null;
    assignment_acquisition_path_key: string | null;
};

type LeadBoxOption = {
    id: number;
    internal_name: string;
    title: string;
    type: 'resource' | 'service' | 'offer';
};

type AcquisitionOption = {
    id: number;
    name: string;
    slug: string;
};

type ServiceOption = {
    id: number;
    name: string;
    slug: string;
    acquisition_id: number;
};

type AcquisitionPathOption = {
    id: number;
    name: string;
    path_key: string;
    acquisition_id: number;
    service_id: number | null;
};

const props = defineProps<{
    slots: SlotPayload[];
    activeLeadBoxes: LeadBoxOption[];
    acquisitions: AcquisitionOption[];
    services: ServiceOption[];
    acquisitionPaths: AcquisitionPathOption[];
}>();

const forms = props.slots.map((slot) =>
    useForm({
        is_enabled: slot.is_enabled,
        lead_box_id: slot.assignment_lead_box_id ?? null as number | null,
        acquisition_id: slot.assignment_acquisition_id ?? null as number | null,
        service_id: slot.assignment_service_id ?? null as number | null,
        acquisition_path_id: slot.assignment_acquisition_path_id ?? null as number | null,
    }),
);

const selectedLeadBoxLabel = (formIndex: number) => {
    const form = forms[formIndex];
    if (!form.lead_box_id) return '— Unassigned —';
    const found = props.activeLeadBoxes.find((box) => box.id === Number(form.lead_box_id));

    return found ? `${found.internal_name} — ${found.title}` : 'Selected';
};

const activeLeadBoxesForSlot = (slot: SlotPayload) => {
    return props.activeLeadBoxes.filter((box) => box.type === slot.required_type);
};

const selectedAcquisitionLabel = (formIndex: number) => {
    const form = forms[formIndex];
    if (!form.acquisition_id) return '— None —';
    const found = props.acquisitions.find((acquisition) => acquisition.id === form.acquisition_id);

    return found ? found.name : 'Selected';
};

const selectedServiceLabel = (formIndex: number) => {
    const form = forms[formIndex];
    if (!form.service_id) return '— None —';
    const found = props.services.find((service) => service.id === form.service_id);

    return found ? found.name : 'Selected';
};

const selectedPathLabel = (formIndex: number) => {
    const form = forms[formIndex];
    if (!form.acquisition_path_id) return '— None —';
    const found = props.acquisitionPaths.find((path) => path.id === form.acquisition_path_id);

    return found ? `${found.name} — ${found.path_key}` : 'Selected';
};

const availableServices = (formIndex: number) => {
    const form = forms[formIndex];

    if (!form.acquisition_id) {
        return props.services;
    }

    return props.services.filter((service) => service.acquisition_id === form.acquisition_id);
};

const availablePaths = (formIndex: number) => {
    const form = forms[formIndex];

    return props.acquisitionPaths.filter((path) => {
        if (form.acquisition_id && path.acquisition_id !== form.acquisition_id) {
            return false;
        }

        if (form.service_id) {
            return path.service_id === null || path.service_id === form.service_id;
        }

        return true;
    });
};

const handleAcquisitionChange = (formIndex: number) => {
    const form = forms[formIndex];

    if (
        form.service_id
        && !availableServices(formIndex).some((service) => service.id === form.service_id)
    ) {
        form.service_id = null;
    }

    if (
        form.acquisition_path_id
        && !availablePaths(formIndex).some((path) => path.id === form.acquisition_path_id)
    ) {
        form.acquisition_path_id = null;
    }
};

const handleServiceChange = (formIndex: number) => {
    const form = forms[formIndex];

    if (
        form.acquisition_path_id
        && !availablePaths(formIndex).some((path) => path.id === form.acquisition_path_id)
    ) {
        form.acquisition_path_id = null;
    }
};

const normalizeId = (value: number | string | null): number | null => {
    if (value === null || value === '') return null;

    return Number(value);
};

const submit = (slot: SlotPayload, formIndex: number) => {
    forms[formIndex]
        .transform((data) => ({
            ...data,
            lead_box_id: normalizeId(data.lead_box_id),
            acquisition_id: normalizeId(data.acquisition_id),
            service_id: normalizeId(data.service_id),
            acquisition_path_id: normalizeId(data.acquisition_path_id),
        }))
        .put(route('admin.lead-slots.update', slot.id), {
            preserveState: false,
            preserveScroll: true,
            onFinish: () => {
                forms[formIndex].transform((data) => data);
            },
        });
};
</script>

<template>
    <Head title="Lead Slots" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                Lead Blocks
                            </p>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                                Lead Slot Assignment
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                Assign any Active Lead Box to any slot. Lead Boxes can be reused across multiple slots.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.index')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Manage Lead Boxes
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <div class="space-y-6">
                        <div
                            v-for="(slot, idx) in props.slots"
                            :key="slot.id"
                            class="rounded-2xl border border-gray-200 p-6"
                        >
                            <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                                <div>
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        {{ slot.label }}
                                    </p>
                                    <h2 class="mt-2 text-xl font-semibold tracking-tight text-gray-900">
                                        {{ slot.key }}
                                    </h2>
                                </div>

                                <button
                                    type="button"
                                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                    :disabled="forms[idx].processing"
                                    @click="submit(slot, idx)"
                                >
                                    {{ forms[idx].processing ? 'Saving…' : 'Save' }}
                                </button>
                            </div>

                            <div class="mt-5 grid gap-5 md:grid-cols-2">
                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <div class="flex items-center justify-between">
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Enabled
                                        </p>

                                        <label class="inline-flex cursor-pointer items-center gap-3">
                                            <input
                                                v-model="forms[idx].is_enabled"
                                                type="checkbox"
                                                class="h-5 w-5 rounded border-gray-300 text-gray-900"
                                            />
                                            <span class="text-sm font-semibold text-gray-900">
                                                {{ forms[idx].is_enabled ? 'On' : 'Off' }}
                                            </span>
                                        </label>
                                    </div>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Disabled slots render nothing publicly even if assigned.
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Assignment
                                    </p>

                                    <select
                                        v-model="forms[idx].lead_box_id"
                                        class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                                    >
                                        <option :value="null">— Unassigned —</option>
                                        <option
                                            v-for="box in activeLeadBoxesForSlot(slot)"
                                            :key="box.id"
                                            :value="box.id"
                                        >
                                            {{ box.internal_name }} — {{ box.title }} ({{ box.type }})
                                        </option>
                                    </select>

                                    <p class="mt-3 text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Requires {{ slot.required_type }}
                                    </p>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Selected: <span class="font-semibold text-gray-900">{{ selectedLeadBoxLabel(idx) }}</span>
                                    </p>

                                    <p v-if="forms[idx].errors.lead_box_id" class="mt-3 text-xs text-red-600">
                                        {{ forms[idx].errors.lead_box_id }}
                                    </p>

                                    <p v-if="!activeLeadBoxesForSlot(slot).length" class="mt-3 text-sm text-gray-600">
                                        No Active {{ slot.required_type }} Lead Boxes yet.
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-5 md:grid-cols-3">
                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Acquisition
                                    </p>

                                    <select
                                        v-model="forms[idx].acquisition_id"
                                        class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                                        @change="handleAcquisitionChange(idx)"
                                    >
                                        <option :value="null">— None —</option>
                                        <option
                                            v-for="acquisition in props.acquisitions"
                                            :key="acquisition.id"
                                            :value="acquisition.id"
                                        >
                                            {{ acquisition.name }}
                                        </option>
                                    </select>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Selected: <span class="font-semibold text-gray-900">{{ selectedAcquisitionLabel(idx) }}</span>
                                    </p>

                                    <p v-if="forms[idx].errors.acquisition_id" class="mt-3 text-xs text-red-600">
                                        {{ forms[idx].errors.acquisition_id }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Service
                                    </p>

                                    <select
                                        v-model="forms[idx].service_id"
                                        class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                                        @change="handleServiceChange(idx)"
                                    >
                                        <option :value="null">— None —</option>
                                        <option
                                            v-for="service in availableServices(idx)"
                                            :key="service.id"
                                            :value="service.id"
                                        >
                                            {{ service.name }}
                                        </option>
                                    </select>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Selected: <span class="font-semibold text-gray-900">{{ selectedServiceLabel(idx) }}</span>
                                    </p>

                                    <p v-if="forms[idx].errors.service_id" class="mt-3 text-xs text-red-600">
                                        {{ forms[idx].errors.service_id }}
                                    </p>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-5 ring-1 ring-black/5">
                                    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                        Acquisition Path
                                    </p>

                                    <select
                                        v-model="forms[idx].acquisition_path_id"
                                        class="mt-3 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                                    >
                                        <option :value="null">— None —</option>
                                        <option
                                            v-for="path in availablePaths(idx)"
                                            :key="path.id"
                                            :value="path.id"
                                        >
                                            {{ path.name }} — {{ path.path_key }}
                                        </option>
                                    </select>

                                    <p class="mt-3 text-sm text-gray-600">
                                        Selected: <span class="font-semibold text-gray-900">{{ selectedPathLabel(idx) }}</span>
                                    </p>

                                    <p v-if="forms[idx].errors.acquisition_path_id" class="mt-3 text-xs text-red-600">
                                        {{ forms[idx].errors.acquisition_path_id }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div
                            v-if="!props.slots.length"
                            class="rounded-2xl border border-dashed border-gray-200 p-10 text-center text-sm text-gray-600"
                        >
                            No slots found.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
