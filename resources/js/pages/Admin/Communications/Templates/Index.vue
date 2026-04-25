<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type TemplateSummary = {
    id: number;
    key: string;
    name: string;
    status: string;
    channel: string;
    category: string;
    description: string | null;
    bindings_count: number;
    binding_summary: {
        primary_label: string | null;
        additional_count: number;
    };
    current_version: {
        id: number;
        version_number: number;
        subject: string;
        preview_text: string | null;
        headline: string | null;
        text_body: string | null;
        html_body: string;
        is_published: boolean;
        published_at: string | null;
    } | null;
};

const props = defineProps<{
    templates: TemplateSummary[];
}>();

const stripHtml = (value: string) =>
    value
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

const bodyExcerpt = (template: TemplateSummary) => {
    const currentVersion = template.current_version;

    if (!currentVersion) {
        return 'No saved version yet.';
    }

    const source =
        currentVersion.text_body?.trim() ||
        stripHtml(currentVersion.html_body || '');

    if (!source) {
        return 'No message body saved yet.';
    }

    return source.length > 220 ? `${source.slice(0, 220).trim()}...` : source;
};

const bindingSummary = (template: TemplateSummary) => {
    if (template.bindings_count === 0) {
        return 'No bindings attached';
    }

    if (!template.binding_summary.primary_label) {
        return template.bindings_count === 1
            ? '1 binding'
            : `${template.bindings_count} bindings`;
    }

    if (template.binding_summary.additional_count === 0) {
        return template.binding_summary.primary_label;
    }

    return `${template.binding_summary.primary_label} +${template.binding_summary.additional_count} more`;
};

const statusClasses = (status: string) => {
    if (status === 'active') {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (status === 'archived') {
        return 'bg-gray-100 text-gray-700 ring-gray-200';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
};

const versionStateClasses = (isPublished: boolean) =>
    isPublished
        ? 'bg-emerald-50 text-emerald-700 ring-emerald-200'
        : 'bg-amber-50 text-amber-700 ring-amber-200';

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString() : 'Not published';
</script>

<template>
    <Head title="Communication Templates" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Communications
                        </p>
                        <h1
                            class="mt-2 text-3xl font-semibold tracking-tight text-gray-900"
                        >
                            Template Library
                        </h1>
                        <p class="mt-2 max-w-3xl text-sm text-gray-600">
                            Browse reusable email messages with enough subject
                            and body context to know what you are choosing
                            before opening the editor.
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

            <div
                v-if="templates.length === 0"
                class="rounded-2xl border border-dashed border-gray-300 bg-white p-10 text-center"
            >
                <h2 class="text-lg font-semibold text-gray-900">
                    No templates yet
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Create a communication template to start building reusable
                    email messages.
                </p>
            </div>

            <div v-else class="space-y-4">
                <article
                    v-for="template in props.templates"
                    :key="template.id"
                    class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold text-gray-900">
                                    {{ template.name }}
                                </h2>
                                <span
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                    :class="statusClasses(template.status)"
                                >
                                    {{ template.status }}
                                </span>
                                <span
                                    v-if="template.current_version"
                                    class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                    :class="
                                        versionStateClasses(
                                            template.current_version
                                                .is_published,
                                        )
                                    "
                                >
                                    {{
                                        template.current_version.is_published
                                            ? 'Published'
                                            : 'Draft'
                                    }}
                                </span>
                            </div>

                            <div
                                class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-gray-500"
                            >
                                <span>{{ template.key }}</span>
                                <span>{{ bindingSummary(template) }}</span>
                                <span v-if="template.current_version"
                                    >Version
                                    {{
                                        template.current_version.version_number
                                    }}</span
                                >
                                <span>{{ template.channel }}</span>
                            </div>

                            <p
                                v-if="template.description"
                                class="mt-3 text-sm text-gray-600"
                            >
                                {{ template.description }}
                            </p>
                        </div>

                        <div class="flex flex-wrap gap-3">
                            <Link
                                :href="
                                    route(
                                        'admin.communications.templates.edit',
                                        template.id,
                                    )
                                "
                                class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            >
                                Edit
                            </Link>
                            <Link
                                :href="
                                    route(
                                        'admin.communications.templates.show',
                                        template.id,
                                    )
                                "
                                class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                View
                            </Link>
                            <Link
                                v-if="template.current_version"
                                :href="`${route('admin.communications.templates.show', template.id)}#published-preview`"
                                class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            >
                                Preview
                            </Link>
                        </div>
                    </div>

                    <div
                        class="mt-5 grid gap-4 lg:grid-cols-[minmax(0,1.2fr),minmax(260px,0.8fr)]"
                    >
                        <div
                            class="rounded-2xl border border-gray-200 bg-gray-50 p-5"
                        >
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                            >
                                Current Message
                            </p>
                            <p
                                class="mt-3 text-base font-semibold text-gray-900"
                            >
                                {{
                                    template.current_version?.subject ||
                                    'No subject yet'
                                }}
                            </p>
                            <p
                                v-if="template.current_version?.preview_text"
                                class="mt-2 text-sm text-gray-600"
                            >
                                {{ template.current_version.preview_text }}
                            </p>
                            <p
                                v-else-if="template.current_version?.headline"
                                class="mt-2 text-sm text-gray-600"
                            >
                                {{ template.current_version.headline }}
                            </p>
                            <p class="mt-4 text-sm leading-6 text-gray-700">
                                {{ bodyExcerpt(template) }}
                            </p>
                        </div>

                        <div class="rounded-2xl border border-gray-200 p-5">
                            <p
                                class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                            >
                                Snapshot
                            </p>
                            <dl class="mt-4 space-y-3 text-sm text-gray-700">
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-gray-500">Category</dt>
                                    <dd
                                        class="text-right font-medium text-gray-900"
                                    >
                                        {{ template.category }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-gray-500">Bindings</dt>
                                    <dd
                                        class="text-right font-medium text-gray-900"
                                    >
                                        {{ bindingSummary(template) }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-gray-500">
                                        Current Version
                                    </dt>
                                    <dd
                                        class="text-right font-medium text-gray-900"
                                    >
                                        {{
                                            template.current_version
                                                ? `v${template.current_version.version_number}`
                                                : 'None'
                                        }}
                                    </dd>
                                </div>
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <dt class="text-gray-500">Published</dt>
                                    <dd
                                        class="text-right font-medium text-gray-900"
                                    >
                                        {{
                                            template.current_version
                                                ?.is_published
                                                ? formatDateTime(
                                                      template.current_version
                                                          .published_at,
                                                  )
                                                : 'Not published'
                                        }}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </AdminLayout>
</template>
