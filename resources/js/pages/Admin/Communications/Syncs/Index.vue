<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type SyncRow = {
    id: number;
    acquisition_contact: { id: number; display_name: string | null; email: string | null } | null;
    provider: string;
    audience_key: string | null;
    email: string | null;
    external_contact_id: string | null;
    last_sync_status: string;
    last_error_message: string | null;
    last_synced_at: string | null;
    updated_at: string | null;
};

type Paginated = {
    data: SyncRow[];
    total?: number;
    from?: number | null;
    to?: number | null;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    filters: { status: string; provider: string; email: string; audience_key: string; date_from: string; date_to: string };
    syncs: Paginated;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const submitFilters = (event: Event) => {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);

    router.get(route('admin.communications.syncs.index'), {
        status: data.get('status'),
        provider: data.get('provider'),
        email: data.get('email'),
        audience_key: data.get('audience_key'),
        date_from: data.get('date_from'),
        date_to: data.get('date_to'),
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    router.get(route('admin.communications.syncs.index'), {}, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Marketing Syncs" />

    <AdminLayout>
        <div class="space-y-4 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Marketing Syncs</h1>
                <p class="mt-2 text-sm text-gray-600">Inspect internal marketing sync state without relying on provider dashboards.</p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <form class="grid gap-4 md:grid-cols-4 xl:grid-cols-6" @submit.prevent="submitFilters">
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Status</span>
                        <input name="status" :value="filters.status" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Provider</span>
                        <input name="provider" :value="filters.provider" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Email</span>
                        <input name="email" :value="filters.email" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Audience key</span>
                        <input name="audience_key" :value="filters.audience_key" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Date from</span>
                        <input name="date_from" type="date" :value="filters.date_from" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <label class="text-sm text-gray-700">
                        <span class="mb-1 block font-medium">Date to</span>
                        <input name="date_to" type="date" :value="filters.date_to" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                    </label>
                    <div class="md:col-span-4 xl:col-span-6 flex gap-2">
                        <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800">Filter</button>
                        <button type="button" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50" @click="clearFilters">Clear</button>
                    </div>
                </form>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-4 py-3 text-sm text-gray-600">
                    Showing {{ syncs.from ?? 0 }}-{{ syncs.to ?? syncs.data.length }} of {{ syncs.total ?? syncs.data.length }} syncs.
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Contact</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Provider</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Audience</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">External ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Error</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Last Synced</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="syncs.data.length === 0">
                                <td colspan="8" class="px-4 py-6 text-sm text-gray-600">No marketing sync records found.</td>
                            </tr>
                            <tr v-for="sync in syncs.data" :key="sync.id">
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <Link
                                        v-if="sync.acquisition_contact"
                                        :href="route('admin.acquisition.contacts.show', sync.acquisition_contact.id)"
                                        class="text-gray-900 underline-offset-2 hover:underline"
                                    >
                                        {{ sync.acquisition_contact.display_name || sync.acquisition_contact.email || `Contact #${sync.acquisition_contact.id}` }}
                                    </Link>
                                    <span v-else>—</span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <Link :href="route('admin.communications.syncs.show', sync.id)" class="underline-offset-2 hover:underline">
                                        {{ sync.provider }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ sync.audience_key || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ sync.email || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ sync.external_contact_id || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ sync.last_sync_status }}</td>
                                <td class="max-w-xs px-4 py-3 text-sm text-gray-700">{{ sync.last_error_message || '—' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-700">{{ formatDateTime(sync.last_synced_at) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="syncs.links?.length" class="border-t border-gray-200 p-4">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in syncs.links" :key="link.label">
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
