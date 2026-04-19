<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type Contact = {
    id: number;
    display_name: string | null;
    email: string | null;
};

type SyncDetail = {
    id: number;
    acquisition_contact: Contact | null;
    provider: string;
    audience_key: string | null;
    email: string | null;
    external_contact_id: string | null;
    last_sync_status: string;
    last_error_message: string | null;
    metadata: Record<string, unknown>;
    created_at: string | null;
    updated_at: string | null;
    last_synced_at: string | null;
};

defineProps<{
    sync: SyncDetail;
}>();

const formatDateTime = (value: string | null) => (value ? new Date(value).toLocaleString() : '—');

const formatMetadata = (value: Record<string, unknown>) => {
    if (Object.keys(value ?? {}).length === 0) {
        return 'No metadata snapshot captured for this sync record.';
    }

    return JSON.stringify(value, null, 2);
};
</script>

<template>
    <Head :title="`Marketing Sync ${sync.provider}`" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Marketing Sync</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ sync.provider }}</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Inspect the internal sync record instead of relying on the provider dashboard alone.
                        </p>
                    </div>

                    <div class="flex items-center gap-2">
                        <Link :href="route('admin.communications.syncs.index')" class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Back to syncs
                        </Link>
                        <Link
                            v-if="sync.acquisition_contact"
                            :href="route('admin.acquisition.contacts.show', sync.acquisition_contact.id)"
                            class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Open contact
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.15fr,0.85fr]">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Sync Details</h2>
                    <dl class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-sm text-gray-500">Acquisition contact</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">
                                <Link
                                    v-if="sync.acquisition_contact"
                                    :href="route('admin.acquisition.contacts.show', sync.acquisition_contact.id)"
                                    class="underline-offset-2 hover:underline"
                                >
                                    {{ sync.acquisition_contact.display_name || sync.acquisition_contact.email || `Contact #${sync.acquisition_contact.id}` }}
                                </Link>
                                <span v-else>—</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Last sync status</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ sync.last_sync_status }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Audience key</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ sync.audience_key || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Email</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ sync.email || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">External contact ID</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ sync.external_contact_id || '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Last synced at</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(sync.last_synced_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Created</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(sync.created_at) }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Updated</dt>
                            <dd class="mt-1 text-sm font-medium text-gray-900">{{ formatDateTime(sync.updated_at) }}</dd>
                        </div>
                    </dl>

                    <div v-if="sync.last_error_message" class="mt-6 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
                        <p class="font-medium">Last error</p>
                        <p class="mt-1">{{ sync.last_error_message }}</p>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Metadata Summary</h2>
                    <pre class="mt-4 overflow-x-auto rounded-xl bg-gray-950 p-4 text-sm text-gray-100">{{ formatMetadata(sync.metadata) }}</pre>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
