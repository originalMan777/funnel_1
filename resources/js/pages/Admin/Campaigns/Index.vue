<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

defineProps<{
    campaigns: Array<{
        id: number;
        name: string;
        status: string;
        audience_type: string;
        entry_trigger: string;
        entry_trigger_label: string;
        description: string | null;
        steps_count: number;
        enrollments_count: number;
        updated_at: string | null;
    }>;
}>();
</script>

<template>
    <Head title="Campaigns" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Campaigns</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Campaigns</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Define campaign entry rules and step sequences on top of the existing communications system.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.campaigns.create')"
                        class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Create campaign
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Campaign</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Audience</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Entry Trigger</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Steps</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Enrollments</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="campaigns.length === 0">
                                <td colspan="7" class="px-3 py-4 text-sm text-gray-600">
                                    No campaigns found yet.
                                </td>
                            </tr>

                            <tr v-for="campaign in campaigns" :key="campaign.id">
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="font-medium">
                                        {{ campaign.name }}
                                    </div>
                                    <div v-if="campaign.description" class="text-gray-500">
                                        {{ campaign.description }}
                                    </div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ campaign.status }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ campaign.audience_type }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ campaign.entry_trigger_label }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ campaign.steps_count }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ campaign.enrollments_count }}</td>
                                <td class="px-3 py-3 text-sm">
                                    <Link
                                        :href="route('admin.campaigns.edit', campaign.id)"
                                        class="text-gray-700 underline-offset-2 hover:underline"
                                    >
                                        Edit
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
