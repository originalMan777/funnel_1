<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type ContactRow = {
    id: number;
    display_name: string | null;
    email: string | null;
    phone: string | null;
    state: string | null;
    source_type: string | null;
    source_label: string | null;
    last_activity_at: string | null;
    created_at: string | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedContacts = {
    data: ContactRow[];
    links: PaginationLink[];
};

const props = defineProps<{
    contacts: PaginatedContacts;
}>();

const openContact = (contactId: number) => {
    router.get(route('admin.acquisition.contacts.show', contactId), {}, {
        preserveScroll: true,
    });
};

const formatDateTime = (value: string | null) => {
    if (!value) return '—';

    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return value;
    }

    return date.toLocaleString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    });
};

const sourceText = (contact: ContactRow) => {
    return contact.source_label || contact.source_type || '—';
};

const rowLabel = (contact: ContactRow) => {
    return contact.display_name || contact.email || `Contact #${contact.id}`;
};
</script>

<template>
    <Head title="Acquisition Contacts" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-6">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Acquisition
                        </p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            Contacts
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Browse acquisition contacts by most recent activity.
                        </p>
                    </div>
                </div>

                <div class="flex-1 overflow-hidden">
                    <div class="h-full overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Name
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Email
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Phone
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        State
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Source
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Last Activity
                                    </th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Created
                                    </th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="contacts.data.length === 0">
                                    <td colspan="7" class="px-4 py-6 text-sm text-gray-600">
                                        No acquisition contacts found.
                                    </td>
                                </tr>

                                <tr
                                    v-for="contact in contacts.data"
                                    :key="contact.id"
                                    class="cursor-pointer hover:bg-gray-50 focus-within:bg-gray-50"
                                    tabindex="0"
                                    @click="openContact(contact.id)"
                                    @keydown.enter.prevent="openContact(contact.id)"
                                    @keydown.space.prevent="openContact(contact.id)"
                                >
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        {{ rowLabel(contact) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ contact.email || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ contact.phone || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ contact.state || '—' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ sourceText(contact) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ formatDateTime(contact.last_activity_at) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        {{ formatDateTime(contact.created_at) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div v-if="contacts.links?.length" class="border-t border-gray-200 p-4">
                    <div class="flex flex-wrap gap-1">
                        <template v-for="link in contacts.links" :key="link.label">
                            <span
                                v-if="!link.url"
                                class="px-2 py-1 text-sm text-gray-400"
                                v-html="link.label"
                            />
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
