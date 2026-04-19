<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type DeliveryDetail = {
    id: number;
    event: { id: number; event_key: string; status: string } | null;
    action_key: string;
    channel: string;
    provider: string | null;
    recipient_email: string | null;
    recipient_name: string | null;
    subject: string | null;
    status: string;
    error_message: string | null;
    provider_message_id: string | null;
    payload: Record<string, unknown>;
    created_at: string | null;
    updated_at: string | null;
    sent_at: string | null;
};

defineProps<{
    delivery: DeliveryDetail;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const formatPayload = (value: Record<string, unknown>) => {
    if (Object.keys(value ?? {}).length === 0) {
        return 'No payload snapshot captured for this delivery.';
    }

    return JSON.stringify(value, null, 2);
};
</script>

<template>
    <Head :title="`Delivery ${delivery.action_key}`" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communication Delivery</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ delivery.action_key }}</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Inspect the internal delivery record, timing, and payload snapshot for one outbound action.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.communications.deliveries.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Back to deliveries
                        </Link>
                        <Link
                            v-if="delivery.event"
                            :href="route('admin.communications.events.show', delivery.event.id)"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Open event trace
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Delivery Details</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Related event</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                <Link
                                    v-if="delivery.event"
                                    :href="route('admin.communications.events.show', delivery.event.id)"
                                    class="underline-offset-2 hover:underline"
                                >
                                    {{ delivery.event.event_key }}
                                </Link>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.status }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Channel</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.channel }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Provider</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.provider || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Recipient</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.recipient_email || delivery.recipient_name || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Subject</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.subject || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Provider message ID</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ delivery.provider_message_id || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Sent at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(delivery.sent_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(delivery.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Updated</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(delivery.updated_at) }}</dd>
                        </div>
                    </dl>

                    <div v-if="delivery.error_message" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                        <p class="font-medium">Error message</p>
                        <p class="mt-1">{{ delivery.error_message }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Payload Snapshot</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-sm text-gray-100">{{ formatPayload(delivery.payload) }}</pre>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
