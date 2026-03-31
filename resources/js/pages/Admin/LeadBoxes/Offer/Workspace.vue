<script setup>
defineProps({
    isEdit: {
        type: Boolean,
        required: true,
    },
    form: {
        type: Object,
        required: true,
    },
    statuses: {
        type: Array,
        required: true,
    },
    icons: {
        type: Array,
        required: true,
    },
    submit: {
        type: Function,
        required: true,
    },
})
</script>

<template>
    <div class="grid gap-8 lg:grid-cols-[420px_1fr]">
        <form class="space-y-5" @submit.prevent="submit">
            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    General
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        >
                            <option v-for="status in statuses" :key="status" :value="status">
                                {{ status }}
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
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Right Side Copy
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Title
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
                            Breakdown line 1
                        </label>
                        <input
                            v-model="form.breakdown_line_1"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.breakdown_line_1" class="mt-2 text-xs text-red-600">
                            {{ form.errors.breakdown_line_1 }}
                        </p>
                    </div>

                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            Breakdown line 2
                        </label>
                        <input
                            v-model="form.breakdown_line_2"
                            type="text"
                            class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-3 text-sm text-gray-900"
                        />
                        <p v-if="form.errors.breakdown_line_2" class="mt-2 text-xs text-red-600">
                            {{ form.errors.breakdown_line_2 }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Center Call To Action
                </p>

                <div class="mt-4 space-y-4">
                    <div>
                        <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                            CTA line
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
                            Reassurance text
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
                    Left Side Support Points
                </p>

                <div class="mt-4 space-y-4">
                    <div
                        v-for="(point, index) in form.value_points"
                        :key="index"
                        class="rounded-xl bg-gray-50 p-4 ring-1 ring-black/5"
                    >
                        <div class="grid gap-3 md:grid-cols-[140px_1fr] md:items-center">
                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Icon
                                </label>
                                <select
                                    v-model="point.icon_key"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm text-gray-900"
                                >
                                    <option v-for="icon in icons" :key="icon" :value="icon">
                                        {{ icon }}
                                    </option>
                                </select>
                                <p
                                    v-if="form.errors[`value_points.${index}.icon_key`]"
                                    class="mt-2 text-xs text-red-600"
                                >
                                    {{ form.errors[`value_points.${index}.icon_key`] }}
                                </p>
                            </div>

                            <div>
                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                                    Line
                                </label>
                                <input
                                    v-model="point.line"
                                    type="text"
                                    class="mt-2 w-full rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm text-gray-900"
                                />
                                <p
                                    v-if="form.errors[`value_points.${index}.line`]"
                                    class="mt-2 text-xs text-red-600"
                                >
                                    {{ form.errors[`value_points.${index}.line`] }}
                                </p>
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

        <div class="space-y-4">
            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Preview
                </p>
                <p class="mt-2 text-sm leading-relaxed text-gray-600">
                    Offer layout: support text on the left, call to action in the middle, explanation text on the right.
                </p>

                <div class="mt-5 rounded-2xl border border-gray-200 bg-gray-50 p-6">
                    <div class="grid gap-6 lg:grid-cols-[1fr_0.9fr_1fr] lg:items-start">
                        <div class="space-y-3">
                            <div
                                v-for="(point, index) in form.value_points"
                                :key="`preview-${index}`"
                                class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-black/5"
                            >
                                <p class="text-sm font-semibold text-gray-900">
                                    {{ point.line || `Support point ${index + 1}` }}
                                </p>
                            </div>
                        </div>

                        <div class="rounded-2xl bg-white p-6 text-center shadow-sm ring-1 ring-black/5">
                            <p class="text-lg font-semibold text-gray-900">
                                {{ form.cta_line || 'Your call to action goes here' }}
                            </p>

                            <button
                                type="button"
                                class="mt-5 inline-flex items-center justify-center rounded-xl bg-gray-900 px-5 py-3 text-sm font-semibold text-white"
                            >
                                {{ form.button_text || 'Take the next step' }}
                            </button>

                            <p v-if="form.reassurance_text" class="mt-4 text-sm text-gray-600">
                                {{ form.reassurance_text }}
                            </p>
                        </div>

                        <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-black/5">
                            <p class="text-xl font-semibold tracking-tight text-gray-900">
                                {{ form.title || 'Right-side headline' }}
                            </p>

                            <p class="mt-4 text-sm leading-relaxed text-gray-600">
                                {{ form.breakdown_line_1 || 'First supporting breakdown line.' }}
                            </p>

                            <p class="mt-3 text-sm leading-relaxed text-gray-600">
                                {{ form.breakdown_line_2 || 'Second supporting breakdown line.' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="rounded-2xl border border-gray-200 p-5">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-gray-500">
                    Notes
                </p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-gray-600">
                    <li>Middle column is the action driver.</li>
                    <li>Left column supports the offer with short value points.</li>
                    <li>Right column explains the offer in two clean lines.</li>
                </ul>
            </div>
        </div>
    </div>
</template>
