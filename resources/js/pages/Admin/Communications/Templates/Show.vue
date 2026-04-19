<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

const props = defineProps<{
    template: {
        id: number;
        key: string;
        name: string;
        status: string;
        channel: string;
        category: string;
        description: string | null;
        from_name_override: string | null;
        from_email_override: string | null;
        reply_to_email: string | null;
        current_version_id: number | null;
        current_version: {
            id: number;
            version_number: number;
            subject: string;
            preview_text: string | null;
            headline: string | null;
            html_body: string;
            text_body: string | null;
            sample_payload: Record<string, unknown>;
            is_published: boolean;
            published_at: string | null;
        } | null;
        versions: Array<{
            id: number;
            version_number: number;
            subject: string;
            preview_text: string | null;
            headline: string | null;
            html_body: string;
            text_body: string | null;
            sample_payload: Record<string, unknown>;
            notes: string | null;
            is_published: boolean;
            published_at: string | null;
        }>;
        bindings: Array<{
            id: number;
            event_key: string;
            event_label: string;
            action_key: string;
            action_label: string;
            channel: string;
            is_enabled: boolean;
            priority: number;
        }>;
    };
}>();

const publishingVersionId = ref<number | null>(null);

const publishVersion = (versionId: number) => {
    publishingVersionId.value = versionId;

    router.post(
        route('admin.communications.templates.versions.publish', [props.template.id, versionId]),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                publishingVersionId.value = null;
            },
        },
    );
};

const formatDateTime = (value: string | null) => {
    if (!value) return 'Not published';

    return new Date(value).toLocaleString();
};
</script>

<template>
    <Head :title="template.name" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communications</p>
                        <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">{{ template.name }}</h1>
                        <p class="mt-2 text-sm text-gray-600">{{ template.key }}</p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="route('admin.communications.templates.index')"
                            class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Back to templates
                        </Link>
                        <Link
                            :href="route('admin.communications.templates.edit', template.id)"
                            class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Open editor
                        </Link>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Status</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ template.status }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Channel</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ template.channel }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Category</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ template.category }}</p>
                </div>
                <div class="rounded-2xl border border-gray-200 bg-white p-5">
                    <p class="text-sm text-gray-500">Versions</p>
                    <p class="mt-2 text-xl font-semibold text-gray-900">{{ template.versions.length }}</p>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.1fr,0.9fr]">
                <div class="space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Overview</h2>
                        <dl class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <dt class="text-sm text-gray-500">Reply-To Email</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ template.reply_to_email || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">From Name Override</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ template.from_name_override || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">From Email Override</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">{{ template.from_email_override || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm text-gray-500">Current Published Version</dt>
                                <dd class="mt-1 text-sm font-medium text-gray-900">
                                    {{ template.current_version ? `Version ${template.current_version.version_number}` : 'None' }}
                                </dd>
                            </div>
                        </dl>
                        <p class="mt-4 text-sm text-gray-600">{{ template.description || 'No description provided.' }}</p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Version History</h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Review saved drafts and publish the version that should become active.
                                </p>
                            </div>

                            <Link
                                :href="route('admin.communications.templates.edit', template.id)"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Edit content
                            </Link>
                        </div>

                        <div class="mt-4 space-y-4">
                            <div v-if="template.versions.length === 0" class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                                No versions have been created yet.
                            </div>

                            <div
                                v-for="version in template.versions"
                                :key="version.id"
                                class="rounded-xl border p-4"
                                :class="version.is_published ? 'border-emerald-200 bg-emerald-50' : 'border-gray-200 bg-white'"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-4">
                                    <div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-sm font-semibold text-gray-900">Version {{ version.version_number }}</p>
                                            <span
                                                v-if="version.is_published"
                                                class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                            >
                                                Published
                                            </span>
                                        </div>
                                        <p class="mt-2 text-sm text-gray-700">{{ version.subject }}</p>
                                        <p class="mt-1 text-xs text-gray-500">{{ formatDateTime(version.published_at) }}</p>
                                        <p v-if="version.notes" class="mt-2 text-sm text-gray-600">{{ version.notes }}</p>
                                    </div>

                                    <button
                                        v-if="!version.is_published"
                                        type="button"
                                        class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        :disabled="publishingVersionId === version.id"
                                        @click="publishVersion(version.id)"
                                    >
                                        {{ publishingVersionId === version.id ? 'Publishing…' : 'Publish' }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Publish Console</h2>
                        <div v-if="template.current_version" class="mt-4 space-y-3 text-sm text-gray-700">
                            <p><span class="font-medium text-gray-900">Subject:</span> {{ template.current_version.subject }}</p>
                            <p><span class="font-medium text-gray-900">Preview text:</span> {{ template.current_version.preview_text || '—' }}</p>
                            <p><span class="font-medium text-gray-900">Headline:</span> {{ template.current_version.headline || '—' }}</p>
                            <p><span class="font-medium text-gray-900">Published:</span> {{ formatDateTime(template.current_version.published_at) }}</p>
                        </div>
                        <p v-else class="mt-4 text-sm text-gray-600">
                            No version is published yet. Use the editor to save a draft, then publish it from the history list.
                        </p>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Bindings</h2>
                        <div class="mt-4 space-y-3">
                            <div v-if="template.bindings.length === 0" class="text-sm text-gray-600">
                                No bindings attached to this template.
                            </div>

                            <div
                                v-for="binding in template.bindings"
                                :key="binding.id"
                                class="rounded-xl border border-gray-200 p-4 text-sm"
                            >
                                <p class="font-medium text-gray-900">{{ binding.event_label }}</p>
                                <p class="mt-1 text-gray-600">{{ binding.action_label }}</p>
                                <p class="mt-2 text-xs text-gray-500">
                                    {{ binding.event_key }} · {{ binding.action_key }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    priority {{ binding.priority }} · {{ binding.is_enabled ? 'enabled' : 'disabled' }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
