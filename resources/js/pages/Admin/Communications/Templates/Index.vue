<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

defineProps<{
    templates: Array<{
        id: number;
        key: string;
        name: string;
        status: string;
        channel: string;
        category: string;
        description: string | null;
        bindings_count: number;
        current_version: {
            id: number;
            version_number: number;
            is_published: boolean;
        } | null;
    }>;
}>();
</script>

<template>
    <Head title="Communication Templates" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communications</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Templates</h1>
                        <p class="mt-2 text-sm text-gray-600">
                            Admin entry point for email communication templates.
                        </p>
                    </div>

                    <Link
                        :href="route('admin.communications.templates.create')"
                        class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                    >
                        Create template
                    </Link>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Template</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Status</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Version</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Bindings</th>
                                <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <tr v-if="templates.length === 0">
                                <td colspan="5" class="px-3 py-4 text-sm text-gray-600">
                                    No communication templates found.
                                </td>
                            </tr>
                            <tr v-for="template in templates" :key="template.id">
                                <td class="px-3 py-3 text-sm text-gray-900">
                                    <div class="font-medium">
                                        <Link
                                            :href="route('admin.communications.templates.show', template.id)"
                                            class="underline-offset-2 hover:underline"
                                        >
                                            {{ template.name }}
                                        </Link>
                                    </div>
                                    <div class="text-gray-500">{{ template.key }}</div>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ template.status }}</td>
                                <td class="px-3 py-3 text-sm text-gray-700">
                                    <span v-if="template.current_version">
                                        v{{ template.current_version.version_number }}
                                        <span v-if="template.current_version.is_published">(published)</span>
                                    </span>
                                    <span v-else>None</span>
                                </td>
                                <td class="px-3 py-3 text-sm text-gray-700">{{ template.bindings_count }}</td>
                                <td class="px-3 py-3 text-sm">
                                    <div class="flex flex-wrap gap-3">
                                        <Link
                                            :href="route('admin.communications.templates.show', template.id)"
                                            class="text-gray-700 underline-offset-2 hover:underline"
                                        >
                                            View
                                        </Link>
                                        <Link
                                            :href="route('admin.communications.templates.edit', template.id)"
                                            class="text-gray-700 underline-offset-2 hover:underline"
                                        >
                                            Edit
                                        </Link>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
