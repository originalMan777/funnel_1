<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type TemplateVersion = {
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
};

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
        current_version: TemplateVersion | null;
        versions: TemplateVersion[];
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

const stripHtml = (value: string) =>
    value
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

const bodyExcerpt = (version: TemplateVersion) => {
    const source =
        version.text_body?.trim() || stripHtml(version.html_body || '');

    if (!source) {
        return 'No body content saved for this version.';
    }

    return source.length > 260 ? `${source.slice(0, 260).trim()}...` : source;
};

const publishVersion = (versionId: number) => {
    publishingVersionId.value = versionId;

    router.post(
        route('admin.communications.templates.versions.publish', [
            props.template.id,
            versionId,
        ]),
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

const statusClasses = (status: string) => {
    if (status === 'active') {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (status === 'archived') {
        return 'bg-gray-100 text-gray-700 ring-gray-200';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
};
</script>

<template>
    <Head :title="template.name" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <section class="rounded-2xl border border-gray-200 bg-white p-6">
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
                            {{ template.name }}
                        </h1>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <span class="text-sm text-gray-500">{{
                                template.key
                            }}</span>
                            <span
                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset"
                                :class="statusClasses(template.status)"
                            >
                                {{ template.status }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 ring-inset"
                            >
                                {{ template.channel }}
                            </span>
                            <span
                                class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700 ring-1 ring-gray-200 ring-inset"
                            >
                                {{ template.category }}
                            </span>
                        </div>
                        <p class="mt-3 max-w-3xl text-sm text-gray-600">
                            Read the current published message first, then
                            review older versions with enough content detail to
                            understand what each one contains.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <Link
                            :href="
                                route('admin.communications.templates.index')
                            "
                            class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Back to templates
                        </Link>
                        <Link
                            :href="
                                route(
                                    'admin.communications.templates.edit',
                                    template.id,
                                )
                            "
                            class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                        >
                            Open editor
                        </Link>
                    </div>
                </div>
            </section>

            <div
                class="grid gap-6 lg:grid-cols-[minmax(0,1.3fr),minmax(320px,0.7fr)]"
            >
                <div class="space-y-6">
                    <section
                        id="published-preview"
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Current Published Version
                                </p>
                                <h2
                                    class="mt-2 text-2xl font-semibold text-gray-900"
                                >
                                    {{
                                        template.current_version
                                            ? `Version ${template.current_version.version_number}`
                                            : 'No published version'
                                    }}
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    {{
                                        template.current_version
                                            ? formatDateTime(
                                                  template.current_version
                                                      .published_at,
                                              )
                                            : 'Publish a version from the editor or version history.'
                                    }}
                                </p>
                            </div>

                            <span
                                v-if="template.current_version"
                                class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-200 ring-inset"
                            >
                                Published
                            </span>
                        </div>

                        <div
                            v-if="template.current_version"
                            class="mt-6 space-y-5"
                        >
                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-5"
                            >
                                <p class="text-sm font-medium text-gray-500">
                                    Subject
                                </p>
                                <p
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    {{ template.current_version.subject }}
                                </p>
                                <p
                                    v-if="template.current_version.preview_text"
                                    class="mt-3 text-sm text-gray-600"
                                >
                                    {{ template.current_version.preview_text }}
                                </p>
                                <p v-else class="mt-3 text-sm text-gray-500">
                                    No preview text saved.
                                </p>
                                <p
                                    v-if="template.current_version.headline"
                                    class="mt-3 text-sm font-medium text-gray-900"
                                >
                                    {{ template.current_version.headline }}
                                </p>
                            </div>

                            <div class="rounded-2xl border border-gray-200 p-5">
                                <p class="text-sm font-medium text-gray-500">
                                    Body excerpt
                                </p>
                                <p class="mt-3 text-sm leading-7 text-gray-700">
                                    {{ bodyExcerpt(template.current_version) }}
                                </p>
                            </div>

                            <div
                                class="rounded-2xl border border-gray-200 bg-gray-50 p-5"
                            >
                                <p
                                    class="mb-3 text-sm font-medium text-gray-900"
                                >
                                    Rendered preview
                                </p>
                                <div
                                    class="prose prose-sm max-w-none rounded-lg bg-white p-4"
                                    v-html="template.current_version.html_body"
                                />
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-6 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600"
                        >
                            No version is currently published for this template.
                        </div>
                    </section>

                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Version History
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    Older and draft versions
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    Each version keeps its message details
                                    visible so you can compare content, not just
                                    metadata.
                                </p>
                            </div>

                            <span class="text-sm text-gray-500"
                                >{{ template.versions.length }} versions</span
                            >
                        </div>

                        <div class="mt-4 space-y-4">
                            <div
                                v-if="template.versions.length === 0"
                                class="rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600"
                            >
                                No versions have been created yet.
                            </div>

                            <article
                                v-for="version in template.versions"
                                :key="version.id"
                                class="rounded-2xl border p-5"
                                :class="
                                    version.is_published
                                        ? 'border-emerald-200 bg-emerald-50'
                                        : 'border-gray-200 bg-white'
                                "
                            >
                                <div
                                    class="flex flex-wrap items-start justify-between gap-4"
                                >
                                    <div class="min-w-0 flex-1">
                                        <div
                                            class="flex flex-wrap items-center gap-2"
                                        >
                                            <p
                                                class="text-sm font-semibold text-gray-900"
                                            >
                                                Version
                                                {{ version.version_number }}
                                            </p>
                                            <span
                                                v-if="version.is_published"
                                                class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                            >
                                                Published
                                            </span>
                                        </div>

                                        <p
                                            class="mt-3 text-lg font-semibold text-gray-900"
                                        >
                                            {{ version.subject }}
                                        </p>
                                        <p
                                            v-if="version.preview_text"
                                            class="mt-2 text-sm text-gray-600"
                                        >
                                            {{ version.preview_text }}
                                        </p>
                                        <p
                                            v-else-if="version.headline"
                                            class="mt-2 text-sm text-gray-600"
                                        >
                                            {{ version.headline }}
                                        </p>
                                        <p
                                            class="mt-4 text-sm leading-7 text-gray-700"
                                        >
                                            {{ bodyExcerpt(version) }}
                                        </p>
                                        <p class="mt-3 text-xs text-gray-500">
                                            {{
                                                formatDateTime(
                                                    version.published_at,
                                                )
                                            }}
                                        </p>
                                        <p
                                            v-if="version.notes"
                                            class="mt-2 text-sm text-gray-600"
                                        >
                                            {{ version.notes }}
                                        </p>
                                    </div>

                                    <button
                                        v-if="!version.is_published"
                                        type="button"
                                        class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                        :disabled="
                                            publishingVersionId === version.id
                                        "
                                        @click="publishVersion(version.id)"
                                    >
                                        {{
                                            publishingVersionId === version.id
                                                ? 'Publishing…'
                                                : 'Publish'
                                        }}
                                    </button>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>

                <div class="space-y-6">
                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Template Details
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900">
                            Sender and delivery summary
                        </h2>

                        <dl class="mt-4 space-y-3 text-sm text-gray-700">
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-gray-500">Reply-To Email</dt>
                                <dd
                                    class="text-right font-medium text-gray-900"
                                >
                                    {{ template.reply_to_email || '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-gray-500">
                                    From Name Override
                                </dt>
                                <dd
                                    class="text-right font-medium text-gray-900"
                                >
                                    {{ template.from_name_override || '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-gray-500">
                                    From Email Override
                                </dt>
                                <dd
                                    class="text-right font-medium text-gray-900"
                                >
                                    {{ template.from_email_override || '—' }}
                                </dd>
                            </div>
                            <div class="flex items-start justify-between gap-4">
                                <dt class="text-gray-500">
                                    Current Published Version
                                </dt>
                                <dd
                                    class="text-right font-medium text-gray-900"
                                >
                                    {{
                                        template.current_version
                                            ? `Version ${template.current_version.version_number}`
                                            : 'None'
                                    }}
                                </dd>
                            </div>
                        </dl>

                        <p class="mt-4 text-sm text-gray-600">
                            {{
                                template.description ||
                                'No description provided.'
                            }}
                        </p>
                    </section>

                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Bindings
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900">
                            Where this template is used
                        </h2>

                        <div class="mt-4 space-y-3">
                            <div
                                v-if="template.bindings.length === 0"
                                class="text-sm text-gray-600"
                            >
                                No bindings attached to this template.
                            </div>

                            <div
                                v-for="binding in template.bindings"
                                :key="binding.id"
                                class="rounded-xl border border-gray-200 p-4 text-sm"
                            >
                                <p class="font-medium text-gray-900">
                                    {{ binding.event_label }}
                                </p>
                                <p class="mt-1 text-gray-600">
                                    {{ binding.action_label }}
                                </p>
                                <p class="mt-2 text-xs text-gray-500">
                                    {{ binding.event_key }} ·
                                    {{ binding.action_key }}
                                </p>
                                <p class="mt-1 text-xs text-gray-500">
                                    priority {{ binding.priority }} ·
                                    {{
                                        binding.is_enabled
                                            ? 'enabled'
                                            : 'disabled'
                                    }}
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
