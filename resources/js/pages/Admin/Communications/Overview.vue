<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

defineProps<{
    summary: {
        events_total: number;
        events_pending: number;
        events_processing: number;
        events_processed: number;
        events_partial_failure: number;
        events_failed: number;
        events_skipped: number;
        recent_deliveries_sent: number;
        recent_deliveries_failed: number;
        marketing_syncs_failed: number;
        recent_window_days: number;
    };
    providers: {
        transactional: string;
        marketing: string;
    };
    recentEvents: Array<{
        id: number;
        event_key: string;
        status: string;
        created_at: string | null;
        processed_at: string | null;
    }>;
}>();

const formatDateTime = (value: string | null) => {
    if (!value) return '—';

    return new Date(value).toLocaleString();
};
</script>

<template>
    <Head title="Communications Overview" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communications</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Overview</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Inspect internal communications truth, provider role selection, and recent processing outcomes.
                </p>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Pending Events</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.events_pending }}</p>
                    <p class="mt-1 text-sm text-gray-600">Queued and waiting to process.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Processing Events</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.events_processing }}</p>
                    <p class="mt-1 text-sm text-gray-600">Currently reserved by the processing pipeline.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Processed Events</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.events_processed }}</p>
                    <p class="mt-1 text-sm text-gray-600">Completed successfully.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Partial Failures</p>
                    <p class="mt-2 text-3xl font-semibold text-amber-700">{{ summary.events_partial_failure }}</p>
                    <p class="mt-1 text-sm text-gray-600">Safe to requeue. Dedupe still protects successful actions.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Failed Events</p>
                    <p class="mt-2 text-3xl font-semibold text-red-700">{{ summary.events_failed }}</p>
                    <p class="mt-1 text-sm text-gray-600">Nothing succeeded for these events.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Skipped Events</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.events_skipped }}</p>
                    <p class="mt-1 text-sm text-gray-600">No actions were required for these events.</p>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Sent Deliveries</p>
                    <p class="mt-2 text-3xl font-semibold text-gray-900">{{ summary.recent_deliveries_sent }}</p>
                    <p class="mt-1 text-sm text-gray-600">Last {{ summary.recent_window_days }} days.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Failed Deliveries</p>
                    <p class="mt-2 text-3xl font-semibold text-red-700">{{ summary.recent_deliveries_failed }}</p>
                    <p class="mt-1 text-sm text-gray-600">Last {{ summary.recent_window_days }} days.</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Failed Marketing Syncs</p>
                    <p class="mt-2 text-3xl font-semibold text-red-700">{{ summary.marketing_syncs_failed }}</p>
                    <p class="mt-1 text-sm text-gray-600">Current failed sync records tracked internally.</p>
                </div>
            </div>

            <div class="rounded-2xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
                Requeues reuse the same queue-driven communication pipeline. Already successful deliveries stay protected by delivery-level dedupe and will not be resent.
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr,2fr]">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Provider Roles</h2>
                    <dl class="mt-4 space-y-3 text-sm">
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Transactional</dt>
                            <dd class="font-medium text-gray-900">{{ providers.transactional }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Marketing</dt>
                            <dd class="font-medium text-gray-900">{{ providers.marketing }}</dd>
                        </div>
                        <div class="flex items-center justify-between gap-4">
                            <dt class="text-gray-500">Tracked event rows</dt>
                            <dd class="font-medium text-gray-900">{{ summary.events_total }}</dd>
                        </div>
                    </dl>

                    <div class="mt-6">
                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="route('admin.communications.settings.index')"
                                class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                Open settings
                            </Link>
                            <Link
                                :href="route('admin.communications.composer.index')"
                                class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Open composer
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Events</h2>
                        <Link :href="route('admin.communications.events.index')" class="text-sm text-gray-600 hover:text-gray-900">
                            View all
                        </Link>
                    </div>

                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Event</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Created</th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Processed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-if="recentEvents.length === 0">
                                    <td colspan="4" class="px-3 py-4 text-sm text-gray-600">No communication events found.</td>
                                </tr>
                                <tr v-for="event in recentEvents" :key="event.id">
                                    <td class="px-3 py-3 text-sm font-medium text-gray-900">
                                        <Link :href="route('admin.communications.events.show', event.id)" class="underline-offset-2 hover:underline">
                                            {{ event.event_key }}
                                        </Link>
                                    </td>
                                    <td class="px-3 py-3 text-sm text-gray-700">{{ event.status }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-700">{{ formatDateTime(event.created_at) }}</td>
                                    <td class="px-3 py-3 text-sm text-gray-700">{{ formatDateTime(event.processed_at) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
