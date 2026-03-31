<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
</script>

<template>
    <Head title="Lead Boxes" />

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
                                Lead Boxes
                            </h1>
                            <p class="mt-2 max-w-3xl text-sm leading-relaxed text-gray-600">
                                This is the lead-box admin entry page. Create a new lead box to begin managing this area.
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="route('admin.lead-boxes.create')"
                                class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800"
                            >
                                Create Lead Box
                            </Link>
                        </div>
                    </div>
                </div>

                <div class="flex-1 p-6">
                    <div class="rounded-2xl border border-dashed border-gray-300 bg-gray-50 p-6">
                        <p class="text-sm leading-relaxed text-gray-600">
                            Use the create flow to add a Resource, Service, or Offer lead box.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
