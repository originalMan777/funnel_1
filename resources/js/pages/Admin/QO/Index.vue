<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

defineOptions({
    layout: AdminLayout,
});

defineProps<{
    items: {
        data: Array<{
            id: number;
            title: string;
            slug: string;
            type: string;
            status: string;
            questions_count?: number;
            submissions_count?: number;
        }>;
    };
}>();
function duplicateItem(itemId: number) {
    if (!confirm('Duplicate this QO item as a draft?')) return;

    router.post(route('admin.qo.duplicate', itemId));
}
</script>

<template>
    <Head title="QO Engine" />

    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">QO Engine</h1>
                <p class="mt-1 text-sm text-slate-600">
                    Build quizzes and assessments with questions, outcomes, and funnel promotions.
                </p>
            </div>

            <Link
                :href="route('admin.qo.create')"
                class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
            >
                Create QO Item
            </Link>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Title</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Type</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Questions</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Submissions</th>
                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">Actions</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="item in items.data" :key="item.id">
                        <td class="px-4 py-3">
                            <div class="font-medium text-slate-900">{{ item.title }}</div>
                            <div class="text-xs text-slate-500">{{ item.slug }}</div>
                        </td>
                        <td class="px-4 py-3 text-sm capitalize text-slate-700">{{ item.type }}</td>
                        <td class="px-4 py-3 text-sm capitalize text-slate-700">{{ item.status }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ item.questions_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-sm text-slate-700">{{ item.submissions_count ?? 0 }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex justify-end gap-3">
                                <Link
                                    :href="route('admin.qo.preview', item.id)"
                                    class="text-sm font-semibold text-slate-700 hover:underline"
                                    target="_blank"
                                >
                                    Preview
                                </Link>

                                <Link
                                    :href="route('qo.show', item.slug)"
                                    class="text-sm font-semibold text-emerald-700 hover:underline"
                                    target="_blank"
                                >
                                    Live Test
                                </Link>

                                <button
                                    type="button"
                                    class="text-sm font-semibold text-indigo-700 hover:underline"
                                    @click="duplicateItem(item.id)"
                                >
                                    Duplicate
                                </button>

                                <Link
                                    :href="route('admin.qo.edit', item.id)"
                                    class="text-sm font-semibold text-slate-900 hover:underline"
                                >
                                    Edit
                                </Link>
                            </div>
                        </td>
                    </tr>

                    <tr v-if="items.data.length === 0">
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500">
                            No QO items yet.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
