<script setup lang="ts">
import axios from 'axios';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';

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
    defaults: {
        from_email: string;
        from_name: string;
    };
}>();

const form = useForm({
    to_email: '',
    to_name: '',
    from_email: props.defaults.from_email ?? '',
    from_name: props.defaults.from_name ?? '',
    subject: '',
    message: '',
    preview_text: '',
    headline: '',
    sample_payload_json: '{}',
});

const previewLoading = ref(false);
const previewError = ref('');
const previewResult = ref<PreviewResponse['rendered'] | null>(null);

const buildPayload = () => {
    form.clearErrors('sample_payload_json');

    let samplePayload: Record<string, unknown> = {};

    try {
        samplePayload = form.sample_payload_json.trim() === ''
            ? {}
            : JSON.parse(form.sample_payload_json);
    } catch {
        form.setError('sample_payload_json', 'Sample payload must be valid JSON.');
    }

    if (form.errors.sample_payload_json) {
        return null;
    }

    return {
        to_email: form.to_email,
        to_name: form.to_name || null,
        from_email: form.from_email,
        from_name: form.from_name || null,
        subject: form.subject,
        message: form.message,
        preview_text: form.preview_text || null,
        headline: form.headline || null,
        sample_payload: samplePayload,
    };
};

const renderPreview = async () => {
    const payload = buildPayload();

    if (!payload) {
        return;
    }

    previewLoading.value = true;
    previewError.value = '';

    try {
        const response = await axios.post<PreviewResponse>(
            route('admin.communications.composer.preview'),
            payload,
            {
                headers: {
                    Accept: 'application/json',
                },
            },
        );

        previewResult.value = response.data.rendered;
    } catch {
        previewError.value = 'Preview could not be generated. Check the draft and try again.';
    } finally {
        previewLoading.value = false;
    }
};

const sendEmail = () => {
    const payload = buildPayload();

    if (!payload) {
        return;
    }

    form
        .transform(() => payload)
        .post(route('admin.communications.composer.send'), {
            preserveScroll: true,
        });
};
</script>

<template>
    <Head title="Email Composer" />

    <AdminLayout>
        <div class="space-y-6 p-4">
            <div class="rounded-2xl border border-gray-200 bg-white p-6">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">Communications</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-gray-900">Email Composer</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Write, preview, and send a one-off email without going through the template system.
                </p>
            </div>

            <div class="grid gap-6 xl:grid-cols-[minmax(0,1.3fr),minmax(360px,0.9fr)]">
                <div class="space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Recipients</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Make it obvious who this email is going to and who it is coming from.
                        </p>

                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">To Email</span>
                                <input v-model="form.to_email" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.to_email" class="mt-1 text-sm text-red-600">{{ form.errors.to_email }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">To Name</span>
                                <input v-model="form.to_name" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.to_name" class="mt-1 text-sm text-red-600">{{ form.errors.to_name }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">From Email</span>
                                <input v-model="form.from_email" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.from_email" class="mt-1 text-sm text-red-600">{{ form.errors.from_email }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">From Name</span>
                                <input v-model="form.from_name" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.from_name" class="mt-1 text-sm text-red-600">{{ form.errors.from_name }}</p>
                            </label>
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Message</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            Keep the subject and body front and center so this reads like a real human email workspace.
                        </p>

                        <div class="mt-6 grid gap-4">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Subject</span>
                                <input v-model="form.subject" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.subject" class="mt-1 text-sm text-red-600">{{ form.errors.subject }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Message Body</span>
                                <textarea
                                    v-model="form.message"
                                    rows="16"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Use plain text. Line breaks and paragraphs are preserved in preview and send.
                                </p>
                                <p v-if="form.errors.message" class="mt-1 text-sm text-red-600">{{ form.errors.message }}</p>
                            </label>
                        </div>
                    </section>

                    <details class="rounded-2xl border border-gray-200 bg-white p-6">
                        <summary class="cursor-pointer text-lg font-semibold text-gray-900">Advanced</summary>
                        <p class="mt-2 text-sm text-gray-600">
                            Optional fields for preview text, headline, and sample payload variables.
                        </p>

                        <div class="mt-6 grid gap-4">
                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Preview Text</span>
                                <input v-model="form.preview_text" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.preview_text" class="mt-1 text-sm text-red-600">{{ form.errors.preview_text }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Headline</span>
                                <input v-model="form.headline" class="w-full rounded-md border border-gray-300 px-3 py-2" />
                                <p v-if="form.errors.headline" class="mt-1 text-sm text-red-600">{{ form.errors.headline }}</p>
                            </label>

                            <label class="text-sm text-gray-700">
                                <span class="mb-1 block font-medium">Sample Payload JSON</span>
                                <textarea
                                    v-model="form.sample_payload_json"
                                    rows="8"
                                    class="w-full rounded-md border border-gray-300 px-3 py-2 font-mono text-sm"
                                />
                                <p class="mt-1 text-xs text-gray-500">
                                    Optional data for placeholders like <code v-pre>{{ recipient.name }}</code>.
                                </p>
                                <p v-if="form.errors.sample_payload_json" class="mt-1 text-sm text-red-600">{{ form.errors.sample_payload_json }}</p>
                                <p v-if="form.errors.sample_payload" class="mt-1 text-sm text-red-600">{{ form.errors.sample_payload }}</p>
                            </label>
                        </div>
                    </details>
                </div>

                <div class="space-y-6">
                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-semibold text-gray-900">Preview</h2>
                                <p class="mt-1 text-sm text-gray-600">
                                    Render the current draft before sending.
                                </p>
                            </div>

                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="previewLoading"
                                @click="renderPreview"
                            >
                                {{ previewLoading ? 'Rendering…' : 'Render Preview' }}
                            </button>
                        </div>

                        <p v-if="previewError" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ previewError }}
                        </p>

                        <div v-if="previewResult" class="mt-4 space-y-4">
                            <div class="rounded-xl border border-gray-200 p-4 text-sm">
                                <p><span class="font-medium text-gray-900">Subject:</span> {{ previewResult.subject }}</p>
                                <p class="mt-2"><span class="font-medium text-gray-900">Preview text:</span> {{ previewResult.preview_text || '—' }}</p>
                                <p class="mt-2"><span class="font-medium text-gray-900">Headline:</span> {{ previewResult.headline || '—' }}</p>
                            </div>

                            <div class="rounded-xl border border-gray-200 bg-gray-50 p-4">
                                <p class="mb-3 text-sm font-medium text-gray-900">Rendered email</p>
                                <div class="prose prose-sm max-w-none rounded-lg bg-white p-4" v-html="previewResult.html_body" />
                            </div>
                        </div>

                        <div v-else class="mt-4 rounded-xl border border-dashed border-gray-300 p-4 text-sm text-gray-600">
                            The preview shows exactly how the current draft will render.
                        </div>
                    </section>

                    <section class="rounded-2xl border border-gray-200 bg-white p-6">
                        <h2 class="text-lg font-semibold text-gray-900">Send</h2>
                        <p class="mt-1 text-sm text-gray-600">
                            When the draft looks right, send it as a one-off email.
                        </p>

                        <div class="mt-4 flex flex-wrap gap-3">
                            <button
                                type="button"
                                class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                                :disabled="previewLoading"
                                @click="renderPreview"
                            >
                                {{ previewLoading ? 'Rendering…' : 'Refresh Preview' }}
                            </button>

                            <button
                                type="button"
                                class="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-800"
                                :disabled="form.processing"
                                @click="sendEmail"
                            >
                                {{ form.processing ? 'Sending…' : 'Send Email' }}
                            </button>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
