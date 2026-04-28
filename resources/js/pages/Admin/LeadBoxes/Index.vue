<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { route } from 'ziggy-js';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type LeadBoxType = 'resource' | 'service' | 'offer';
type LeadBoxStatus = 'active' | 'draft' | 'archived' | string;

type LeadBox = {
    id: number;
    type: LeadBoxType | string;
    status: LeadBoxStatus;
    internal_name: string;
    title: string | null;
    updated_at: string | null;
};

const props = defineProps<{
    leadBoxes: LeadBox[];
}>();

const leadBoxes = computed(() => props.leadBoxes ?? []);
const createLeadBoxHref = route('admin.lead-boxes.create');

const typeLabel = (type: string) => type.charAt(0).toUpperCase() + type.slice(1);

const statusClasses = (status: string) => {
    if (status === 'active') {
        return 'border border-emerald-200 bg-emerald-50 text-emerald-700';
    }

    if (status === 'draft') {
        return 'border border-amber-200 bg-amber-50 text-amber-700';
    }

    if (status === 'archived') {
        return 'border border-gray-200 bg-gray-100 text-gray-600';
    }

    return 'border border-gray-200 bg-gray-50 text-gray-700';
};

const typeClasses = (type: string) => {
    if (type === 'resource') {
        return 'border border-blue-200 bg-blue-50 text-blue-700';
    }

    if (type === 'service') {
        return 'border border-teal-200 bg-teal-50 text-teal-700';
    }

    if (type === 'offer') {
        return 'border border-fuchsia-200 bg-fuchsia-50 text-fuchsia-700';
    }

    return 'border border-gray-200 bg-gray-50 text-gray-700';
};

const formattedUpdatedAt = (updatedAt: string | null) => {
    if (!updatedAt) {
        return '—';
    }

    const parsed = new Date(updatedAt);

    if (Number.isNaN(parsed.getTime())) {
        return updatedAt;
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(parsed);
};

const duplicateLeadBox = (leadBox: LeadBox) => {
    if (!window.confirm('Duplicate this lead box as a draft?')) {
        return;
    }

    router.post(route('admin.lead-boxes.duplicate', leadBox.id), {}, { preserveScroll: true });
};

const editRoute = (leadBox: LeadBox) => {
    if (leadBox.type === 'resource') {
        return route('admin.lead-boxes.resource.edit', leadBox.id);
    }

    if (leadBox.type === 'service') {
        return route('admin.lead-boxes.service.edit', leadBox.id);
    }

    if (leadBox.type === 'offer') {
        return route('admin.lead-boxes.offer.edit', leadBox.id);
    }

    return route('admin.lead-boxes.edit', leadBox.id);
};
</script>

<template>
    <Head title="Lead Boxes" />

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
                                Lead Boxes
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                View and edit the Resource, Service, and Offer lead boxes currently available in the system.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="createLeadBoxHref"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
                            >
                                Create Lead Box
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 overflow-auto p-6">
                    <div
                        v-if="leadBoxes.length === 0"
                        class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6"
                    >
                        <p class="text-sm leading-relaxed text-gray-600">
                            No lead boxes are available yet. Use the create flow to add a Resource, Service, or Offer lead box.
                        </p>
                    </div>

                    <div
                        v-else
                        class="overflow-hidden rounded-2xl border border-gray-200"
                    >
                        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
                            <p class="text-sm font-medium text-gray-700">
                                {{ leadBoxes.length }} lead box{{ leadBoxes.length === 1 ? '' : 'es' }}
                            </p>
                        </div>

                        <div class="hidden overflow-x-auto md:block">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-white">
                                    <tr>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Internal Name
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Type
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Status
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Title
                                        </th>
                                        <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Updated
                                        </th>
                                        <th class="px-6 py-4 text-right text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Action
                                        </th>
                                    </tr>
                                </thead>

                                <tbody class="divide-y divide-gray-200 bg-white">
                                    <tr
                                        v-for="leadBox in leadBoxes"
                                        :key="leadBox.id"
                                    >
                                        <td class="px-6 py-4 align-top text-sm font-semibold text-gray-900">
                                            {{ leadBox.internal_name }}
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-gray-700">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                                :class="typeClasses(leadBox.type)"
                                            >
                                                {{ typeLabel(leadBox.type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-gray-700">
                                            <span
                                                class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                                :class="statusClasses(leadBox.status)"
                                            >
                                                {{ leadBox.status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-gray-700">
                                            {{ leadBox.title || '—' }}
                                        </td>
                                        <td class="px-6 py-4 align-top text-sm text-gray-600">
                                            {{ formattedUpdatedAt(leadBox.updated_at) }}
                                        </td>
                                        <td class="px-6 py-4 align-top text-right">
                                            <div class="flex justify-end gap-2">
                                                <button
                                                    type="button"
                                                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                                    @click="duplicateLeadBox(leadBox)"
                                                >
                                                    Duplicate
                                                </button>
                                                <Link
                                                    :href="editRoute(leadBox)"
                                                    class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                                >
                                                    Edit
                                                </Link>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="divide-y divide-gray-200 md:hidden">
                            <div
                                v-for="leadBox in leadBoxes"
                                :key="leadBox.id"
                                class="space-y-4 p-5"
                            >
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                            Internal Name
                                        </p>
                                        <h2 class="mt-1 text-base font-semibold text-gray-900">
                                            {{ leadBox.internal_name }}
                                        </h2>
                                    </div>

                                    <div class="flex shrink-0 flex-col gap-2">
                                        <button
                                            type="button"
                                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                            @click="duplicateLeadBox(leadBox)"
                                        >
                                            Duplicate
                                        </button>
                                        <Link
                                            :href="editRoute(leadBox)"
                                            class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold"
                                        :class="typeClasses(leadBox.type)"
                                    >
                                        {{ typeLabel(leadBox.type) }}
                                    </span>

                                    <span
                                        class="inline-flex rounded-full px-3 py-1 text-xs font-semibold capitalize"
                                        :class="statusClasses(leadBox.status)"
                                    >
                                        {{ leadBox.status }}
                                    </span>
                                </div>

                                <div class="space-y-3 text-sm text-gray-600">
                                    <p>
                                        <span class="font-semibold text-gray-900">Title:</span>
                                        {{ leadBox.title || '—' }}
                                    </p>
                                    <p>
                                        <span class="font-semibold text-gray-900">Updated:</span>
                                        {{ formattedUpdatedAt(leadBox.updated_at) }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
