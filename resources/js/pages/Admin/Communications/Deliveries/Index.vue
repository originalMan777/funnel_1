<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type DeliveryRow = {
    id: number;
    communication_event_id: number;
    event_key: string | null;
    action_key: string;
    channel: string;
    provider: string | null;
    recipient_email: string | null;
    recipient_name: string | null;
    status: string;
    error_message: string | null;
    subject: string | null;
    created_at: string | null;
    sent_at: string | null;
};

type Paginated = {
    data: DeliveryRow[];
    total?: number;
    from?: number | null;
    to?: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    filters: { status: string; provider: string; channel: string; recipient: string; event_key: string; date_from: string; date_to: string };
    deliveries: Paginated;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const submitFilters = (event: Event) => {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);

    router.get(route('admin.communications.deliveries.index'), {
        status: data.get('status'),
        provider: data.get('provider'),
        channel: data.get('channel'),
        recipient: data.get('recipient'),
        event_key: data.get('event_key'),
        date_from: data.get('date_from'),
        date_to: data.get('date_to'),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    router.get(route('admin.communications.deliveries.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Communication Deliveries" />

    <AdminLayout>
        <div class="space-y-4 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Communication Deliveries</h1>
                <p class="mt-2 text-sm text-gray-600">Inspect internal delivery outcomes across transactional and marketing channels.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <form class="grid gap-4 md:grid-cols-4 xl:grid-cols-7" @submit.prevent="submitFilters">
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Status</span>
                        <input name="status" :value="filters.status" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Provider</span>
                        <input name="provider" :value="filters.provider" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Channel</span>
                        <input name="channel" :value="filters.channel" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Recipient</span>
                        <input name="recipient" :value="filters.recipient" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Event key</span>
                        <input name="event_key" :value="filters.event_key" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Date from</span>
                        <input name="date_from" type="date" :value="filters.date_from" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Date to</span>
                        <input name="date_to" type="date" :value="filters.date_to" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <div class="md:col-span-4 xl:col-span-7 flex gap-2">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="clearFilters">Clear</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600">
                    Showing {{ deliveries.from ?? 0 }}-{{ deliveries.to ?? deliveries.data.length }} of {{ deliveries.total ?? deliveries.data.length }} deliveries.
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Channel</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Provider</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Recipient</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Error</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Created</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Sent</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="deliveries.data.length === 0">
                                <td colspan="9" class="px-4 py-6 text-sm text-gray-600">No communication deliveries found.</td>
                            </tr>
                            <tr v-for="delivery in deliveries.data" :key="delivery.id">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <Link
                                        v-if="delivery.event_key"
                                        :href="route('admin.communications.events.show', delivery.communication_event_id)"
                                        class="underline-offset-2 hover:underline"
                                    >
                                        {{ delivery.event_key }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    <Link :href="route('admin.communications.deliveries.show', delivery.id)" class="underline-offset-2 hover:underline">
                                        {{ delivery.action_key }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ delivery.channel }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ delivery.provider || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ delivery.recipient_email || delivery.recipient_name || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ delivery.status }}</td>
                                <td class="max-w-xs px-4 py-3 text-sm text-gray-700">{{ delivery.error_message || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(delivery.created_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(delivery.sent_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="deliveries.links?.length" class="border-t border-gray-200 p-4">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in deliveries.links" :key="link.label">
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
