<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type EventRow = {
    id: number;
    event_key: string;
    subject_type: string;
    subject_id: number;
    acquisition_contact: { id: number; display_name: string | null; email: string | null } | null;
    status: string;
    processed_at: string | null;
    created_at: string | null;
};

type Paginated = {
    data: EventRow[];
    total?: number;
    from?: number | null;
    to?: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    filters: { status: string; event_key: string; date_from: string; date_to: string };
    events: Paginated;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const submitFilters = (event: Event) => {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);

    router.get(route('admin.communications.events.index'), {
        status: data.get('status'),
        event_key: data.get('event_key'),
        date_from: data.get('date_from'),
        date_to: data.get('date_to'),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    router.get(route('admin.communications.events.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};

const requeue = (id: number) => {
    router.post(route('admin.communications.events.requeue', id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Communication Events" />

    <AdminLayout>
        <div class="space-y-4 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Communication Events</h1>
                <p class="mt-2 text-sm text-gray-600">Inspect internal communication intent and safely requeue failed work.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <form class="grid gap-4 md:grid-cols-4 xl:grid-cols-5" @submit.prevent="submitFilters">
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Status</span>
                        <input name="status" :value="filters.status" class="w-full rounded-md border border-gray-300 px-3 py-2" />
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
                    <div class="flex items-end gap-2 xl:col-span-5">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="clearFilters">Clear</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600">
                    <p>
                        Showing {{ events.from ?? 0 }}-{{ events.to ?? events.data.length }} of {{ events.total ?? events.data.length }} events.
                        Failed or partial events can be requeued safely. Successful deliveries are still protected by delivery-level dedupe.
                    </p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Event</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Subject</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Processed</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Created</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="events.data.length === 0">
                                <td colspan="7" class="px-4 py-6 text-sm text-gray-600">No communication events found.</td>
                            </tr>
                            <tr v-for="event in events.data" :key="event.id">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    <Link :href="route('admin.communications.events.show', event.id)" class="underline-offset-2 hover:underline">
                                        {{ event.event_key }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ event.subject_type }} #{{ event.subject_id }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <Link
                                        v-if="event.acquisition_contact"
                                        :href="route('admin.acquisition.contacts.show', event.acquisition_contact.id)"
                                        class="text-gray-900 underline-offset-2 hover:underline"
                                    >
                                        {{ event.acquisition_contact.display_name || event.acquisition_contact.email || `Contact #${event.acquisition_contact.id}` }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ event.status }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(event.processed_at) }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(event.created_at) }}</td>
                                <td class="px-4 py-3 text-sm">
                                    <button
                                        v-if="['failed', 'partial_failure'].includes(event.status)"
                                        type="button"
                                        class="rounded-md border border-gray-300 px-3 py-1.5 text-gray-700 hover:bg-gray-50"
                                        @click="requeue(event.id)"
                                    >
                                        Requeue
                                    </button>
                                    <span v-else class="text-gray-400">—</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="events.links?.length" class="border-t border-gray-200 p-4">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in events.links" :key="link.label">
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
