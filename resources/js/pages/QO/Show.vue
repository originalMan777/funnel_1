<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type QOOption = {
    id: number;
    label: string;
    score_value: number | null;
    is_correct: boolean;
    category_key: string | null;
    sort_order: number;
};

type QOQuestion = {
    id: number;
    type: 'single_choice' | 'multiple_choice' | 'yes_no' | 'short_text' | 'number';
    prompt: string | null;
    helper_text: string | null;
    explanation_text: string | null;
    sort_order: number;
    is_required: boolean;
    settings_json?: {
        placeholder?: string | null;
        min?: number | null;
        max?: number | null;
        step?: number | null;
    } | null;
    options?: QOOption[];
};

const props = defineProps<{
    item: {
        id: number;
        title: string;
        slug: string;
        type: 'quiz' | 'assessment';
        intro_title: string | null;
        intro_body: string | null;
        start_button_label: string | null;
        show_progress_bar: boolean;
        show_question_numbers: boolean;
        allow_back: boolean;
        show_correctness_feedback: boolean;
        allow_second_chance: boolean;
        reveal_correct_answer_after_fail: boolean;
        show_explanations: boolean;
        capture_mode: 'open' | 'optional_pre_start' | 'pre_start_gate' | 'optional_pre_result' | 'pre_result_gate' | 'post_result_only' | 'hybrid';
        cta_config?: Record<string, {
            enabled: boolean;
            intent: 'low' | 'medium' | 'high' | 'ultra';
            behavior: 'optional' | 'required' | 'hidden';
            headline: string;
            body: string;
            primary_label: string;
        }>;
        questions: QOQuestion[];
    };
    isPreview?: boolean;
}>();

const state = ref<'intro' | 'question' | 'pre_result' | 'outcome'>('intro');
const currentIndex = ref(0);
const submissionId = ref<number | null>(null);
const selectedOptionId = ref<number | null>(null);
const selectedOptionIds = ref<number[]>([]);
const textAnswer = ref('');
const numberAnswer = ref<number | null>(null);
const statusMessage = ref<string | null>(null);
const isProcessing = ref(false);
const finalOutcome = ref<any>(null);
const finalScore = ref<number | null>(null);
const lastFeedback = ref<any>(null);
const answerChecked = ref(false);
const captureCompleted = ref(false);
const savedQuestionIds = ref<number[]>([]);
const finalPercentage = computed(() => {
    if (finalScore.value === null || !totalQuestions.value) return null;
    return Math.round((finalScore.value / totalQuestions.value) * 100);
});

const questions = computed(() => props.item.questions ?? []);
const currentQuestion = computed(() => questions.value[currentIndex.value] ?? null);
const totalQuestions = computed(() => questions.value.length);
const currentQuestionNumber = computed(() => currentIndex.value + 1);
const isLastQuestion = computed(() => currentIndex.value >= totalQuestions.value - 1);
const canGoBack = computed(() => props.item.allow_back && currentIndex.value > 0);

const progressPercent = computed(() => {
    if (!totalQuestions.value) return 0;
    return Math.round((currentQuestionNumber.value / totalQuestions.value) * 100);
});

const showsPreStartCapture = computed(() => {
    return ['optional_pre_start', 'pre_start_gate', 'hybrid'].includes(props.item.capture_mode);
});

const showsPreResultCapture = computed(() => {
    return ['optional_pre_result', 'pre_result_gate'].includes(props.item.capture_mode)
        || (props.item.capture_mode === 'hybrid' && !captureCompleted.value);
});

const showsPostResultCapture = computed(() => {
    return ['post_result_only', 'open', 'optional_pre_start', 'optional_pre_result', 'hybrid'].includes(props.item.capture_mode);
});

const isPreStartGated = computed(() => props.item.capture_mode === 'pre_start_gate');
const isPreResultGated = computed(() => props.item.capture_mode === 'pre_result_gate' || (props.item.capture_mode === 'hybrid' && !captureCompleted.value));

function markCaptureCompleted() {
    captureCompleted.value = true;
    statusMessage.value = 'Information saved for this walkthrough.';
}

function ctaSlot(key: string) {
    return props.item.cta_config?.[key] ?? null;
}

function ctaEnabled(key: string): boolean {
    const slot = ctaSlot(key);

    return !!slot && slot.enabled && slot.behavior !== 'hidden';
}

function ctaLabel(key: string, fallback: string): string {
    return ctaSlot(key)?.primary_label || fallback;
}

function ctaHeadline(key: string, fallback: string): string {
    return ctaSlot(key)?.headline || fallback;
}

function ctaBody(key: string, fallback: string): string {
    return ctaSlot(key)?.body || fallback;
}

const shouldShowMidCta = computed(() => {
    if (!totalQuestions.value) return false;

    return currentQuestionNumber.value === Math.ceil(totalQuestions.value / 2);
});


function optionFeedbackClass(option: QOOption): string {
    if (!props.item.show_correctness_feedback || !answerChecked.value || !lastFeedback.value) {
        return '';
    }

    const isSelected = selectedOptionId.value === option.id || selectedOptionIds.value.includes(option.id);

    if (isSelected && lastFeedback.value.is_correct === true) {
        return 'border-emerald-600 bg-emerald-600 text-white';
    }

    if (isSelected && lastFeedback.value.is_correct === false) {
        return 'border-red-600 bg-red-600 text-white';
    }

    if (props.item.reveal_correct_answer_after_fail && option.is_correct) {
        return 'border-emerald-600 bg-emerald-50 text-emerald-800';
    }

    return '';
}

function optionBaseClass(option: QOOption): string {
    const feedback = optionFeedbackClass(option);

    if (feedback) return feedback;

    const isSelected = selectedOptionId.value === option.id || selectedOptionIds.value.includes(option.id);

    return isSelected
        ? 'border-slate-950 bg-slate-950 text-white'
        : 'border-slate-200 bg-white text-slate-800 hover:border-slate-400';
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

function resetAnswerState() {
    selectedOptionId.value = null;
    selectedOptionIds.value = [];
    textAnswer.value = '';
    numberAnswer.value = null;
    statusMessage.value = null;
    lastFeedback.value = null;
    answerChecked.value = false;
}

async function postJson(url: string, payload: Record<string, unknown>) {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(payload),
    });

    if (!response.ok) {
        const text = await response.text();
        throw new Error(text || 'Request failed.');
    }

    return response.json();
}

async function start() {
    if (isPreStartGated.value && !captureCompleted.value) {
        statusMessage.value = 'Complete the quick form below to start.';
        return;
    }

    isProcessing.value = true;
    statusMessage.value = 'Starting...';

    try {
        const result = await postJson(route('qo.runtime.start', props.item.slug), {
            is_preview: props.isPreview === true,
        });
        submissionId.value = result.submission.id;
        savedQuestionIds.value = [];
        currentIndex.value = 0;
        resetAnswerState();
        state.value = 'question';
    } catch (error) {
        statusMessage.value = 'Could not start this test.';
        console.error(error);
    } finally {
        isProcessing.value = false;
    }
}

function toggleMulti(optionId: number) {
    if (selectedOptionIds.value.includes(optionId)) {
        selectedOptionIds.value = selectedOptionIds.value.filter((id) => id !== optionId);
        return;
    }

    selectedOptionIds.value = [...selectedOptionIds.value, optionId];
}

function buildAnswerPayload() {
    const question = currentQuestion.value;

    if (!question || !submissionId.value) {
        return null;
    }

    const payload: Record<string, unknown> = {
        submission_id: submissionId.value,
        qo_question_id: question.id,
    };

    if (question.type === 'multiple_choice') {
        payload.qo_option_ids = selectedOptionIds.value;
    } else if (question.type === 'single_choice' || question.type === 'yes_no') {
        payload.qo_option_id = selectedOptionId.value;
    } else if (question.type === 'short_text') {
        payload.answer_text = textAnswer.value;
    } else if (question.type === 'number') {
        payload.answer_number = numberAnswer.value;
    }

    return payload;
}

function hasAnswer(): boolean {
    const question = currentQuestion.value;

    if (!question) return false;

    if (question.type === 'multiple_choice') return selectedOptionIds.value.length > 0;
    if (question.type === 'single_choice' || question.type === 'yes_no') return selectedOptionId.value !== null;
    if (question.type === 'short_text') return textAnswer.value.trim().length > 0;
    if (question.type === 'number') return numberAnswer.value !== null;

    return false;
}

async function submitCurrentAnswer(checkOnly = false) {
    if (!currentQuestion.value || !submissionId.value) return false;

    if (savedQuestionIds.value.includes(currentQuestion.value.id) && !checkOnly) {
        return true;
    }

    if (!hasAnswer()) {
        statusMessage.value = 'Choose or enter an answer before continuing.';
        return false;
    }

    isProcessing.value = true;
    statusMessage.value = checkOnly ? 'Checking answer...' : 'Saving answer...';

    try {
        const payload = buildAnswerPayload();

        if (!payload) return false;

        const result = await postJson(route('qo.runtime.answer', props.item.slug), payload);

        if (!savedQuestionIds.value.includes(currentQuestion.value.id)) {
            savedQuestionIds.value = [...savedQuestionIds.value, currentQuestion.value.id];
        }

        if (checkOnly && props.item.show_correctness_feedback) {
            lastFeedback.value = result.feedback;
            answerChecked.value = true;

            if (result.feedback?.is_correct === true) {
                statusMessage.value = 'Correct.';
            } else if (result.feedback?.is_correct === false) {
                statusMessage.value = props.item.allow_second_chance && result.feedback?.can_retry
                    ? 'Not quite. Try again.'
                    : 'Not quite.';
            } else {
                statusMessage.value = 'Answer saved.';
            }

            if (props.item.allow_second_chance && result.feedback?.can_retry) {
                answerChecked.value = false;
            }
        }

        return true;
    } catch (error) {
        statusMessage.value = checkOnly ? 'Could not check this answer.' : 'Could not save this answer.';
        console.error(error);
        return false;
    } finally {
        isProcessing.value = false;
    }
}

async function checkAnswer() {
    if (answerChecked.value) return;

    await submitCurrentAnswer(true);
}

async function next() {
    if (!currentQuestion.value || !submissionId.value) return;

    if (!answerChecked.value) {
        const saved = await submitCurrentAnswer(false);

        if (!saved) return;
    }

    if (isLastQuestion.value) {
        if (showsPreResultCapture.value) {
            state.value = 'pre_result';
            return;
        }

        await complete();
        return;
    }

    currentIndex.value += 1;
    resetAnswerState();
}

async function complete() {
    if (!submissionId.value) return;

    statusMessage.value = 'Calculating result...';

    try {
        const result = await postJson(route('qo.runtime.complete', props.item.slug), {
            submission_id: submissionId.value,
        });

        finalOutcome.value = result.outcome;
        finalScore.value = result.final_score;
        state.value = 'outcome';
    } catch (error) {
        statusMessage.value = 'Could not calculate the result. Check the console/logs.';
        console.error(error);
    }
}

function back() {
    if (!canGoBack.value) return;
    currentIndex.value -= 1;
    resetAnswerState();
}
</script>

<template>
    <Head :title="item.title" />

    <div class="min-h-screen bg-slate-950 text-white">
        <div
            v-if="isPreview"
            class="sticky top-0 z-50 bg-amber-400 px-6 py-2 text-center text-sm font-semibold text-slate-950"
        >
            Preview Mode — This assessment is not live.
        </div>
        <header class="border-b border-white/10 bg-slate-950/90">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">QO Engine</p>
                    <h1 class="text-lg font-semibold">{{ item.title }}</h1>
                </div>

                <Link href="/" class="text-sm text-slate-300 hover:text-white">Exit</Link>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-6 py-8">
            <section v-if="state === 'intro'" class="mx-auto max-w-3xl rounded-3xl border border-white/10 bg-white p-8 text-slate-950 shadow-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ item.type }}</p>
                <h2 class="mt-3 text-3xl font-bold">{{ item.intro_title || item.title }}</h2>
                <p class="mt-4 text-base leading-7 text-slate-600">{{ item.intro_body || 'Answer a few questions to get your outcome.' }}</p>

                <button
                    type="button"
                    class="mt-8 rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                    :disabled="isProcessing"
                    @click="start"
                >
                    {{ item.start_button_label || 'Start' }}
                </button>

                <p v-if="statusMessage" class="mt-4 text-sm text-slate-500">{{ statusMessage }}</p>
            </section>

            <section v-if="state === 'intro' && showsPreStartCapture && ctaEnabled('pre_start')" class="mx-auto mt-12 max-w-3xl rounded-2xl border border-white/10 bg-white/5 p-6 text-white">
                <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">
                    Optional
                </p>
                <h3 class="mt-2 text-xl font-bold">
                    {{ ctaHeadline('pre_start', 'Get the full breakdown after your assessment.') }}
                </h3>
                <p class="mt-2 text-sm leading-6 text-slate-300">
                    {{ ctaBody('pre_start', 'Your score is only the surface. This step reserves the expanded breakdown and next-step plan that will later connect to the lead system.') }}
                </p>

                <div class="mt-4 grid gap-3 sm:grid-cols-[1fr_1fr_auto]">
                    <input class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-400" placeholder="Name" />
                    <input class="rounded-xl border border-white/10 bg-white/10 px-4 py-3 text-sm text-white placeholder:text-slate-400" placeholder="Email" />
                    <button type="button" class="rounded-xl bg-white px-5 py-3 text-sm font-semibold text-slate-950" @click="markCaptureCompleted">
                        {{ ctaLabel('pre_start', 'Save My Breakdown') }}
                    </button>
                </div>

                <p class="mt-3 text-xs text-slate-400">
                    <span v-if="isPreStartGated">Required to start this assessment.</span>
                    <span v-else>Optional — you can start without filling this out.</span>
                </p>
            </section>

            <section v-if="state === 'question' && currentQuestion" class="space-y-5">
                <div class="rounded-3xl border border-white/10 bg-white p-6 text-slate-950 shadow-2xl">
                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 pb-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ item.type }}</p>
                            <h2 class="mt-1 text-2xl font-bold">{{ item.title }}</h2>
                        </div>

                        <div class="text-right text-sm text-slate-500">
                            Question {{ currentQuestionNumber }} of {{ totalQuestions }}
                        </div>
                    </div>

                    <div v-if="item.show_progress_bar" class="mt-5 h-2 overflow-hidden rounded-full bg-slate-100">
                        <div class="h-full rounded-full bg-slate-950 transition-all" :style="{ width: `${progressPercent}%` }" />
                    </div>

                    <div class="mt-8 grid gap-8 lg:grid-cols-[1fr_420px]">
                        <div>
                            <p v-if="item.show_question_numbers" class="text-sm font-semibold text-cyan-700">
                                Question {{ currentQuestionNumber }}
                            </p>

                            <h3 class="mt-2 text-3xl font-bold leading-tight text-slate-950">
                                {{ currentQuestion.prompt || 'Untitled question' }}
                            </h3>

                            <p v-if="currentQuestion.helper_text" class="mt-4 text-base leading-7 text-slate-600">
                                {{ currentQuestion.helper_text }}
                            </p>

                        </div>

                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5">
                            <p class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Answer</p>

                            <div v-if="currentQuestion.type === 'single_choice' || currentQuestion.type === 'yes_no'" class="space-y-3">
                                <button
                                    v-for="option in currentQuestion.options"
                                    :key="option.id"
                                    type="button"
                                    class="w-full rounded-xl border px-4 py-3 text-left text-sm font-semibold transition"
                                    :class="optionBaseClass(option)"
                                    :disabled="answerChecked && !item.allow_second_chance"
                                    @click="selectedOptionId = option.id"
                                >
                                    {{ option.label }}
                                </button>
                            </div>

                            <div v-else-if="currentQuestion.type === 'multiple_choice'" class="space-y-3">
                                <button
                                    v-for="option in currentQuestion.options"
                                    :key="option.id"
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl border px-4 py-3 text-left text-sm font-semibold transition"
                                    :class="optionBaseClass(option)"
                                    :disabled="answerChecked && !item.allow_second_chance"
                                    @click="toggleMulti(option.id)"
                                >
                                    <span class="flex h-5 w-5 items-center justify-center rounded border" :class="selectedOptionIds.includes(option.id) ? 'border-white' : 'border-slate-300'">
                                        <span v-if="selectedOptionIds.includes(option.id)" class="h-2 w-2 rounded-sm bg-white" />
                                    </span>
                                    {{ option.label }}
                                </button>
                            </div>

                            <textarea
                                v-else-if="currentQuestion.type === 'short_text'"
                                v-model="textAnswer"
                                rows="5"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                                :placeholder="currentQuestion.settings_json?.placeholder || 'Type your answer here...'"
                            />

                            <input
                                v-else-if="currentQuestion.type === 'number'"
                                v-model="numberAnswer"
                                type="number"
                                class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm"
                                :placeholder="currentQuestion.settings_json?.placeholder || 'Enter a number...'"
                                :min="currentQuestion.settings_json?.min ?? undefined"
                                :max="currentQuestion.settings_json?.max ?? undefined"
                                :step="currentQuestion.settings_json?.step ?? 1"
                            />
                        </div>
                    </div>

                    <div class="mt-8 flex items-center justify-between border-t border-slate-200 pt-5">
                        <div class="text-sm text-slate-500">
                            {{ statusMessage || 'Choose an answer, then continue.' }}
                        </div>

                        <div class="flex gap-3">
                            <button
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                                :disabled="!canGoBack || isProcessing"
                                @click="back"
                            >
                                Back
                            </button>

                            <button
                                v-if="item.show_correctness_feedback"
                                type="button"
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 disabled:opacity-40"
                                :disabled="isProcessing || answerChecked"
                                @click="checkAnswer"
                            >
                                Check Answer
                            </button>

                            <button
                                type="button"
                                class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800 disabled:opacity-50"
                                :disabled="isProcessing"
                                @click="next"
                            >
                                {{ isLastQuestion ? 'See Outcome' : 'Next' }}
                            </button>
                        </div>
                    </div>
                </div>

                <section v-if="shouldShowMidCta && ctaEnabled('mid_assessment')" class="mx-auto mt-10 max-w-5xl rounded-2xl border border-white/10 bg-white/[0.04] px-6 py-5 text-slate-200">
                    <p class="text-xs font-semibold uppercase tracking-[0.25em] text-cyan-300">
                        Mid Assessment
                    </p>
                    <div class="mt-2 flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-lg font-bold text-white">{{ ctaHeadline('mid_assessment', 'You’re halfway through.') }}</h3>
                            <p class="mt-1 max-w-2xl text-sm text-slate-400">
                                {{ ctaBody('mid_assessment', 'This slot can later show a soft lead magnet or progress-based offer without interrupting the test.') }}
                            </p>
                        </div>
                        <button type="button" class="w-fit rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10">
                            {{ ctaLabel('mid_assessment', 'Save My Progress') }}
                        </button>
                    </div>
                </section>

                <footer class="mt-24 border-t border-white/10 px-2 pt-8 text-slate-300">
                    <div class="mx-auto flex max-w-5xl flex-col gap-4 rounded-2xl bg-white/[0.03] px-6 py-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-white">Footer Lead Box / Promo Zone</p>
                            <p class="mt-1 max-w-2xl text-sm text-slate-400">
                                This area can hold a lead magnet, offer, consultation CTA, or rotating promo without competing with the quiz.
                            </p>
                        </div>

                        <button
                            type="button"
                            class="w-fit rounded-xl border border-white/15 px-4 py-2 text-sm font-semibold text-white hover:bg-white/10"
                        >
                            Learn More
                        </button>
                    </div>
                </footer>
            </section>

            <section v-if="state === 'pre_result' && showsPreResultCapture && ctaEnabled('pre_result')" class="mx-auto max-w-3xl rounded-3xl border border-white/10 bg-white p-8 text-slate-950 shadow-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                    Before your result
                </p>

                <h2 class="mt-3 text-3xl font-bold">
                    {{ ctaHeadline('pre_result', 'Unlock the meaning behind your result.') }}
                </h2>

                <p class="mt-4 text-base leading-7 text-slate-600">
                    {{ ctaBody('pre_result', 'Your result is ready. Turn your answers into a useful breakdown with next-step direction.') }}
                </p>

                <div class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Name" />
                        <input class="rounded-xl border border-slate-300 px-4 py-3 text-sm" placeholder="Email" />
                    </div>
                    <p class="mt-3 text-xs text-slate-500">
                        <span v-if="isPreResultGated">Required to unlock the result.</span>
                        <span v-else>Optional — you can skip this and view your result now.</span>
                    </p>
                </div>

                <div class="mt-6 flex flex-col gap-3 sm:flex-row">
                    <button
                        type="button"
                        class="rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                        @click="markCaptureCompleted(); complete()"
                    >
                        {{ ctaLabel('pre_result', 'Unlock My Result') }}
                    </button>

                    <button
                        v-if="!isPreResultGated"
                        type="button"
                        class="rounded-xl border border-slate-300 px-6 py-3 text-sm font-semibold text-slate-800 hover:bg-slate-50"
                        @click="complete"
                    >
                        Skip and Show Result
                    </button>
                </div>
            </section>

            <section v-if="state === 'outcome'" class="mx-auto max-w-3xl rounded-3xl border border-white/10 bg-white p-8 text-slate-950 shadow-2xl">
                <p class="text-sm font-semibold uppercase tracking-wide text-emerald-700">Complete</p>

                <h2 class="mt-3 text-3xl font-bold">{{ finalOutcome?.title || 'Your outcome is ready' }}</h2>

                <p class="mt-4 text-base leading-7 text-slate-600">
                    {{ finalOutcome?.summary || 'Your results have been calculated.' }}
                </p>

                <div v-if="finalScore !== null" class="mt-4 space-y-2 rounded-xl bg-slate-100 px-4 py-3 text-sm font-semibold text-slate-700">
                    <p>Final Score: {{ finalScore }} / {{ totalQuestions }}</p>
                    <p v-if="finalPercentage !== null">Percentage: {{ finalPercentage }}%</p>
                </div>

                <div v-if="showsPostResultCapture && ctaEnabled('post_result')" class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-5">
                    <p class="text-sm font-semibold uppercase tracking-wide text-slate-500">
                        Post Result
                    </p>

                    <h3 class="mt-2 text-xl font-bold text-slate-950">
                        {{ ctaHeadline('post_result', 'Get your next-step action plan.') }}
                    </h3>

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ ctaBody('post_result', 'This is the strongest CTA slot. Later, it will connect to your lead box / booking flow based on this result.') }}
                    </p>

                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <button type="button" class="rounded-xl bg-slate-950 px-5 py-3 text-sm font-semibold text-white hover:bg-slate-800">
                            {{ ctaLabel('post_result', 'Get My Action Plan') }}
                        </button>

                        <button type="button" class="rounded-xl border border-slate-300 px-5 py-3 text-sm font-semibold text-slate-800 hover:bg-white">
                            Book the Next Step
                        </button>
                    </div>
                </div>

                <Link
                    :href="route('qo.show', item.slug)"
                    class="mt-8 inline-block rounded-xl bg-slate-950 px-6 py-3 text-sm font-semibold text-white hover:bg-slate-800"
                >
                    Restart
                </Link>
            </section>
        </main>
    </div>
</template>
