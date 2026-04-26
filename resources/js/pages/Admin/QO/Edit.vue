<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AdminLayout from '@/AppLayouts/AdminLayout.vue';
import { computed, ref, watch } from 'vue';

defineOptions({ layout: AdminLayout });

type QOOption = {
    id: number;
    label: string;
    score_value: number | null;
    is_correct: boolean;
    category_key: string | null;
    sort_order: number;
};

type QOQuestionSettings = {
    placeholder?: string | null;
    min_length?: number | null;
    max_length?: number | null;
    min?: number | null;
    max?: number | null;
    step?: number | null;
};

type QOQuestion = {
    id: number;
    type: 'single_choice' | 'multiple_choice' | 'yes_no' | 'short_text' | 'number';
    prompt: string | null;
    helper_text: string | null;
    explanation_text: string | null;
    sort_order: number;
    is_required: boolean;
    settings_json?: QOQuestionSettings | null;
    options?: QOOption[];
};

type QOOutcome = {
    id: number;
    outcome_key: string;
    title: string;
    summary: string | null;
    body: string | null;
    min_score: number | null;
    max_score: number | null;
    category_key: string | null;
    cta_label: string | null;
    cta_url: string | null;
    lead_box_id: number | null;
};

const props = defineProps<{
    publishReadiness?: {
        is_publishable: boolean;
        errors: string[];
        warnings: string[];
    };

    item: null | {
        id: number;
        title: string;
        internal_name: string | null;
        slug: string;
        type: 'quiz' | 'assessment';
        status: 'draft' | 'published' | 'archived';
        intro_title: string | null;
        intro_body: string | null;
        start_button_label: string | null;
        interaction_mode: 'evaluative' | 'correctness';
        result_mode: 'score_range' | 'category' | 'mixed';
        capture_mode: 'open' | 'optional_pre_start' | 'pre_start_gate' | 'optional_pre_result' | 'pre_result_gate' | 'post_result_only' | 'hybrid';
        cta_config?: Record<string, {
            enabled: boolean;
            intent: 'low' | 'medium' | 'high' | 'ultra';
            behavior: 'optional' | 'required' | 'hidden';
            headline: string;
            body: string;
            primary_label: string;
        }>;
        allow_back: boolean;
        show_correctness_feedback: boolean;
        allow_second_chance: boolean;
        reveal_correct_answer_after_fail: boolean;
        show_explanations: boolean;
        questions?: QOQuestion[];
        outcomes?: QOOutcome[];
    };
}>();

const isEditing = computed(() => Boolean(props.item));
const questions = computed(() => props.item?.questions ?? []);
const outcomes = computed(() => props.item?.outcomes ?? []);

const selectedQuestionId = ref<number | null>(questions.value[0]?.id ?? null);

const selectedQuestion = computed(() => {
    return questions.value.find((question) => question.id === selectedQuestionId.value) ?? questions.value[0] ?? null;
});

const completedQuestionCount = computed(() => {
    return questions.value.filter((question) => Boolean(question.prompt?.trim())).length;
});

const form = useForm({
    title: props.item?.title ?? '',
    internal_name: props.item?.internal_name ?? '',
    slug: props.item?.slug ?? '',
    type: props.item?.type ?? 'assessment',
    status: props.item?.status ?? 'draft',
    intro_title: props.item?.intro_title ?? '',
    intro_body: props.item?.intro_body ?? '',
    start_button_label: props.item?.start_button_label ?? 'Start',
    interaction_mode: props.item?.interaction_mode ?? 'evaluative',
    result_mode: props.item?.result_mode ?? 'score_range',
    capture_mode: props.item?.capture_mode ?? 'optional_pre_result',
    cta_config: props.item?.cta_config ?? {
        pre_start: {
            enabled: true,
            intent: 'low',
            behavior: 'optional',
            headline: 'Get the full breakdown after your assessment.',
            body: 'Your score is only the surface. Reserve the expanded breakdown and next-step plan.',
            primary_label: 'Save My Breakdown',
        },
        mid_assessment: {
            enabled: true,
            intent: 'medium',
            behavior: 'optional',
            headline: 'You are halfway through.',
            body: 'Keep going. This slot can later connect to a progress-based offer or lead magnet.',
            primary_label: 'Save My Progress',
        },
        pre_result: {
            enabled: true,
            intent: 'high',
            behavior: 'optional',
            headline: 'Unlock the meaning behind your result.',
            body: 'Turn your answers into a useful breakdown with next-step direction.',
            primary_label: 'Unlock My Result',
        },
        post_result: {
            enabled: true,
            intent: 'ultra',
            behavior: 'optional',
            headline: 'Get your next-step action plan.',
            body: 'Use this result as the starting point for a stronger plan, consultation, or conversion path.',
            primary_label: 'Get My Action Plan',
        },
    },
    allow_back: props.item?.allow_back ?? true,
    show_correctness_feedback: props.item?.show_correctness_feedback ?? false,
    allow_second_chance: props.item?.allow_second_chance ?? false,
    reveal_correct_answer_after_fail: props.item?.reveal_correct_answer_after_fail ?? false,
    show_explanations: props.item?.show_explanations ?? false,
    question_count: 10,
    default_question_type: 'single_choice',
});


function currentFeedbackMode() {
    if (!form.show_correctness_feedback) return 'assessment';
    if (form.show_correctness_feedback && form.allow_second_chance) return 'practice';
    if (form.show_correctness_feedback && !form.allow_back) return 'locked';
    return 'quiz';
}

function setFeedbackMode(mode: string) {
    if (mode === 'assessment') {
        form.show_correctness_feedback = false;
        form.allow_second_chance = false;
        form.reveal_correct_answer_after_fail = false;
        form.show_explanations = false;
        form.allow_back = true;
        return;
    }

    if (mode === 'quiz') {
        form.show_correctness_feedback = true;
        form.allow_second_chance = false;
        form.reveal_correct_answer_after_fail = true;
        form.show_explanations = true;
        form.allow_back = true;
        return;
    }

    if (mode === 'practice') {
        form.show_correctness_feedback = true;
        form.allow_second_chance = true;
        form.reveal_correct_answer_after_fail = false;
        form.show_explanations = true;
        form.allow_back = true;
        return;
    }

    if (mode === 'locked') {
        form.show_correctness_feedback = true;
        form.allow_second_chance = false;
        form.reveal_correct_answer_after_fail = true;
        form.show_explanations = true;
        form.allow_back = false;
    }
}

const questionForm = useForm({
    type: selectedQuestion.value?.type ?? 'single_choice',
    prompt: selectedQuestion.value?.prompt ?? '',
    helper_text: selectedQuestion.value?.helper_text ?? '',
    explanation_text: selectedQuestion.value?.explanation_text ?? '',
    is_required: selectedQuestion.value?.is_required ?? true,
    settings_json: {
        placeholder: selectedQuestion.value?.settings_json?.placeholder ?? '',
        min_length: selectedQuestion.value?.settings_json?.min_length ?? null,
        max_length: selectedQuestion.value?.settings_json?.max_length ?? null,
        min: selectedQuestion.value?.settings_json?.min ?? null,
        max: selectedQuestion.value?.settings_json?.max ?? null,
        step: selectedQuestion.value?.settings_json?.step ?? 1,
    },
});

const selectedType = computed(() => questionForm.type);
const usesOptions = computed(() => ['single_choice', 'multiple_choice'].includes(selectedType.value));
const usesYesNo = computed(() => selectedType.value === 'yes_no');
const usesTextInput = computed(() => selectedType.value === 'short_text');
const usesNumberInput = computed(() => selectedType.value === 'number');

const outcomeWarnings = computed(() => {
    const warnings: string[] = [];

    if (!outcomes.value.length) {
        warnings.push('No outcomes exist yet.');
        return warnings;
    }

    outcomes.value.forEach((outcome) => {
        if (outcome.min_score !== null && outcome.max_score !== null && Number(outcome.min_score) > Number(outcome.max_score)) {
            warnings.push(`${outcome.title || outcome.outcome_key} has a min score higher than max score.`);
        }
    });

    const scoreOutcomes = outcomes.value
        .filter((outcome) => outcome.min_score !== null || outcome.max_score !== null)
        .map((outcome) => ({
            ...outcome,
            min: outcome.min_score ?? Number.NEGATIVE_INFINITY,
            max: outcome.max_score ?? Number.POSITIVE_INFINITY,
        }))
        .sort((a, b) => Number(a.min) - Number(b.min));

    for (let i = 0; i < scoreOutcomes.length - 1; i++) {
        const current = scoreOutcomes[i];
        const next = scoreOutcomes[i + 1];

        if (Number(current.max) >= Number(next.min)) {
            warnings.push(`${current.title} overlaps with ${next.title}.`);
        }
    }

    if (props.item?.result_mode === 'category') {
        const hasCategoryOutcome = outcomes.value.some((outcome) => outcome.category_key);
        if (!hasCategoryOutcome) {
            warnings.push('Category result mode needs at least one outcome with a category key.');
        }
    }

    return warnings;
});

watch(selectedQuestion, (question) => {
    questionForm.defaults({
        type: question?.type ?? 'single_choice',
        prompt: question?.prompt ?? '',
        helper_text: question?.helper_text ?? '',
        explanation_text: question?.explanation_text ?? '',
        is_required: question?.is_required ?? true,
        settings_json: {
            placeholder: question?.settings_json?.placeholder ?? '',
            min_length: question?.settings_json?.min_length ?? null,
            max_length: question?.settings_json?.max_length ?? null,
            min: question?.settings_json?.min ?? null,
            max: question?.settings_json?.max ?? null,
            step: question?.settings_json?.step ?? 1,
        },
    });

    questionForm.reset();
});

watch(() => questionForm.type, (newType, oldType) => {
    if (!props.item || !selectedQuestion.value || newType === oldType) return;

    router.post(route('admin.qo.options.reset-for-type', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
    }), {
        type: newType,
    }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
});

function submit() {
    if (props.item) {
        form.put(route('admin.qo.update', props.item.id), { preserveScroll: true });
        return;
    }

    form.post(route('admin.qo.store'));
}

function saveQuestion() {
    if (!props.item || !selectedQuestion.value) return;

    questionForm.put(route('admin.qo.questions.update', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
    }), { preserveScroll: true });
}

function addQuestion() {
    if (!props.item) return;
    router.post(route('admin.qo.questions.store', props.item.id), { type: 'single_choice' }, { preserveScroll: true });
}

function deleteQuestion(question: QOQuestion) {
    if (!props.item || !confirm('Delete this question slot?')) return;

    router.delete(route('admin.qo.questions.destroy', {
        itemId: props.item.id,
        questionId: question.id,
    }), { preserveScroll: true });
}

function addOption() {
    if (!props.item || !selectedQuestion.value) return;

    router.post(route('admin.qo.options.store', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
    }), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}

function addSpecialOption(label: string) {
    if (!props.item || !selectedQuestion.value) return;

    router.post(route('admin.qo.options.store', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
    }), {
        label,
        score_value: 0,
        is_correct: false,
        category_key: null,
    }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}

function saveOption(option: QOOption) {
    if (!props.item || !selectedQuestion.value) return;

    router.put(route('admin.qo.options.update', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
        optionId: option.id,
    }), {
        label: option.label,
        score_value: option.score_value,
        is_correct: option.is_correct,
        category_key: option.category_key,
    }, { preserveScroll: true });
}

function deleteOption(option: QOOption) {
    if (!props.item || !selectedQuestion.value || !confirm('Delete this answer slot?')) return;

    router.delete(route('admin.qo.options.destroy', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
        optionId: option.id,
    }), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}

function resetOptionsForType() {
    if (!props.item || !selectedQuestion.value) return;

    if (!confirm('Reset answer slots for this question type? Existing slots will be deleted.')) return;

    router.post(route('admin.qo.options.reset-for-type', {
        itemId: props.item.id,
        questionId: selectedQuestion.value.id,
    }), {
        type: questionForm.type,
    }, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}

function addOutcome() {
    if (!props.item) return;

    router.post(route('admin.qo.outcomes.store', props.item.id), {}, {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}

function saveOutcome(outcome: QOOutcome) {
    if (!props.item) return;

    router.put(route('admin.qo.outcomes.update', {
        itemId: props.item.id,
        outcomeId: outcome.id,
    }), outcome, { preserveScroll: true });
}

function deleteOutcome(outcome: QOOutcome) {
    if (!props.item || !confirm('Delete this outcome?')) return;

    router.delete(route('admin.qo.outcomes.destroy', {
        itemId: props.item.id,
        outcomeId: outcome.id,
    }), {
        preserveScroll: true,
        onSuccess: () => router.reload({ only: ['item'], preserveScroll: true }),
    });
}
</script>

<template>
    <Head :title="isEditing ? 'Edit QO Item' : 'Create QO Item'" />

    <div class="space-y-6 p-6">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-slate-900">
                    {{ isEditing ? 'Edit QO Item' : 'Create QO Item' }}
                </h1>
                <p class="mt-1 text-sm text-slate-600">
                    Questions & Outcomes builder.
                </p>
            </div>

            <Link :href="route('admin.qo.index')" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Back
            </Link>
        </div>

        <form class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="submit">
            <div class="grid gap-4 md:grid-cols-2">
                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Title</span>
                    <input v-model="form.title" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Slug</span>
                    <input v-model="form.slug" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Type</span>
                    <select v-model="form.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="assessment">Assessment</option>
                        <option value="quiz">Quiz</option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Result Mode</span>
                    <select v-model="form.result_mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="score_range">Score Range</option>
                        <option value="category">Category</option>
                        <option value="mixed">Mixed</option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Capture Mode</span>
                    <select v-model="form.capture_mode" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="open">Open — no capture</option>
                        <option value="optional_pre_start">Optional before start</option>
                        <option value="pre_start_gate">Gate before start</option>
                        <option value="optional_pre_result">Optional before result</option>
                        <option value="pre_result_gate">Gate before result</option>
                        <option value="post_result_only">Post-result only</option>
                        <option value="hybrid">Hybrid — optional early, gated later</option>
                    </select>
                </label>

                <label class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Feedback Mode</span>
                    <select
                        :value="currentFeedbackMode()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        @change="setFeedbackMode(($event.target as HTMLSelectElement).value)"
                    >
                        <option value="assessment">Assessment — show result at end only</option>
                        <option value="quiz">Quiz — instant right/wrong</option>
                        <option value="practice">Practice — instant feedback + retry</option>
                        <option value="locked">Locked Test — answer locks, no backtracking</option>
                    </select>
                </label>

                <label v-if="!isEditing" class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">How many questions?</span>
                    <input v-model="form.question_count" type="number" min="1" max="100" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                </label>

                <label v-if="!isEditing" class="space-y-1">
                    <span class="text-sm font-medium text-slate-700">Default Question Type</span>
                    <select v-model="form.default_question_type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="single_choice">Single Choice</option>
                        <option value="multiple_choice">Multiple Choice</option>
                        <option value="yes_no">Yes / No</option>
                        <option value="short_text">Short Text</option>
                        <option value="number">Number</option>
                    </select>
                </label>
            </div>

            <div class="flex justify-end border-t border-slate-100 pt-5">
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white">
                    {{ isEditing ? 'Save QO Item' : 'Create QO Item + Generate Slots' }}
                </button>
            </div>
        </form>

        <section v-if="isEditing" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="mb-5">
                <h2 class="text-lg font-semibold text-slate-900">CTA Control Panel</h2>
                <p class="mt-1 text-sm text-slate-600">
                    Control the four conversion locations. Lead Slot connections come later; this locks the behavior and hierarchy now.
                </p>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div
                    v-for="(slot, key) in form.cta_config"
                    :key="key"
                    class="rounded-xl border border-slate-200 bg-slate-50 p-4"
                >
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <p class="text-sm font-semibold capitalize text-slate-900">
                                {{ String(key).replace('_', ' ') }}
                            </p>
                            <p class="mt-1 text-xs text-slate-500">
                                Location + intent controls how aggressive this CTA feels.
                            </p>
                        </div>

                        <label class="flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <input v-model="slot.enabled" type="checkbox" class="rounded border-slate-300" />
                            Enabled
                        </label>
                    </div>

                    <div class="grid gap-3 md:grid-cols-2">
                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Intent</span>
                            <select v-model="slot.intent" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="low">Low intent</option>
                                <option value="medium">Medium intent</option>
                                <option value="high">High intent</option>
                                <option value="ultra">Ultra intent</option>
                            </select>
                        </label>

                        <label class="space-y-1">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Behavior</span>
                            <select v-model="slot.behavior" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                <option value="optional">Optional</option>
                                <option value="required">Required / gated</option>
                                <option value="hidden">Hidden</option>
                            </select>
                        </label>

                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Headline</span>
                            <input v-model="slot.headline" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </label>

                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Body</span>
                            <textarea v-model="slot.body" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </label>

                        <label class="space-y-1 md:col-span-2">
                            <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Primary Button</span>
                            <input v-model="slot.primary_label" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        </label>
                    </div>
                </div>
            </div>

            <div class="mt-5 flex justify-end border-t border-slate-100 pt-5">
                <button
                    type="button"
                    class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white"
                    @click="submit"
                >
                    Save CTA Controls
                </button>
            </div>
        </section>

        <div v-if="isEditing" class="grid gap-6 lg:grid-cols-[320px_1fr]">
            <aside class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="mb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">Question Slots</h2>
                        <p class="mt-1 text-sm text-slate-700">{{ completedQuestionCount }} / {{ questions.length }} filled</p>
                    </div>

                    <button type="button" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white" @click="addQuestion">
                        Add
                    </button>
                </div>

                <div class="space-y-2">
                    <button
                        v-for="(question, index) in questions"
                        :key="question.id"
                        type="button"
                        class="flex w-full items-center justify-between rounded-lg border px-3 py-2 text-left text-sm"
                        :class="selectedQuestion?.id === question.id ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-700 hover:bg-slate-50'"
                        @click="selectedQuestionId = question.id"
                    >
                        <span>Question {{ index + 1 }}</span>
                        <span class="rounded-full px-2 py-0.5 text-xs" :class="question.prompt ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'">
                            {{ question.prompt ? 'Filled' : 'Empty' }}
                        </span>
                    </button>
                </div>
            </aside>

            <section class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                <form v-if="selectedQuestion" class="space-y-5" @submit.prevent="saveQuestion">
                    <div class="flex items-start justify-between">
                        <div>
                            <h2 class="text-lg font-semibold text-slate-900">Question {{ selectedQuestion.sort_order }}</h2>
                            <p class="text-sm text-slate-500">Configure this question slot.</p>
                        </div>

                        <button type="button" class="rounded-lg border border-red-200 px-3 py-1.5 text-xs font-semibold text-red-700 hover:bg-red-50" @click="deleteQuestion(selectedQuestion)">
                            Delete
                        </button>
                    </div>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium text-slate-700">Question Type</span>
                        <select v-model="questionForm.type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                            <option value="single_choice">Single Choice</option>
                            <option value="multiple_choice">Multiple Choice</option>
                            <option value="yes_no">Yes / No</option>
                            <option value="short_text">Short Text</option>
                            <option value="number">Number</option>
                        </select>
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium text-slate-700">Prompt</span>
                        <textarea v-model="questionForm.prompt" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium text-slate-700">Helper Text</span>
                        <textarea v-model="questionForm.helper_text" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </label>

                    <label class="block space-y-1">
                        <span class="text-sm font-medium text-slate-700">Explanation Text</span>
                        <textarea v-model="questionForm.explanation_text" rows="3" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </label>

                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input v-model="questionForm.is_required" type="checkbox" class="rounded border-slate-300" />
                        Required question
                    </label>

                    <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white">
                        Save Question
                    </button>
                </form>

                <div v-if="selectedQuestion" class="space-y-4 rounded-xl border border-slate-200 bg-slate-50 p-5">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">
                                <span v-if="selectedType === 'single_choice'">Single Choice Answer Slots</span>
                                <span v-else-if="selectedType === 'multiple_choice'">Multiple Choice Answer Slots</span>
                                <span v-else-if="selectedType === 'yes_no'">Yes / No Answer Slots</span>
                                <span v-else-if="selectedType === 'short_text'">Short Text Input</span>
                                <span v-else-if="selectedType === 'number'">Number Input</span>
                            </h3>
                            <p class="text-sm text-slate-600">
                                <span v-if="selectedType === 'single_choice'">Visitor selects one answer.</span>
                                <span v-else-if="selectedType === 'multiple_choice'">Visitor can select more than one answer.</span>
                                <span v-else-if="selectedType === 'yes_no'">Visitor chooses Yes or No.</span>
                                <span v-else>No answer buttons needed for this type.</span>
                            </p>
                        </div>

                        <div v-if="usesOptions" class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700" @click="resetOptionsForType">Reset Slots</button>
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700" @click="addSpecialOption('All of the above')">Add All</button>
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700" @click="addSpecialOption('None of the above')">Add None</button>
                            <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="addOption">Add Slot</button>
                        </div>

                        <div v-if="usesYesNo" class="flex flex-wrap gap-2">
                            <button type="button" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-700" @click="resetOptionsForType">Reset Yes / No</button>
                        </div>
                    </div>

                    <div v-if="usesOptions || usesYesNo" class="space-y-3">
                        <div
                            v-for="option in selectedQuestion.options"
                            :key="option.id"
                            class="grid gap-3 rounded-lg border border-slate-200 bg-white p-4 md:grid-cols-[1fr_110px_110px_140px_auto]"
                        >
                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Label</span>
                                <input v-model="option.label" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            </label>

                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Score</span>
                                <input v-model="option.score_value" type="number" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            </label>

                            <label class="flex items-end gap-2 pb-2 text-sm text-slate-700">
                                <input v-model="option.is_correct" type="checkbox" class="rounded border-slate-300" />
                                Correct
                            </label>

                            <label class="space-y-1">
                                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Category</span>
                                <input v-model="option.category_key" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                            </label>

                            <div class="flex items-end gap-2">
                                <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="saveOption(option)">Save</button>
                                <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700" @click="deleteOption(option)">Delete</button>
                            </div>
                        </div>

                        <div v-if="!selectedQuestion.options || selectedQuestion.options.length === 0" class="rounded-lg border border-dashed border-slate-300 bg-white p-6 text-center text-sm text-slate-500">
                            No answer slots yet. Use Reset Slots or Add Slot.
                        </div>
                    </div>

                    <div v-if="usesTextInput" class="grid gap-3 md:grid-cols-3">
                        <input v-model="questionForm.settings_json.placeholder" placeholder="Placeholder" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-3" />
                        <input v-model="questionForm.settings_json.min_length" type="number" placeholder="Min length" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model="questionForm.settings_json.max_length" type="number" placeholder="Max length" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>

                    <div v-if="usesNumberInput" class="grid gap-3 md:grid-cols-4">
                        <input v-model="questionForm.settings_json.placeholder" placeholder="Placeholder" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-4" />
                        <input v-model="questionForm.settings_json.min" type="number" placeholder="Min" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model="questionForm.settings_json.max" type="number" placeholder="Max" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                        <input v-model="questionForm.settings_json.step" type="number" placeholder="Step" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    </div>
                </div>
            </section>
        </div>

        <section v-if="isEditing" class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div v-if="publishReadiness" class="mb-6 space-y-4">
                <div
                    v-if="publishReadiness.errors.length"
                    class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"
                >
                    <p class="font-semibold">Publish Errors</p>
                    <ul class="mt-2 list-disc pl-5">
                        <li v-for="error in publishReadiness.errors" :key="error">{{ error }}</li>
                    </ul>
                </div>

                <div
                    v-if="publishReadiness.warnings.length"
                    class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                >
                    <p class="font-semibold">Warnings</p>
                    <ul class="mt-2 list-disc pl-5">
                        <li v-for="warning in publishReadiness.warnings" :key="warning">{{ warning }}</li>
                    </ul>
                </div>
            </div>

            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Outcomes</h2>
                    <p class="text-sm text-slate-600">Define how answers resolve into final results.</p>
                </div>

                <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="addOutcome">
                    Add Outcome
                </button>
            </div>

            <div v-if="outcomeWarnings.length" class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                <p class="font-semibold">Outcome warnings</p>
                <ul class="mt-2 list-disc pl-5">
                    <li v-for="warning in outcomeWarnings" :key="warning">{{ warning }}</li>
                </ul>
            </div>

            <div v-if="outcomes.length" class="space-y-4">
                <div v-for="outcome in outcomes" :key="outcome.id" class="grid gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 md:grid-cols-3">
                    <input v-model="outcome.outcome_key" placeholder="Outcome key" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model="outcome.title" placeholder="Title" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model="outcome.category_key" placeholder="Category key" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model="outcome.min_score" type="number" placeholder="Min score" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model="outcome.max_score" type="number" placeholder="Max score" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <input v-model="outcome.cta_label" placeholder="CTA label" class="rounded-lg border border-slate-300 px-3 py-2 text-sm" />
                    <textarea v-model="outcome.summary" placeholder="Summary" rows="2" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-3" />
                    <input v-model="outcome.cta_url" placeholder="CTA URL" class="rounded-lg border border-slate-300 px-3 py-2 text-sm md:col-span-2" />

                    <div class="flex gap-2">
                        <button type="button" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white" @click="saveOutcome(outcome)">Save</button>
                        <button type="button" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-700" @click="deleteOutcome(outcome)">Delete</button>
                    </div>
                </div>
            </div>

            <div v-else class="rounded-lg border border-dashed border-slate-300 p-6 text-center text-sm text-slate-500">
                No outcomes yet. Add one to define result logic.
            </div>
        </section>
    </div>
</template>
