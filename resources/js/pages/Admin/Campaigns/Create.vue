<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import CampaignForm from './Form.vue';

defineProps<{
    formOptions: {
        statusOptions: Array<{ value: string; label: string }>;
        audienceOptions: Array<{ value: string; label: string }>;
        entryTriggerOptions: Array<{ value: string; label: string }>;
        templateOptions: Array<{ value: number; label: string; description?: string }>;
        delayUnitOptions: Array<{ value: string; label: string }>;
        sendModeOptions: Array<{ value: string; label: string }>;
    };
}>();
</script>

<template>
    <Head title="Create Campaign" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Campaigns</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Create Campaign</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Set up a campaign definition and its step sequence using the existing communications stack.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.campaigns.index')"
                        class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Back to campaigns
                    </Link>
                </div>
            </section>

            <CampaignForm
                mode="create"
                :submit-url="route('admin.campaigns.store')"
                :form-options="formOptions"
            />
        </div>
    </AdminLayout>
</template>
