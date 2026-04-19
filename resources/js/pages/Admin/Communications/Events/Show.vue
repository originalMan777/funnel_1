<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type Contact = {
    id: number;
    display_name: string | null;
    email: string | null;
};

type EventDetail = {
    id: number;
    event_key: string;
    subject_type: string;
    subject_id: number;
    acquisition_contact: Contact | null;
    status: string;
    payload: Record<string, unknown>;
    created_at: string | null;
    processed_at: string | null;
    can_requeue: boolean;
};

type DeliveryRow = {
    id: number;
    action_key: string;
    channel: string;
    provider: string | null;
    recipient_email: string | null;
    recipient_name: string | null;
    status: string;
    error_message: string | null;
    provider_message_id: string | null;
    created_at: string | null;
    sent_at: string | null;
};

const props = defineProps<{
    event: EventDetail;
    deliveries: DeliveryRow[];
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const formatPayload = (value: Record<string, unknown>) => {
    const entries = Object.keys(value ?? {});

    if (entries.length === 0) {
        return 'No payload snapshot captured for this event.';
    }

    return JSON.stringify(value, null, 2);
};

const requeue = () => {
    router.post(route('admin.communications.events.requeue', props.event.id), {}, {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="`Communication Event ${event.event_key}`" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communication Event Trace</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ event.event_key }}</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Inspect the internal event, its payload snapshot, and all related delivery attempts in order.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link
                            :href="route('admin.communications.events.index')"
                            class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Back to events
                        </Link>
                        <button
                            v-if="event.can_requeue"
                            type="button"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            @click="requeue"
                        >
                            Requeue event
                        </button>
                    </div>
                </div>
            </div>

            <div
                v-if="event.can_requeue"
                class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"
            >
                Requeue uses the existing communication pipeline. Already successful deliveries stay protected by delivery-level dedupe and will not be resent.
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.2fr,0.8fr]">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Event Details</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Event key</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ event.event_key }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ event.status }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Subject</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ event.subject_type }} #{{ event.subject_id }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Acquisition contact</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                <Link
                                    v-if="event.acquisition_contact"
                                    :href="route('admin.acquisition.contacts.show', event.acquisition_contact.id)"
                                    class="underline-offset-2 hover:underline"
                                >
                                    {{ event.acquisition_contact.display_name || event.acquisition_contact.email || `Contact #${event.acquisition_contact.id}` }}
                                </Link>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(event.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Processed</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(event.processed_at) }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Payload Snapshot</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-sm text-gray-100">{{ formatPayload(event.payload) }}</pre>
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Related Deliveries</h2>
                    <p class="mt-1 text-sm text-gray-600">
                        Delivery attempts are shown in recorded order so one event can be debugged end to end.
                    </p>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
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
                            <tr v-if="deliveries.length === 0">
                                <td colspan="8" class="px-4 py-6 text-sm text-gray-600">No delivery attempts have been recorded for this event yet.</td>
                            </tr>
                            <tr v-for="delivery in deliveries" :key="delivery.id">
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
            </div>
        </div>
    </AdminLayout>
</template>
