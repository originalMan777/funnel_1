<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

defineProps<{
    leadBox: {
        id: number;
        type: string;
        status: string;
        internal_name: string;
        title: string;
        short_text: string | null;
        button_text: string | null;
        icon_key: string | null;
        content: Record<string, unknown>;
    };
    statuses: string[];
    icons: string[];
    visualPresets: string[];
}>();

const duplicateLeadBox = (id: number) => {
    if (!window.confirm('Duplicate this lead box as a draft?')) {
        return;
    }

    router.post(route('admin.lead-boxes.duplicate', id), {}, { preserveScroll: true });
};
</script>

<template>
    <Head title="Edit Lead Box" />

    <AdminLayout>
        <div class="h-full p-4">
            <div class="flex h-full flex-col rounded-2xl border border-gray-200 bg-white">
                <div class="border-b border-gray-200 p-6">
                    <div class="flex flex-col gap-3 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                Lead Boxes
                            </p>
                            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">
                                Edit Lead Box
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                This is the current generic edit entry for lead boxes. It confirms which lead box you opened while the typed edit flow remains separate.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <button
                                type="button"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                                @click="duplicateLeadBox(leadBox.id)"
                            >
                                Duplicate
                            </button>
                            <Link
                                :href="route('admin.lead-boxes.index')"
                                class="inline-flex items-center justify-center rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-50"
                            >
                                Back to Lead Boxes
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 p-6">
                    <div class="rounded-2xl border border-gray-200 p-6">
                        <div class="grid gap-5 md:grid-cols-3">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Title
                                </p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">
                                    {{ leadBox.title }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Type
                                </p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">
                                    {{ leadBox.type }}
                                </p>
                            </div>

                            <div>
                                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Status
                                </p>
                                <p class="mt-2 text-sm font-semibold text-gray-900">
                                    {{ leadBox.status }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6">
                        <p class="text-sm leading-relaxed text-gray-600">
                            The current lead box type is <span class="font-semibold text-gray-900">{{ leadBox.type }}</span>.
                            This landing page exists so the generic edit route is physically real and does not dead-end.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
