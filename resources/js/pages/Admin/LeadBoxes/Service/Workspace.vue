<script setup lang="ts">
import LeadBlockRenderer from '@/components/public/lead/LeadBlockRenderer.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

defineProps<{
    form: any;
    statuses: string[];
    icons: string[];
    previewModel: LeadBlockRenderModel;
    submit: () => void;
}>();
</script>

<template>
    <div class="grid gap-8 lg:grid-cols-[420px_1fr]">
        <form class="space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 p-5">
                <div class="space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        >
                            <option v-for="s in statuses" :key="s" :value="s">
                                {{ s }}
                            </option>
                        </select>
                        <p v-if="form.errors.status" class="mt-2 text-xs text-red-600">
                            {{ form.errors.status }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Internal name
                        </label>
                        <input
                            v-model="form.internal_name"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.internal_name" class="mt-2 text-xs text-red-600">
                            {{ form.errors.internal_name }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Explanation title (right column)
                        </label>
                        <input
                            v-model="form.title"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.title" class="mt-2 text-xs text-red-600">
                            {{ form.errors.title }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Explanation text (right column)
                        </label>
                        <textarea
                            v-model="form.short_text"
                            rows="4"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.short_text" class="mt-2 text-xs text-red-600">
                            {{ form.errors.short_text }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            CTA line (center column)
                        </label>
                        <input
                            v-model="form.cta_line"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.cta_line" class="mt-2 text-xs text-red-600">
                            {{ form.errors.cta_line }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Button text
                        </label>
                        <input
                            v-model="form.button_text"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.button_text" class="mt-2 text-xs text-red-600">
                            {{ form.errors.button_text }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Reassurance text (optional)
                        </label>
                        <input
                            v-model="form.reassurance_text"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.reassurance_text" class="mt-2 text-xs text-red-600">
                            {{ form.errors.reassurance_text }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Value points (left column)
                </p>

                <div class="mt-4 space-y-4">
                    <div v-for="(vp, idx) in form.value_points" :key="idx" class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5">
                        <div class="grid gap-3 md:grid-cols-[140px_1fr] md:items-center">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Icon
                                </label>
                                <select
                                    v-model="vp.icon_key"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                                >
                                    <option v-for="icon in icons" :key="icon" :value="icon">
                                        {{ icon }}
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Line
                                </label>
                                <input
                                    v-model="vp.line"
                                    type="text"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-900"
                                />
                            </div>
                        </div>
                    </div>

                    <p v-if="form.errors.value_points" class="text-xs text-red-600">
                        {{ form.errors.value_points }}
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                    :disabled="form.processing"
                >
                    {{ form.processing ? 'Saving…' : 'Save' }}
                </button>

                <p v-if="form.recentlySuccessful" class="text-sm font-semibold text-emerald-700">
                    Saved.
                </p>
            </div>
        </form>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                Preview
            </p>
            <div class="mt-3">
                <LeadBlockRenderer :model="previewModel" preview-mode />
            </div>
        </div>
    </div>
</template>
