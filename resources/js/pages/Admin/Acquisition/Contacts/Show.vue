<script setup lang="ts">
import { computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type ContactDto = {
    id: number;
    display_name: string | null;
    email: string | null;
    phone: string | null;
    state: string | null;
    contact_type: string | null;
    source_type: string | null;
    source_label: string | null;
    company_name: string | null;
    person_name: string | null;
    created_at: string | null;
    last_activity_at: string | null;
};

type TimelineItem = {
    type: 'contact_created' | 'lead_submission' | 'popup_submission' | 'source_recorded' | 'state_changed' | 'touch_logged';
    title: string;
    subtitle: string | null;
    timestamp: string | null;
    details: Record<string, unknown>;
};

const props = defineProps<{
    contact: ContactDto;
    timeline_items: TimelineItem[];
}>();

const stateOptions = [
    'new',
    'contacted',
    'engaged',
    'qualified',
    'converted',
    'lost',
    'suppressed',
] as const;

const stateForm = useForm({
    state: props.contact.state || 'new',
});

const touchTypeOptions = [
    'call',
    'email',
    'note',
    'follow_up',
] as const;

const touchStatusOptions = [
    'completed',
    'scheduled',
] as const;

const touchForm = useForm({
    type: 'call',
    status: 'completed',
    summary: '',
    details: '',
    scheduled_for: '',
});

const contactName = computed(() => {
    return props.contact.display_name
        || props.contact.person_name
        || props.contact.email
        || `Contact #${props.contact.id}`;
});

const formatDateTime = (value: string | null) => {
    if (!value) return 'Unknown time';

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

const formatLabel = (value: string) => {
    return value
        .replace(/_/g, ' ')
        .replace(/\b\w/g, (character) => character.toUpperCase());
};

const renderDetailValue = (value: unknown) => {
    if (value === null || value === undefined || value === '') {
        return '—';
    }

    if (Array.isArray(value)) {
        return value.join(', ');
    }

    if (typeof value === 'object') {
        return JSON.stringify(value);
    }

    return String(value);
};

const updateState = () => {
    stateForm.patch(route('admin.acquisition.contacts.update-state', props.contact.id), {
        preserveScroll: true,
    });
};

const logTouch = () => {
    touchForm.post(route('admin.acquisition.contacts.touches.store', props.contact.id), {
        preserveScroll: true,
        onSuccess: () => {
            touchForm.reset();
            touchForm.type = 'call';
            touchForm.status = 'completed';
        },
    });
};

watch(() => touchForm.status, (status) => {
    if (status !== 'scheduled') {
        touchForm.scheduled_for = '';
    }
});
</script>

<template>
    <Head :title="`Acquisition Contact: ${contactName}`" />

    <AdminLayout>
        <div class="space-y-6">
            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                    <div>
                        <p class="text-sm font-medium uppercase tracking-[0.18em] text-gray-500">
                            Acquisition Contact
                        </p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                            {{ contactName }}
                        </h1>
                        <p class="mt-2 text-sm text-gray-600">
                            State: {{ contact.state || 'unknown' }}
                        </p>
                    </div>

                    <div class="grid gap-3 text-sm text-gray-700 md:min-w-[18rem]">
                        <div>
                            <label for="contact-state" class="font-semibold text-gray-900">State:</label>
                            <select
                                id="contact-state"
                                v-model="stateForm.state"
                                class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                                :disabled="stateForm.processing"
                                @change="updateState"
                            >
                                <option v-for="state in stateOptions" :key="state" :value="state">
                                    {{ formatLabel(state) }}
                                </option>
                            </select>
                            <p v-if="stateForm.errors.state" class="mt-1 text-xs text-red-600">
                                {{ stateForm.errors.state }}
                            </p>
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Email:</span>
                            {{ contact.email || '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Phone:</span>
                            {{ contact.phone || '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Company:</span>
                            {{ contact.company_name || '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Type:</span>
                            {{ contact.contact_type || '—' }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Created:</span>
                            {{ formatDateTime(contact.created_at) }}
                        </div>
                        <div>
                            <span class="font-semibold text-gray-900">Last activity:</span>
                            {{ formatDateTime(contact.last_activity_at) }}
                        </div>
                    </div>
                </div>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Log Touch</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Record a manual call, email, note, or follow-up for this contact.
                        </p>
                    </div>
                </div>

                <form class="mt-6 grid gap-4 md:grid-cols-2" @submit.prevent="logTouch">
                    <div>
                        <label for="touch-type" class="text-sm font-semibold text-gray-900">Type</label>
                        <select
                            id="touch-type"
                            v-model="touchForm.type"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                        >
                            <option v-for="type in touchTypeOptions" :key="type" :value="type">
                                {{ formatLabel(type) }}
                            </option>
                        </select>
                        <p v-if="touchForm.errors.type" class="mt-1 text-xs text-red-600">
                            {{ touchForm.errors.type }}
                        </p>
                    </div>

                    <div>
                        <label for="touch-status" class="text-sm font-semibold text-gray-900">Status</label>
                        <select
                            id="touch-status"
                            v-model="touchForm.status"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                        >
                            <option v-for="status in touchStatusOptions" :key="status" :value="status">
                                {{ formatLabel(status) }}
                            </option>
                        </select>
                        <p v-if="touchForm.errors.status" class="mt-1 text-xs text-red-600">
                            {{ touchForm.errors.status }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="touch-summary" class="text-sm font-semibold text-gray-900">Summary</label>
                        <input
                            id="touch-summary"
                            v-model="touchForm.summary"
                            type="text"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                            maxlength="255"
                        />
                        <p v-if="touchForm.errors.summary" class="mt-1 text-xs text-red-600">
                            {{ touchForm.errors.summary }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <label for="touch-details" class="text-sm font-semibold text-gray-900">Details</label>
                        <textarea
                            id="touch-details"
                            v-model="touchForm.details"
                            rows="4"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                        />
                        <p v-if="touchForm.errors.details" class="mt-1 text-xs text-red-600">
                            {{ touchForm.errors.details }}
                        </p>
                    </div>

                    <div v-if="touchForm.status === 'scheduled'">
                        <label for="touch-scheduled-for" class="text-sm font-semibold text-gray-900">Scheduled For</label>
                        <input
                            id="touch-scheduled-for"
                            v-model="touchForm.scheduled_for"
                            type="datetime-local"
                            class="mt-1 block w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                        />
                        <p v-if="touchForm.errors.scheduled_for" class="mt-1 text-xs text-red-600">
                            {{ touchForm.errors.scheduled_for }}
                        </p>
                    </div>

                    <div class="md:col-span-2">
                        <button
                            type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="touchForm.processing"
                        >
                            {{ touchForm.processing ? 'Logging…' : 'Log Touch' }}
                        </button>
                    </div>
                </form>
            </section>

            <section class="rounded-3xl border border-gray-200 bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-900">Timeline</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Unified read-only activity for this acquisition contact.
                        </p>
                    </div>
                    <div class="text-sm text-gray-500">
                        {{ timeline_items.length }} item<span v-if="timeline_items.length !== 1">s</span>
                    </div>
                </div>

                <div v-if="timeline_items.length" class="mt-6 space-y-6">
                    <div
                        v-for="(item, index) in timeline_items"
                        :key="`${item.type}-${item.timestamp}-${index}`"
                        class="relative pl-8"
                    >
                        <div class="absolute left-2 top-2 h-full w-px bg-gray-200" v-if="index !== timeline_items.length - 1"></div>
                        <div class="absolute left-0 top-1.5 h-4 w-4 rounded-full border-2 border-white bg-gray-900 shadow-sm"></div>

                        <div class="rounded-2xl border border-gray-200 bg-gray-50 p-4">
                            <div class="flex flex-col gap-2 md:flex-row md:items-start md:justify-between">
                                <div>
                                    <h3 class="text-base font-semibold text-gray-900">
                                        {{ item.title }}
                                    </h3>
                                    <p v-if="item.subtitle" class="mt-1 text-sm text-gray-600">
                                        {{ item.subtitle }}
                                    </p>
                                </div>

                                <div class="text-sm text-gray-500">
                                    {{ formatDateTime(item.timestamp) }}
                                </div>
                            </div>

                            <dl v-if="Object.keys(item.details || {}).length" class="mt-4 grid gap-3 md:grid-cols-2">
                                <div
                                    v-for="(value, key) in item.details"
                                    :key="key"
                                    class="rounded-xl bg-white px-3 py-2 ring-1 ring-gray-200"
                                >
                                    <dt class="text-xs font-semibold uppercase tracking-[0.14em] text-gray-500">
                                        {{ formatLabel(key) }}
                                    </dt>
                                    <dd class="mt-1 text-sm text-gray-800 break-words">
                                        {{ renderDetailValue(value) }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                <div v-else class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
                    No timeline activity is available for this contact yet.
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
