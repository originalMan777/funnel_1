<script setup lang="ts">
import axios from 'axios';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

type BindingDefinition = {
    event_key: string;
    label: string;
    actions: Array<{
        action_key: string;
        label: string;
    }>;
};

type BindingFormRow = {
    id?: number;
    event_key: string;
    action_key: string;
    event_label?: string;
    action_label?: string;
    channel?: string;
    is_enabled: boolean;
    priority: number;
};

type TemplateVersion = {
    id: number;
    version_number: number;
    subject: string;
    preview_text: string | null;
    headline: string | null;
    html_body: string;
    text_body: string | null;
    variables_schema: Record<string, unknown> | Array<unknown>;
    sample_payload: Record<string, unknown> | Array<unknown>;
    notes: string | null;
    is_published: boolean;
    published_at: string | null;
};

type PreviewResponse = {
    rendered: {
        subject: string;
        preview_text: string | null;
        headline: string | null;
        html_body: string;
        text_body: string | null;
    };
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
        editor_version: TemplateVersion | null;
        versions: TemplateVersion[];
        bindings: BindingFormRow[];
    };
    bindingDefinitions: BindingDefinition[];
}>();

const formatJson = (
    value: Record<string, unknown> | Array<unknown> | null | undefined,
) => JSON.stringify(value ?? {}, null, 2);

const stripHtml = (value: string) =>
    value
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

const bodyExcerpt = (version: TemplateVersion | null) => {
    if (!version) {
        return 'No saved message yet.';
    }

    const source =
        version.text_body?.trim() || stripHtml(version.html_body || '');

    if (!source) {
        return 'No body content saved yet.';
    }

    return source.length > 180 ? `${source.slice(0, 180).trim()}...` : source;
};

const templateForm = useForm({
    key: props.template.key,
    name: props.template.name,
    status: props.template.status,
    description: props.template.description ?? '',
    from_name_override: props.template.from_name_override ?? '',
    from_email_override: props.template.from_email_override ?? '',
    reply_to_email: props.template.reply_to_email ?? '',
    bindings: props.template.bindings.map((binding) => ({
        event_key: binding.event_key,
        action_key: binding.action_key,
        is_enabled: binding.is_enabled,
        priority: binding.priority,
    })),
});

const draftForm = useForm({
    subject: props.template.editor_version?.subject ?? '',
    preview_text: props.template.editor_version?.preview_text ?? '',
    headline: props.template.editor_version?.headline ?? '',
    html_body: props.template.editor_version?.html_body ?? '',
    text_body: props.template.editor_version?.text_body ?? '',
    sample_payload_json: formatJson(
        props.template.editor_version?.sample_payload ?? {},
    ),
    variables_schema_json: formatJson(
        props.template.editor_version?.variables_schema ?? [],
    ),
    notes: props.template.editor_version?.notes ?? '',
});

const testSendForm = useForm({
    to_email: '',
    to_name: '',
});

const previewLoading = ref(false);
const previewError = ref('');
const previewResult = ref<PreviewResponse['rendered'] | null>(
    props.template.editor_version
        ? {
              subject: props.template.editor_version.subject,
              preview_text: props.template.editor_version.preview_text,
              headline: props.template.editor_version.headline,
              html_body: props.template.editor_version.html_body,
              text_body: props.template.editor_version.text_body,
          }
        : null,
);
const publishingVersionId = ref<number | null>(null);

const hasVersions = computed(() => props.template.versions.length > 0);
const publishedVersion = computed(() => props.template.current_version);
const draftSourceLabel = computed(() => {
    if (!props.template.editor_version) {
        return 'No saved draft yet';
    }

    if (
        props.template.current_version_id === props.template.editor_version.id
    ) {
        return `Editing from published version ${props.template.editor_version.version_number}`;
    }

    return `Editing from latest saved version ${props.template.editor_version.version_number}`;
});

const statusClasses = computed(() => {
    if (templateForm.status === 'active') {
        return 'bg-emerald-50 text-emerald-700 ring-emerald-200';
    }

    if (templateForm.status === 'archived') {
        return 'bg-gray-100 text-gray-700 ring-gray-200';
    }

    return 'bg-amber-50 text-amber-700 ring-amber-200';
});

const blankBinding = (): BindingFormRow => ({
    event_key: '',
    action_key: '',
    is_enabled: true,
    priority: 100,
});

const addBinding = () => {
    templateForm.bindings.push(blankBinding());
};

const removeBinding = (index: number) => {
    templateForm.bindings.splice(index, 1);
};

const actionsForEvent = (eventKey: string) =>
    props.bindingDefinitions.find(
        (definition) => definition.event_key === eventKey,
    )?.actions ?? [];

const updateBindingEvent = (index: number, eventKey: string) => {
    templateForm.bindings[index].event_key = eventKey;

    if (
        !actionsForEvent(eventKey).some(
            (action) =>
                action.action_key === templateForm.bindings[index].action_key,
        )
    ) {
        templateForm.bindings[index].action_key = '';
    }
};

const formatDateTime = (value: string | null) =>
    value ? new Date(value).toLocaleString() : 'Not published';

const buildDraftPayload = () => {
    draftForm.clearErrors('sample_payload_json', 'variables_schema_json');

    let samplePayload: Record<string, unknown> | Array<unknown> = {};
    let variablesSchema: Record<string, unknown> | Array<unknown> = [];

    try {
        samplePayload =
            draftForm.sample_payload_json.trim() === ''
                ? {}
                : JSON.parse(draftForm.sample_payload_json);
    } catch {
        draftForm.setError(
            'sample_payload_json',
            'Sample payload must be valid JSON.',
        );
    }

    try {
        variablesSchema =
            draftForm.variables_schema_json.trim() === ''
                ? []
                : JSON.parse(draftForm.variables_schema_json);
    } catch {
        draftForm.setError(
            'variables_schema_json',
            'Variables schema must be valid JSON.',
        );
    }

    if (
        draftForm.errors.sample_payload_json ||
        draftForm.errors.variables_schema_json
    ) {
        return null;
    }

    return {
        subject: draftForm.subject,
        preview_text: draftForm.preview_text || null,
        headline: draftForm.headline || null,
        html_body: draftForm.html_body,
        text_body: draftForm.text_body || null,
        sample_payload: samplePayload,
        variables_schema: variablesSchema,
        notes: draftForm.notes || null,
    };
};

const saveSettings = () => {
    templateForm.put(
        route('admin.communications.templates.update', props.template.id),
        {
            preserveScroll: true,
        },
    );
};

const saveDraftVersion = () => {
    const payload = buildDraftPayload();

    if (!payload) {
        return;
    }

    draftForm
        .transform(() => payload)
        .post(
            route(
                'admin.communications.templates.versions.store',
                props.template.id,
            ),
            {
                preserveScroll: true,
            },
        );
};

const refreshPreview = async () => {
    const payload = buildDraftPayload();

    if (!payload) {
        return;
    }

    previewLoading.value = true;
    previewError.value = '';

    try {
        const response = await axios.post<PreviewResponse>(
            route('admin.communications.templates.preview', props.template.id),
            payload,
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        previewResult.value = response.data.rendered;
    } catch {
        previewError.value =
            'Preview could not be generated. Check the draft fields and try again.';
    } finally {
        previewLoading.value = false;
    }
};

const sendTest = () => {
    const payload = buildDraftPayload();

    if (!payload) {
        return;
    }

    testSendForm.clearErrors();
    testSendForm
        .transform(() => ({
            ...payload,
            to_email: testSendForm.to_email,
            to_name: testSendForm.to_name || null,
            sample_payload: payload.sample_payload,
        }))
        .post(
            route(
                'admin.communications.templates.test-send',
                props.template.id,
            ),
            {
                preserveScroll: true,
            },
        );
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
</script>

<template>
    <Head :title="`Edit ${template.name}`" />

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
                                :class="statusClasses"
                            >
                                {{ templateForm.status }}
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
                            The message workspace stays front and center while
                            the existing template settings, bindings, preview,
                            test send, and version history remain intact.
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
                                    'admin.communications.templates.show',
                                    template.id,
                                )
                            "
                            class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            View template
                        </Link>
                        <button
                            type="button"
                            class="inline-flex rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            :disabled="previewLoading"
                            @click="refreshPreview"
                        >
                            {{
                                previewLoading
                                    ? 'Rendering…'
                                    : 'Refresh preview'
                            }}
                        </button>
                        <button
                            type="button"
                            class="inline-flex rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                            :disabled="draftForm.processing"
                            @click="saveDraftVersion"
                        >
                            {{
                                draftForm.processing
                                    ? 'Saving…'
                                    : 'Save draft version'
                            }}
                        </button>
                    </div>
                </div>

                <div class="mt-6 grid gap-4 lg:grid-cols-3">
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Editing
                        </p>
                        <p class="mt-2 text-sm font-medium text-gray-900">
                            {{ draftSourceLabel }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{
                                template.editor_version
                                    ? `Draft subject: ${template.editor_version.subject}`
                                    : 'Start writing a new reusable email message.'
                            }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Published
                        </p>
                        <p class="mt-2 text-sm font-medium text-gray-900">
                            {{
                                publishedVersion
                                    ? `Version ${publishedVersion.version_number}`
                                    : 'No published version'
                            }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            {{
                                publishedVersion
                                    ? formatDateTime(
                                          publishedVersion.published_at,
                                      )
                                    : 'Publish from version history when ready.'
                            }}
                        </p>
                    </div>
                    <div
                        class="rounded-2xl border border-gray-200 bg-gray-50 p-4"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Bindings
                        </p>
                        <p class="mt-2 text-sm font-medium text-gray-900">
                            {{
                                templateForm.bindings.length === 1
                                    ? '1 active binding row'
                                    : `${templateForm.bindings.length} binding rows`
                            }}
                        </p>
                        <p class="mt-1 text-sm text-gray-600">
                            Existing event and action contracts stay unchanged.
                        </p>
                    </div>
                </div>
            </section>

            <div
                class="grid gap-6 xl:grid-cols-[minmax(0,1.35fr),minmax(360px,0.85fr)]"
            >
                <div class="space-y-6">
                    <form
                        class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6"
                        @submit.prevent="saveDraftVersion"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Message Workspace
                                </p>
                                <h2
                                    class="mt-2 text-2xl font-semibold text-gray-900"
                                >
                                    Write the reusable email
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    Keep the actual message primary: subject,
                                    preview text, headline, and the email body
                                    you want this template to send.
                                </p>
                            </div>

                            <div class="text-right text-sm text-gray-600">
                                <p class="font-medium text-gray-900">
                                    {{
                                        template.editor_version
                                            ? `Latest saved version ${template.editor_version.version_number}`
                                            : 'Unsaved draft'
                                    }}
                                </p>
                                <p class="mt-1">
                                    {{
                                        publishedVersion
                                            ? `Published version ${publishedVersion.version_number} is live`
                                            : 'Nothing is published yet'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div class="grid gap-4">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >Subject</span
                                >
                                <input
                                    v-model="draftForm.subject"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="draftForm.errors.subject"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ draftForm.errors.subject }}
                                </p>
                            </label>

                            <div class="grid gap-4 md:grid-cols-2">
                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium"
                                        >Preview Text</span
                                    >
                                    <input
                                        v-model="draftForm.preview_text"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    <p
                                        v-if="draftForm.errors.preview_text"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ draftForm.errors.preview_text }}
                                    </p>
                                </label>

                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium"
                                        >Headline</span
                                    >
                                    <input
                                        v-model="draftForm.headline"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    <p
                                        v-if="draftForm.errors.headline"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ draftForm.errors.headline }}
                                    </p>
                                </label>
                            </div>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >Email Body</span
                                >
                                <textarea
                                    v-model="draftForm.html_body"
                                    rows="18"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    This field still maps to
                                    <code>html_body</code>; the label is
                                    simplified so the page reads like an email
                                    authoring workspace.
                                </p>
                                <p
                                    v-if="draftForm.errors.html_body"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ draftForm.errors.html_body }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >Text Body</span
                                >
                                <textarea
                                    v-model="draftForm.text_body"
                                    rows="8"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Optional fallback copy for channels or mail
                                    clients that depend on plain text.
                                </p>
                                <p
                                    v-if="draftForm.errors.text_body"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ draftForm.errors.text_body }}
                                </p>
                            </label>
                        </div>

                        <div class="grid gap-4 lg:grid-cols-2">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >Sample Payload</span
                                >
                                <textarea
                                    v-model="draftForm.sample_payload_json"
                                    rows="10"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Used for the existing preview and draft
                                    test-send flows.
                                </p>
                                <p
                                    v-if="draftForm.errors.sample_payload_json"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ draftForm.errors.sample_payload_json }}
                                </p>
                                <p
                                    v-if="draftForm.errors.sample_payload"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ draftForm.errors.sample_payload }}
                                </p>
                            </label>

                            <div class="space-y-4">
                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium"
                                        >System Schema Reference</span
                                    >
                                    <textarea
                                        v-model="
                                            draftForm.variables_schema_json
                                        "
                                        rows="8"
                                        readonly
                                        class="w-full rounded-md border border-gray-300 bg-gray-50 px-3 py-2 font-mono text-sm text-gray-600"
                                    />
                                    <p class="mt-1 text-xs text-gray-500">
                                        Read-only schema snapshot detected from
                                        the existing template version. It is
                                        shown here for reference and preserved
                                        when you save a new version.
                                    </p>
                                    <p
                                        v-if="
                                            draftForm.errors
                                                .variables_schema_json
                                        "
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{
                                            draftForm.errors
                                                .variables_schema_json
                                        }}
                                    </p>
                                    <p
                                        v-if="draftForm.errors.variables_schema"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ draftForm.errors.variables_schema }}
                                    </p>
                                </label>

                                <label class="text-sm text-gray-700">
                                    <span class="mb-1 block font-medium"
                                        >Version Notes</span
                                    >
                                    <textarea
                                        v-model="draftForm.notes"
                                        rows="4"
                                        class="w-full rounded-md border border-gray-300 px-3 py-2"
                                    />
                                    <p
                                        v-if="draftForm.errors.notes"
                                        class="mt-1 text-sm text-red-600"
                                    >
                                        {{ draftForm.errors.notes }}
                                    </p>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-wrap justify-end gap-3">
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="previewLoading"
                                @click="refreshPreview"
                            >
                                {{
                                    previewLoading
                                        ? 'Rendering…'
                                        : 'Refresh preview'
                                }}
                            </button>

                            <button
                                type="submit"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                :disabled="draftForm.processing"
                            >
                                {{
                                    draftForm.processing
                                        ? 'Saving…'
                                        : 'Save draft version'
                                }}
                            </button>
                        </div>
                    </form>

                    <form
                        class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6"
                        @submit.prevent="saveSettings"
                    >
                        <div
                            class="flex flex-wrap items-start justify-between gap-4"
                        >
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Delivery / Sender Settings
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    Template identity and sender details
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    Keep the existing template metadata, sender
                                    overrides, and backend-facing identifiers
                                    aligned with the rest of the communications
                                    system.
                                </p>
                            </div>

                            <button
                                type="submit"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                :disabled="templateForm.processing"
                            >
                                {{
                                    templateForm.processing
                                        ? 'Saving…'
                                        : 'Save settings'
                                }}
                            </button>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Name</span>
                                <input
                                    v-model="templateForm.name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="templateForm.errors.name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.name }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Key</span>
                                <input
                                    v-model="templateForm.key"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="templateForm.errors.key"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.key }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >Status</span
                                >
                                <select
                                    v-model="templateForm.status"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                >
                                    <option value="draft">draft</option>
                                    <option value="active">active</option>
                                    <option value="archived">archived</option>
                                </select>
                                <p
                                    v-if="templateForm.errors.status"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.status }}
                                </p>
                            </label>

                            <div
                                class="rounded-xl border border-dashed border-gray-300 px-4 py-3 text-sm text-gray-600"
                            >
                                <p>
                                    <span class="font-medium text-gray-900"
                                        >Channel:</span
                                    >
                                    {{ template.channel }}
                                </p>
                                <p class="mt-1">
                                    <span class="font-medium text-gray-900"
                                        >Category:</span
                                    >
                                    {{ template.category }}
                                </p>
                            </div>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >From Name Override</span
                                >
                                <input
                                    v-model="templateForm.from_name_override"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="
                                        templateForm.errors.from_name_override
                                    "
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.from_name_override }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >From Email Override</span
                                >
                                <input
                                    v-model="templateForm.from_email_override"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="
                                        templateForm.errors.from_email_override
                                    "
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{
                                        templateForm.errors.from_email_override
                                    }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700 md:col-span-2">
                                <span class="mb-1 block font-medium"
                                    >Reply-To Email</span
                                >
                                <input
                                    v-model="templateForm.reply_to_email"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="templateForm.errors.reply_to_email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.reply_to_email }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700 md:col-span-2">
                                <span class="mb-1 block font-medium"
                                    >Description</span
                                >
                                <textarea
                                    v-model="templateForm.description"
                                    rows="3"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="templateForm.errors.description"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ templateForm.errors.description }}
                                </p>
                            </label>
                        </div>

                        <section
                            class="rounded-2xl border border-gray-200 bg-gray-50 p-5"
                        >
                            <div
                                class="flex items-center justify-between gap-4"
                            >
                                <div>
                                    <p
                                        class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                    >
                                        Bindings
                                    </p>
                                    <h3
                                        class="mt-2 text-lg font-semibold text-gray-900"
                                    >
                                        Event bindings
                                    </h3>
                                    <p class="mt-1 text-sm text-gray-600">
                                        Attach this template to the existing
                                        event and action combinations supported
                                        by the backend.
                                    </p>
                                </div>

                                <button
                                    type="button"
                                    class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                    @click="addBinding"
                                >
                                    Add binding
                                </button>
                            </div>

                            <div class="mt-4 space-y-4">
                                <div
                                    v-if="templateForm.bindings.length === 0"
                                    class="rounded-xl border border-dashed border-gray-300 bg-white p-4 text-sm text-gray-600"
                                >
                                    No bindings selected yet.
                                </div>

                                <div
                                    v-for="(
                                        binding, index
                                    ) in templateForm.bindings"
                                    :key="index"
                                    class="rounded-xl border border-gray-200 bg-white p-4"
                                >
                                    <div class="grid gap-4 md:grid-cols-2">
                                        <label class="text-sm text-gray-700">
                                            <span class="mb-1 block font-medium"
                                                >Event</span
                                            >
                                            <select
                                                :value="binding.event_key"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2"
                                                @change="
                                                    updateBindingEvent(
                                                        index,
                                                        (
                                                            $event.target as HTMLSelectElement
                                                        ).value,
                                                    )
                                                "
                                            >
                                                <option value="">
                                                    Select event
                                                </option>
                                                <option
                                                    v-for="definition in bindingDefinitions"
                                                    :key="definition.event_key"
                                                    :value="
                                                        definition.event_key
                                                    "
                                                >
                                                    {{ definition.label }}
                                                </option>
                                            </select>
                                            <p
                                                v-if="
                                                    templateForm.errors[
                                                        `bindings.${index}.event_key`
                                                    ]
                                                "
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{
                                                    templateForm.errors[
                                                        `bindings.${index}.event_key`
                                                    ]
                                                }}
                                            </p>
                                        </label>

                                        <label class="text-sm text-gray-700">
                                            <span class="mb-1 block font-medium"
                                                >Action</span
                                            >
                                            <select
                                                v-model="binding.action_key"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2"
                                                :disabled="!binding.event_key"
                                            >
                                                <option value="">
                                                    Select action
                                                </option>
                                                <option
                                                    v-for="action in actionsForEvent(
                                                        binding.event_key,
                                                    )"
                                                    :key="action.action_key"
                                                    :value="action.action_key"
                                                >
                                                    {{ action.label }}
                                                </option>
                                            </select>
                                            <p
                                                v-if="
                                                    templateForm.errors[
                                                        `bindings.${index}.action_key`
                                                    ]
                                                "
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{
                                                    templateForm.errors[
                                                        `bindings.${index}.action_key`
                                                    ]
                                                }}
                                            </p>
                                        </label>

                                        <label class="text-sm text-gray-700">
                                            <span class="mb-1 block font-medium"
                                                >Priority</span
                                            >
                                            <input
                                                v-model="binding.priority"
                                                type="number"
                                                min="1"
                                                class="w-full rounded-md border border-gray-300 px-3 py-2"
                                            />
                                            <p
                                                v-if="
                                                    templateForm.errors[
                                                        `bindings.${index}.priority`
                                                    ]
                                                "
                                                class="mt-1 text-sm text-red-600"
                                            >
                                                {{
                                                    templateForm.errors[
                                                        `bindings.${index}.priority`
                                                    ]
                                                }}
                                            </p>
                                        </label>

                                        <div
                                            class="flex items-end justify-between gap-4"
                                        >
                                            <label
                                                class="inline-flex items-center gap-3 text-sm text-gray-700"
                                            >
                                                <input
                                                    v-model="binding.is_enabled"
                                                    type="checkbox"
                                                    class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-300"
                                                />
                                                <span class="font-medium"
                                                    >Binding enabled</span
                                                >
                                            </label>

                                            <button
                                                type="button"
                                                class="rounded-md border border-red-200 px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50"
                                                @click="removeBinding(index)"
                                            >
                                                Remove
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>
                    </form>
                </div>

                <div class="space-y-6">
                    <section
                        id="live-preview"
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <p
                                    class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                                >
                                    Live Preview
                                </p>
                                <h2
                                    class="mt-2 text-xl font-semibold text-gray-900"
                                >
                                    Current draft render
                                </h2>
                                <p class="mt-2 text-sm text-gray-600">
                                    Uses the existing preview endpoint against
                                    the fields in this editor.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="previewLoading"
                                @click="refreshPreview"
                            >
                                {{ previewLoading ? 'Rendering…' : 'Render' }}
                            </button>
                        </div>

                        <p
                            v-if="previewError"
                            class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700"
                        >
                            {{ previewError }}
                        </p>

                        <div v-if="previewResult" class="mt-4 space-y-4">
                            <div
                                class="rounded-xl border border-gray-200 p-4 text-sm"
                            >
                                <p>
                                    <span class="font-medium text-gray-900"
                                        >Subject:</span
                                    >
                                    {{ previewResult.subject }}
                                </p>
                                <p class="mt-2">
                                    <span class="font-medium text-gray-900"
                                        >Preview text:</span
                                    >
                                    {{ previewResult.preview_text || '—' }}
                                </p>
                                <p class="mt-2">
                                    <span class="font-medium text-gray-900"
                                        >Headline:</span
                                    >
                                    {{ previewResult.headline || '—' }}
                                </p>
                            </div>

                            <div
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4"
                            >
                                <p
                                    class="mb-3 text-sm font-medium text-gray-900"
                                >
                                    Rendered email
                                </p>
                                <div
                                    class="prose prose-sm max-w-none rounded-lg bg-white p-4"
                                    v-html="previewResult.html_body"
                                />
                            </div>

                            <div class="rounded-xl border border-gray-200 p-4">
                                <p
                                    class="mb-3 text-sm font-medium text-gray-900"
                                >
                                    Text output
                                </p>
                                <pre
                                    class="text-sm whitespace-pre-wrap text-gray-700"
                                    >{{
                                        previewResult.text_body ||
                                        'No text body provided.'
                                    }}</pre
                                >
                            </div>
                        </div>

                        <div
                            v-else
                            class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600"
                        >
                            Render the current draft to inspect the message
                            before saving, testing, or publishing.
                        </div>
                    </section>

                    <form
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                        @submit.prevent="sendTest"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Test Send
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900">
                            Send this draft to yourself
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Sends the current editor content through the
                            existing template test-send flow.
                        </p>

                        <div class="mt-4 grid gap-4">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >To Email</span
                                >
                                <input
                                    v-model="testSendForm.to_email"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="testSendForm.errors.to_email"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ testSendForm.errors.to_email }}
                                </p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium"
                                    >To Name</span
                                >
                                <input
                                    v-model="testSendForm.to_name"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2"
                                />
                                <p
                                    v-if="testSendForm.errors.to_name"
                                    class="mt-1 text-sm text-red-600"
                                >
                                    {{ testSendForm.errors.to_name }}
                                </p>
                            </label>
                        </div>

                        <div class="mt-4 flex justify-end">
                            <button
                                type="submit"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                :disabled="testSendForm.processing"
                            >
                                {{
                                    testSendForm.processing
                                        ? 'Sending…'
                                        : 'Send draft test email'
                                }}
                            </button>
                        </div>
                    </form>

                    <section
                        class="rounded-2xl border border-gray-200 bg-white p-6"
                    >
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Published Snapshot
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900">
                            What is live right now
                        </h2>

                        <div
                            v-if="publishedVersion"
                            class="mt-4 space-y-3 text-sm text-gray-700"
                        >
                            <p>
                                <span class="font-medium text-gray-900"
                                    >Version:</span
                                >
                                {{ publishedVersion.version_number }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-900"
                                    >Subject:</span
                                >
                                {{ publishedVersion.subject }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-900"
                                    >Preview text:</span
                                >
                                {{ publishedVersion.preview_text || '—' }}
                            </p>
                            <p>
                                <span class="font-medium text-gray-900"
                                    >Published:</span
                                >
                                {{
                                    formatDateTime(
                                        publishedVersion.published_at,
                                    )
                                }}
                            </p>
                            <p
                                class="rounded-xl border border-gray-200 bg-gray-50 p-4 text-sm text-gray-700"
                            >
                                {{ bodyExcerpt(publishedVersion) }}
                            </p>
                        </div>

                        <p v-else class="mt-4 text-sm text-gray-600">
                            No version is currently published.
                        </p>
                    </section>
                </div>
            </div>

            <section class="rounded-2xl border border-gray-200 bg-white p-6">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <p
                            class="text-xs font-semibold tracking-[0.18em] text-gray-500 uppercase"
                        >
                            Version History
                        </p>
                        <h2 class="mt-2 text-xl font-semibold text-gray-900">
                            Saved drafts and published versions
                        </h2>
                        <p class="mt-2 text-sm text-gray-600">
                            Review older versions in readable form and publish
                            an existing version without leaving the editor.
                        </p>
                    </div>

                    <span class="text-sm text-gray-500"
                        >{{ template.versions.length }} versions</span
                    >
                </div>

                <div
                    v-if="!hasVersions"
                    class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600"
                >
                    No versions have been saved yet.
                </div>

                <div v-else class="mt-4 space-y-3">
                    <div
                        v-for="version in template.versions"
                        :key="version.id"
                        class="rounded-xl border p-4"
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
                                <div class="flex flex-wrap items-center gap-2">
                                    <p
                                        class="text-sm font-semibold text-gray-900"
                                    >
                                        Version {{ version.version_number }}
                                    </p>
                                    <span
                                        v-if="version.is_published"
                                        class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700"
                                    >
                                        Published
                                    </span>
                                    <span
                                        v-else-if="
                                            template.editor_version &&
                                            version.id ===
                                                template.editor_version.id
                                        "
                                        class="rounded-full bg-gray-100 px-2.5 py-1 text-xs font-semibold text-gray-700"
                                    >
                                        Draft source
                                    </span>
                                </div>
                                <p
                                    class="mt-2 text-base font-medium text-gray-900"
                                >
                                    {{ version.subject }}
                                </p>
                                <p
                                    v-if="version.preview_text"
                                    class="mt-1 text-sm text-gray-600"
                                >
                                    {{ version.preview_text }}
                                </p>
                                <p
                                    v-else-if="version.headline"
                                    class="mt-1 text-sm text-gray-600"
                                >
                                    {{ version.headline }}
                                </p>
                                <p class="mt-3 text-sm leading-6 text-gray-700">
                                    {{ bodyExcerpt(version) }}
                                </p>
                                <p class="mt-3 text-xs text-gray-500">
                                    {{ formatDateTime(version.published_at) }}
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
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="publishingVersionId === version.id"
                                @click="publishVersion(version.id)"
                            >
                                {{
                                    publishingVersionId === version.id
                                        ? 'Publishing…'
                                        : 'Publish'
                                }}
                            </button>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
