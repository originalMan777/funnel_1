<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type EnrollmentRow = {
    id: number;
    campaign: {
        id: number | null;
        name: string;
    };
    recipient: {
        name: string;
        email: string | null;
    };
    source: {
        label: string;
        identity: string;
        route: string | null;
    };
    current_step: {
        order: number | null;
        label: string;
        send_mode: string | null;
        delay: string | null;
    };
    status: string;
    status_label: string;
    next_run_at: string | null;
    started_at: string | null;
    completed_at: string | null;
    exit_reason: string;
};

type Paginated = {
    data: EnrollmentRow[];
    total?: number;
    from?: number | null;
    to?: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    filters: {
        campaign: string;
        status: string;
    };
    campaignOptions: Array<{ value: string; label: string }>;
    statusOptions: Array<{ value: string; label: string }>;
    enrollments: Paginated;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const submitFilters = (event: Event) => {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);

    router.get(route('admin.campaign-enrollments.index'), {
        campaign: data.get('campaign'),
        status: data.get('status'),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    router.get(route('admin.campaign-enrollments.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Campaign Enrollments" />

    <AdminLayout>
        <div class="space-y-4 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Campaign Enrollments</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Monitor live campaign recipients, see what source enrolled them, and inspect what runs next.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <form class="grid gap-4 md:grid-cols-3" @submit.prevent="submitFilters">
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Campaign</span>
                        <select name="campaign" :value="filters.campaign" class="w-full rounded-md border border-gray-300 px-3 py-2">
                            <option value="">All campaigns</option>
                            <option v-for="option in campaignOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Status</span>
                        <select name="status" :value="filters.status" class="w-full rounded-md border border-gray-300 px-3 py-2">
                            <option value="">All statuses</option>
                            <option v-for="option in statusOptions" :key="option.value" :value="option.value">
                                {{ option.label }}
                            </option>
                        </select>
                    </label>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="clearFilters">Clear</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600">
                    Showing {{ enrollments.from ?? 0 }}-{{ enrollments.to ?? enrollments.data.length }} of {{ enrollments.total ?? enrollments.data.length }} enrollments.
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Campaign</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Recipient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Source Type</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Current Step</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Next Run At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Started At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Completed At</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Exit Reason</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="enrollments.data.length === 0">
                                <td colspan="10" class="px-4 py-6 text-sm text-gray-600">No campaign enrollments found.</td>
                            </tr>

                            <tr v-for="enrollment in enrollments.data" :key="enrollment.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ enrollment.campaign.name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-900">{{ enrollment.recipient.name }}</div>
                                    <div class="text-gray-500">{{ enrollment.recipient.email || '—' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>{{ enrollment.source.label }}</div>
                                    <div class="text-gray-500">{{ enrollment.source.identity }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>{{ enrollment.current_step.label }}</div>
                                    <div v-if="enrollment.current_step.send_mode" class="text-gray-500">
                                        {{ enrollment.current_step.send_mode }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ enrollment.status_label }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(enrollment.next_run_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(enrollment.started_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(enrollment.completed_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ enrollment.exit_reason }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <Link
                                        :href="route('admin.campaign-enrollments.show', enrollment.id)"
                                        class="text-gray-700 underline-offset-2 hover:underline"
                                    >
                                        View
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="enrollments.links?.length" class="border-t border-gray-200 p-4">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in enrollments.links" :key="link.label">
                            <span v-if="!link.url" class="px-2 py-1 text-sm text-gray-400" v-html="link.label" />
                            <Link
                                v-else
                                :href="link.url"
                                class="rounded-md px-2 py-1 text-sm"
                                :class="link.active ? 'bg-gray-900 text-white' : 'text-gray-700 hover:bg-gray-100'"
                                v-html="link.label"
                                preserve-scroll
                            />
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
