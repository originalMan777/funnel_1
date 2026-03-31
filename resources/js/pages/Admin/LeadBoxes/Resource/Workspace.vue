<script setup lang="ts">
import LeadBlockRenderer from '@/components/public/lead/LeadBlockRenderer.vue';
import type { LeadBlockRenderModel } from '@/types/leadBlocks';

defineProps<{
    form: any;
    statuses: string[];
    visualPresets: string[];
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
                            Resource title
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
                            Short text
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
                            Icon key (curated)
                        </label>
                        <input
                            v-model="form.icon_key"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                            placeholder="book-open"
                        />
                        <p v-if="form.errors.icon_key" class="mt-2 text-xs text-red-600">
                            {{ form.errors.icon_key }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Resource visual preset
                        </label>
                        <select
                            v-model="form.visual_preset"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        >
                            <option v-for="preset in visualPresets" :key="preset" :value="preset">
                                {{ preset }}
                            </option>
                        </select>
                        <p v-if="form.errors.visual_preset" class="mt-2 text-xs text-red-600">
                            {{ form.errors.visual_preset }}
                        </p>
                    </div>
                </div>

                <div class="mt-5 flex items-center gap-3">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white shadow-sm hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-60"
                        :disabled="form.processing"
                    >
                        {{ form.processing ? 'Saving…' : 'Save' }}
                    </button>

                    <p v-if="form.recentlySuccessful" class="text-sm text-emerald-700">
                        Saved.
                    </p>
                </div>
            </div>
        </form>

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Preview
                </p>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    Uses the same renderer path as the public homepage slot.
                </p>

                <div class="mt-5">
                    <LeadBlockRenderer :model="previewModel" preview-mode />
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Notes
                </p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-gray-600">
                    <li>Only <span class="font-semibold text-gray-900">Active</span> Lead Boxes can be assigned to a slot.</li>
                    <li>Draft/Inactive Lead Boxes will never render publicly.</li>
                    <li>This pass only ships the <span class="font-semibold text-gray-900">home_intro</span> slot on the homepage.</li>
                </ul>
            </div>
        </div>
    </div>
</template>
