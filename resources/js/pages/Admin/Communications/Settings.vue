<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type KeyValueRow = { key: string; value: string };
type TriggerRow = { key: string; audience_key: string; tags: string };

const props = defineProps<{
    settings: {
        transactional_provider: string;
        marketing_provider: string;
        admin_notification_email: string | null;
        admin_notification_name: string | null;
        marketing_default_audience_key: string;
        mailchimp_audiences: KeyValueRow[];
        mailchimp_tags: KeyValueRow[];
        mailchimp_triggers: TriggerRow[];
    };
    providerStatus: {
        postmark_configured: boolean;
        mailchimp_configured: boolean;
    };
    defaults: {
        transactional_provider: string;
        marketing_provider: string;
        admin_notification_email: string | null;
        admin_notification_name: string | null;
        marketing_default_audience_key: string;
    };
    options: {
        transactional_providers: string[];
        marketing_providers: string[];
    };
}>();

const form = useForm({
    transactional_provider: props.settings.transactional_provider,
    marketing_provider: props.settings.marketing_provider,
    admin_notification_email: props.settings.admin_notification_email ?? '',
    admin_notification_name: props.settings.admin_notification_name ?? '',
    marketing_default_audience_key: props.settings.marketing_default_audience_key,
    mailchimp_audiences: props.settings.mailchimp_audiences.length ? props.settings.mailchimp_audiences : [{ key: '', value: '' }],
    mailchimp_tags: props.settings.mailchimp_tags.length ? props.settings.mailchimp_tags : [{ key: '', value: '' }],
    mailchimp_triggers: props.settings.mailchimp_triggers.length ? props.settings.mailchimp_triggers : [{ key: '', audience_key: '', tags: '' }],
});

const addAudience = () => form.mailchimp_audiences.push({ key: '', value: '' });
const addTag = () => form.mailchimp_tags.push({ key: '', value: '' });
const addTrigger = () => form.mailchimp_triggers.push({ key: '', audience_key: '', tags: '' });

const removeAudience = (index: number) => form.mailchimp_audiences.splice(index, 1);
const removeTag = (index: number) => form.mailchimp_tags.splice(index, 1);
const removeTrigger = (index: number) => form.mailchimp_triggers.splice(index, 1);

const save = () => {
    form.put(route('admin.communications.settings.update'));
};
</script>

<template>
    <Head title="Communication Settings" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h1 class="text-3xl font-semibold tracking-tight text-gray-900">Communication Settings</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Manage current provider roles and practical operational mappings. API credentials remain env-backed.
                </p>
                <p class="mt-2 text-sm text-gray-600">
                    Only approved communications setting keys are persisted here. Unknown keys and provider secrets stay out of the database.
                </p>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <h2 class="text-lg font-semibold text-gray-900">Provider Status</h2>
                <dl class="mt-4 grid gap-4 md:grid-cols-2">
                    <div class="rounded-xl border border-gray-200 p-4">
                        <dt class="text-sm text-gray-500">Postmark credential</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ providerStatus.postmark_configured ? 'Configured in env' : 'Missing in env' }}</dd>
                    </div>
                    <div class="rounded-xl border border-gray-200 p-4">
                        <dt class="text-sm text-gray-500">Mailchimp credential</dt>
                        <dd class="mt-1 text-sm font-medium text-gray-900">{{ providerStatus.mailchimp_configured ? 'Configured in env' : 'Missing in env' }}</dd>
                    </div>
                </dl>
            </div>

            <form class="space-y-6" @submit.prevent="save">
                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <h2 class="text-lg font-semibold text-gray-900">Core Settings</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Empty admin notification fields fall back to env-backed defaults. Current default audience key fallback: {{ defaults.marketing_default_audience_key }}.
                    </p>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Transactional provider</span>
                            <select v-model="form.transactional_provider" class="w-full rounded-md border border-gray-300 px-3 py-2">
                                <option v-for="option in options.transactional_providers" :key="option" :value="option">{{ option }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Marketing provider</span>
                            <select v-model="form.marketing_provider" class="w-full rounded-md border border-gray-300 px-3 py-2">
                                <option v-for="option in options.marketing_providers" :key="option" :value="option">{{ option }}</option>
                            </select>
                        </label>
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Admin notification email</span>
                            <input v-model="form.admin_notification_email" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.admin_notification_email" class="mt-1 text-sm text-red-600">{{ form.errors.admin_notification_email }}</p>
                        </label>
                        <label class="text-sm text-gray-700">
                            <span class="mb-1 block font-medium">Admin notification name</span>
                            <input v-model="form.admin_notification_name" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.admin_notification_name" class="mt-1 text-sm text-red-600">{{ form.errors.admin_notification_name }}</p>
                        </label>
                        <label class="text-sm text-gray-700 md:col-span-2">
                            <span class="mb-1 block font-medium">Default marketing audience key</span>
                            <input v-model="form.marketing_default_audience_key" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                            <p v-if="form.errors.marketing_default_audience_key" class="mt-1 text-sm text-red-600">{{ form.errors.marketing_default_audience_key }}</p>
                        </label>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900">Mailchimp Audience Mappings</h2>
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="addAudience">Add row</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="(row, index) in form.mailchimp_audiences" :key="`audience-${index}`" class="grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                            <input v-model="row.key" placeholder="audience.general" class="rounded-md border border-gray-300 px-3 py-2" />
                            <input v-model="row.value" placeholder="provider audience id" class="rounded-md border border-gray-300 px-3 py-2" />
                            <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="removeAudience(index)">Remove</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900">Mailchimp Tag Mappings</h2>
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="addTag">Add row</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="(row, index) in form.mailchimp_tags" :key="`tag-${index}`" class="grid gap-3 md:grid-cols-[1fr,1fr,auto]">
                            <input v-model="row.key" placeholder="tag.contact.requested" class="rounded-md border border-gray-300 px-3 py-2" />
                            <input v-model="row.value" placeholder="provider tag value" class="rounded-md border border-gray-300 px-3 py-2" />
                            <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="removeTag(index)">Remove</button>
                        </div>
                    </div>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6">
                    <div class="flex items-center justify-between gap-4">
                        <h2 class="text-lg font-semibold text-gray-900">Mailchimp Trigger Mappings</h2>
                        <button type="button" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm text-gray-700 hover:bg-gray-50" @click="addTrigger">Add row</button>
                    </div>
                    <div class="mt-4 space-y-3">
                        <div v-for="(row, index) in form.mailchimp_triggers" :key="`trigger-${index}`" class="grid gap-3 md:grid-cols-[1fr,1fr,1.5fr,auto]">
                            <input v-model="row.key" placeholder="trigger.contact.requested" class="rounded-md border border-gray-300 px-3 py-2" />
                            <input v-model="row.audience_key" placeholder="audience.general" class="rounded-md border border-gray-300 px-3 py-2" />
                            <input v-model="row.tags" placeholder="tag_one, tag_two" class="rounded-md border border-gray-300 px-3 py-2" />
                            <button type="button" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50" @click="removeTrigger(index)">Remove</button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end">
                    <button type="submit" class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800" :disabled="form.processing">
                        Save settings
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
