<script setup lang="ts">
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import TopNavA from './TopNavA.vue';
import TopNavB from './TopNavB.vue';

type ItemOption = string;

type CategoryConfig = {
    key: string;
    label: string;
    description: string;
    required: boolean;
    controlled: boolean;
    editable: boolean;
    tier: string;
    searchable: boolean;
    input_type: string;
    items: ItemOption[];
};

type ToolConfig = {
    generator: {
        default_result_count: number;
        max_result_count: number;
        required_groups: string[];
        tier_1_groups: string[];
        tier_2_groups: string[];
        word_range: {
            min: number;
            max: number;
            default_min: number;
            default_max: number;
        };
        prompt_families: {
            standard: { label: string; count: number };
            optimized: { label: string; count: number };
        };
        tier: {
            name: string;
            batch_size: number;
            reset_limit: number | null;
            continue_limit: number | null;
        };
    };
    ui: {
        core_groups_open_by_default: string[];
        optional_groups_open_by_default: string[];
        show_search_for_groups: string[];
        show_select_all_for_groups: string[];
        sticky_control_center: boolean;
        left_panel_scrollable: boolean;
    };
    categories: CategoryConfig[];
};

type SelectedItem = {
    label: string;
    stars: 1 | 2 | 3;
};

type GeneratedRow = {
    id: string;
    summary: string;
    profile: {
        topic: string;
        article_type: string;
        article_format: string;
        vibe: string;
        reader_impact: string | null;
        audience: string | null;
        context: string | null;
        perspective: string | null;
        word_range: {
            min: number;
            max: number;
        };
    };
    badges: string[];
    title_options: string[];
    standard_prompts: string[];
    optimized_prompts: string[];
};

type GenerateResponse = {
    success: boolean;
    message: string;
    data: {
        session: {
            id: string;
        };
        meta: {
            tier: string;
            batch_size: number;
            can_continue: boolean;
            can_reset: boolean;
            remaining_continue_count: number | null;
            remaining_reset_count: number | null;
            requested_count: number;
            generated_count: number;
            estimated_core_combinations: number;
            session_generated_count: number;
            exhausted: boolean;
            word_range: {
                min: number;
                max: number;
            };
        };
        rows: GeneratedRow[];
    };
};

const props = defineProps<{
    config: ToolConfig;
}>();

const categoryMap = computed<Record<string, CategoryConfig>>(() => {
    return props.config.categories.reduce((carry, category) => {
        carry[category.key] = category;
        return carry;
    }, {} as Record<string, CategoryConfig>);
});

const openGroups = reactive<Record<string, boolean>>({});
const searchTerms = reactive<Record<string, string>>({});
const selected = reactive<Record<string, Record<string, SelectedItem>>>({});
const loadingAction = ref<'generate' | 'continue' | 'reset' | null>(null);
const errorMessage = ref('');
const successMessage = ref('');
const minWords = ref<number>(props.config.generator.word_range.default_min);
const maxWords = ref<number>(props.config.generator.word_range.default_max);
const batchSize = ref<number>(props.config.generator.default_result_count || props.config.generator.tier.batch_size);
const extraDirection = ref('');
const generatedRows = ref<GeneratedRow[]>([]);
const meta = ref<GenerateResponse['data']['meta'] | null>(null);
const activeRowKey = ref<string | null>(null);
const sessionId = ref<string | null>(null);
const expandedPrompts = reactive<Record<string, boolean>>({});
const copiedKey = ref<string | null>(null);
let copyFeedbackTimeout: ReturnType<typeof setTimeout> | null = null;
const currentYear = new Date().getFullYear();
const nojoFooterLogoSrc = '/images/logos/nojo-consult-logo1.svg';
const awestrukFooterLogoSrc = '/images/logos/awestruk-square-logo1.svg';
const footerLogoVisible = reactive({
    nojo: true,
    awestruk: true,
});

function initializeState() {
    for (const category of props.config.categories) {
        selected[category.key] = {};
        searchTerms[category.key] = '';

        const isCoreOpen = props.config.ui.core_groups_open_by_default.includes(category.key);
        const isOptionalOpen = props.config.ui.optional_groups_open_by_default.includes(category.key);

        openGroups[category.key] = isCoreOpen || isOptionalOpen;
    }
}

initializeState();
clampBatchSize();

const requiredCategories = computed(() => {
    return props.config.categories.filter((category) => category.required);
});

const optionalCategories = computed(() => {
    return props.config.categories.filter(
        (category) => !category.required && category.key !== 'extra_direction'
    );
});

const totalSelectedCount = computed(() => {
    return Object.values(selected).reduce((sum, group) => sum + Object.keys(group || {}).length, 0);
});

const estimatedCoreCombinations = computed(() => {
    const topics = Math.max(selectedCount('topics'), 1);
    const articleTypes = Math.max(selectedCount('article_types'), 1);
    const articleFormats = Math.max(selectedCount('article_formats'), 1);
    const vibes = Math.max(selectedCount('vibes'), 1);

    return topics * articleTypes * articleFormats * vibes;
});

const canContinue = computed(() => {
    return Boolean(sessionId.value && meta.value?.can_continue && !isLoading.value);
});

const canReset = computed(() => {
    return Boolean(sessionId.value && meta.value?.can_reset && !isLoading.value);
});

const isLoading = computed(() => loadingAction.value !== null);

const tierName = computed(() => {
    return props.config.generator.tier.name.replace('_', ' ');
});

const wordSliderStyle = computed(() => {
    const min = props.config.generator.word_range.min;
    const max = props.config.generator.word_range.max;
    const low = ((minWords.value - min) / (max - min)) * 100;
    const high = ((maxWords.value - min) / (max - min)) * 100;

    return {
        background: `linear-gradient(to right, #d4d4d8 0%, #d4d4d8 ${low}%, #0f766e ${low}%, #0f766e ${high}%, #d4d4d8 ${high}%, #d4d4d8 100%)`,
    };
});

function isChecked(groupKey: string, label: string): boolean {
    return Boolean(selected[groupKey]?.[label]);
}

function getStars(groupKey: string, label: string): 1 | 2 | 3 | null {
    return selected[groupKey]?.[label]?.stars ?? null;
}

function toggleItem(groupKey: string, label: string) {
    if (!selected[groupKey]) {
        selected[groupKey] = {};
    }

    if (selected[groupKey][label]) {
        delete selected[groupKey][label];
        return;
    }

    selected[groupKey][label] = { label, stars: 1 };
}

function setStars(groupKey: string, label: string, stars: 1 | 2 | 3) {
    if (!selected[groupKey]) {
        selected[groupKey] = {};
    }

    if (!selected[groupKey][label]) {
        selected[groupKey][label] = { label, stars };
        return;
    }

    selected[groupKey][label].stars = stars;
}

function toggleGroup(groupKey: string) {
    openGroups[groupKey] = !openGroups[groupKey];
}

function filteredItems(category: CategoryConfig): string[] {
    const items = category.items ?? [];
    const term = (searchTerms[category.key] || '').trim().toLowerCase();

    if (!term) {
        return items;
    }

    return items.filter((item) => item.toLowerCase().includes(term));
}

function selectAll(category: CategoryConfig) {
    if (!selected[category.key]) {
        selected[category.key] = {};
    }

    for (const item of category.items) {
        if (!selected[category.key][item]) {
            selected[category.key][item] = { label: item, stars: 1 };
        }
    }
}

function clearAll(category: CategoryConfig) {
    selected[category.key] = {};
}

function selectedCount(groupKey: string): number {
    return Object.keys(selected[groupKey] || {}).length;
}

function rowAccordionKey(row: GeneratedRow, index: number) {
    return `${row.id}-${index}`;
}

function isRowExpanded(row: GeneratedRow, index: number) {
    return activeRowKey.value === rowAccordionKey(row, index);
}

function toggleRow(row: GeneratedRow, index: number) {
    const rowKey = rowAccordionKey(row, index);
    activeRowKey.value = activeRowKey.value === rowKey ? null : rowKey;
}

function clampBatchSize() {
    const fallback = props.config.generator.default_result_count || props.config.generator.tier.batch_size;
    const min = 1;
    const max = props.config.generator.max_result_count;
    const raw = Number(batchSize.value);

    batchSize.value = Number.isFinite(raw) ? raw : fallback;
    batchSize.value = Math.max(min, Math.min(max, Math.round(batchSize.value)));
}

function clampWords() {
    const range = props.config.generator.word_range;
    minWords.value = Math.max(range.min, Math.min(range.max, Number(minWords.value) || range.min));
    maxWords.value = Math.max(range.min, Math.min(range.max, Number(maxWords.value) || range.max));

    if (minWords.value > maxWords.value) {
        [minWords.value, maxWords.value] = [maxWords.value, minWords.value];
    }
}

function handleMinSlider(value: number) {
    minWords.value = value;

    if (minWords.value > maxWords.value) {
        maxWords.value = minWords.value;
    }

    clampWords();
}

function handleMaxSlider(value: number) {
    maxWords.value = value;

    if (maxWords.value < minWords.value) {
        minWords.value = maxWords.value;
    }

    clampWords();
}

function buildPayload(action: 'generate' | 'continue' | 'reset') {
    clampWords();
    clampBatchSize();

    const groups: Record<string, SelectedItem[]> = {};

    for (const [groupKey, items] of Object.entries(selected)) {
        groups[groupKey] = Object.values(items);
    }

    return {
        action,
        session_id: action === 'continue' || action === 'reset' ? sessionId.value : null,
        result_count: batchSize.value,
        min_words: minWords.value,
        max_words: maxWords.value,
        groups,
        extra_direction: extraDirection.value.trim(),
    };
}

function validateRequiredSelections(): boolean {
    for (const category of requiredCategories.value) {
        if (selectedCount(category.key) < 1) {
            errorMessage.value = `Please select at least one option for ${category.label}.`;
            return false;
        }
    }

    errorMessage.value = '';
    return true;
}

async function submitAction(action: 'generate' | 'continue' | 'reset') {
    successMessage.value = '';
    errorMessage.value = '';

    if (!validateRequiredSelections()) {
        return;
    }

    loadingAction.value = action;

    try {
        const response = await axios.post<GenerateResponse>(
            '/admin/content-formula/generate',
            buildPayload(action)
        );

        if (!response.data?.success) {
            errorMessage.value = 'Unable to generate content ideas.';
            return;
        }

        const rows = response.data.data.rows || [];
        generatedRows.value = action === 'continue'
            ? [...generatedRows.value, ...rows]
            : rows;
        sessionId.value = response.data.data.session?.id || null;
        meta.value = response.data.data.meta || null;
        successMessage.value = response.data.message || 'Content ideas generated successfully.';

        if (action !== 'continue') {
            activeRowKey.value = null;

            Object.keys(expandedPrompts).forEach((key) => {
                delete expandedPrompts[key];
            });
        }
    } catch (error: any) {
        if (error?.response?.data?.message) {
            errorMessage.value = error.response.data.message;
        } else if (error?.response?.data?.errors) {
            const firstError = Object.values(error.response.data.errors).flat()[0];
            errorMessage.value = String(firstError);
        } else {
            errorMessage.value = 'Something went wrong while generating content ideas.';
        }
    } finally {
        loadingAction.value = null;
    }
}

function isCopied(feedbackKey: string) {
    return copiedKey.value === feedbackKey;
}

async function copyText(text: string, feedbackKey?: string) {
    try {
        await navigator.clipboard.writeText(text);
        successMessage.value = 'Copied successfully.';

        if (feedbackKey) {
            copiedKey.value = feedbackKey;

            if (copyFeedbackTimeout) {
                clearTimeout(copyFeedbackTimeout);
            }

            copyFeedbackTimeout = setTimeout(() => {
                if (copiedKey.value === feedbackKey) {
                    copiedKey.value = null;
                }
            }, 1400);
        }
    } catch {
        errorMessage.value = 'Unable to copy to clipboard.';
    }
}

function promptItems(row: GeneratedRow) {
    return [
        ...row.standard_prompts.map((prompt, index) => ({
            id: `${row.id}-standard-${index}`,
            label: 'Standard',
            title: `Prompt ${index + 1}`,
            prompt,
        })),
        ...row.optimized_prompts.map((prompt, index) => ({
            id: `${row.id}-optimized-${index}`,
            label: 'Optimized',
            title: `Prompt ${row.standard_prompts.length + index + 1}`,
            prompt,
        })),
    ];
}

function isPromptExpanded(promptId: string) {
    return Boolean(expandedPrompts[promptId]);
}

function togglePrompt(promptId: string) {
    expandedPrompts[promptId] = !expandedPrompts[promptId];
}

function handleFooterLogoError(key: 'nojo' | 'awestruk') {
    footerLogoVisible[key] = false;
}

onBeforeUnmount(() => {
    if (copyFeedbackTimeout) {
        clearTimeout(copyFeedbackTimeout);
    }
});
</script>

<template>
    <Head title="Content Formula Tool" />

    <div class="min-h-screen bg-stone-100">
        <TopNavA />
        <TopNavB />

        <div class="bg-[radial-gradient(circle_at_top,_rgba(15,118,110,0.16),_transparent_40%),linear-gradient(180deg,_#f7f4ee_0%,_#f5f5f4_45%,_#fafaf9_100%)]">
            <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                <div class="mb-6 overflow-hidden rounded-[28px] border border-stone-200 bg-white/90 shadow-[0_20px_60px_rgba(28,25,23,0.08)] backdrop-blur">
                    <div class="grid gap-6 px-6 py-6 lg:grid-cols-[1.5fr_0.9fr] lg:px-8">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-teal-700">
                                Admin Content Formula Generator
                            </p>
                            <h1 class="mt-3 text-3xl font-semibold tracking-tight text-stone-900">
                                Build premium content batches from your formula pools.
                            </h1>
                            <p class="mt-3 max-w-3xl text-sm leading-6 text-stone-600">
                                This admin-only tool keeps the mother-site formula model intact while producing structured title sets and prompt families that are ready for downstream writing tools.
                            </p>
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                <div class="text-xs font-medium uppercase tracking-wide text-stone-500">Tier</div>
                                <div class="mt-2 text-2xl font-semibold capitalize text-stone-900">{{ tierName }}</div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                <div class="text-xs font-medium uppercase tracking-wide text-stone-500">Current Batch</div>
                                <div class="mt-2 text-2xl font-semibold text-stone-900">
                                    {{ batchSize }}
                                </div>
                            </div>
                            <div class="rounded-2xl border border-stone-200 bg-stone-50 p-4">
                                <div class="text-xs font-medium uppercase tracking-wide text-stone-500">Core Combos</div>
                                <div class="mt-2 text-2xl font-semibold text-stone-900">{{ estimatedCoreCombinations }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 items-stretch gap-6 lg:grid-cols-12">
                    <div class="flex lg:col-span-8">
                        <div class="overflow-hidden rounded-[28px] border border-stone-200 bg-white shadow-[0_18px_50px_rgba(28,25,23,0.07)]">
                            <div class="border-b border-stone-200 px-6 py-5">
                                <h2 class="text-lg font-semibold text-stone-900">Build Your Content Pool</h2>
                                <p class="mt-1 text-sm text-stone-600">
                                    Choose the formula ingredients you want in rotation. Star values still control emphasis across the session.
                                </p>
                            </div>

                            <div class="max-h-[78vh] overflow-y-auto px-6 py-6">
                                <div class="space-y-6">
                                    <div
                                        v-for="category in requiredCategories"
                                        :key="category.key"
                                        class="rounded-3xl border border-stone-200 bg-stone-50"
                                    >
                                        <div
                                            class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                            @click="toggleGroup(category.key)"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-base font-semibold text-stone-900">{{ category.label }}</h3>
                                                    <span class="inline-flex rounded-full bg-teal-100 px-2.5 py-0.5 text-xs font-medium text-teal-800">
                                                        Required
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-stone-600">{{ category.description }}</p>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-700 shadow-sm">
                                                    {{ selectedCount(category.key) }} selected
                                                </span>
                                                <span class="text-sm text-stone-500">{{ openGroups[category.key] ? '−' : '+' }}</span>
                                            </div>
                                        </div>

                                        <div v-if="openGroups[category.key]" class="border-t border-stone-200 bg-white px-5 py-5">
                                            <div
                                                v-if="props.config.ui.show_search_for_groups.includes(category.key)"
                                                class="mb-4"
                                            >
                                                <input
                                                    v-model="searchTerms[category.key]"
                                                    type="text"
                                                    :placeholder="`Search ${category.label.toLowerCase()}...`"
                                                    class="w-full rounded-2xl border border-stone-300 px-4 py-2.5 text-sm text-stone-800 outline-none placeholder:text-stone-400 focus:border-teal-600"
                                                />
                                            </div>

                                            <div
                                                v-if="props.config.ui.show_select_all_for_groups.includes(category.key)"
                                                class="mb-4 flex flex-wrap items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-stone-300 bg-white px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                                    @click.stop="selectAll(category)"
                                                >
                                                    Select all
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-2xl border border-stone-300 bg-white px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                                    @click.stop="clearAll(category)"
                                                >
                                                    Clear all
                                                </button>
                                            </div>

                                            <div class="space-y-2">
                                                <div
                                                    v-for="item in filteredItems(category)"
                                                    :key="`${category.key}-${item}`"
                                                    class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                                >
                                                    <label class="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            class="h-4 w-4 rounded border-stone-300 text-teal-700 focus:ring-0"
                                                            :checked="isChecked(category.key, item)"
                                                            @change="toggleItem(category.key, item)"
                                                        />
                                                        <span class="text-sm font-medium text-stone-800">{{ item }}</span>
                                                    </label>

                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            v-for="star in [1, 2, 3]"
                                                            :key="star"
                                                            type="button"
                                                            class="rounded-xl border px-2.5 py-1 text-xs font-medium"
                                                            :class="getStars(category.key, item) === star
                                                                ? 'border-teal-700 bg-teal-700 text-white'
                                                                : 'border-stone-300 bg-white text-stone-700 hover:bg-stone-50'"
                                                            @click.stop="setStars(category.key, item, star as 1 | 2 | 3)"
                                                        >
                                                            {{ star }}★
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-8">
                                    <div class="mb-4">
                                        <h2 class="text-lg font-semibold text-stone-900">Optional Refinements</h2>
                                        <p class="mt-1 text-sm text-stone-600">
                                            Audience, context, and reader impact stay active whenever selected. Perspective is introduced more selectively to keep variety high.
                                        </p>
                                    </div>

                                    <div class="space-y-6">
                                        <div
                                            v-for="category in optionalCategories"
                                            :key="category.key"
                                            class="rounded-3xl border border-stone-200 bg-stone-50"
                                        >
                                            <div
                                                class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                                @click="toggleGroup(category.key)"
                                            >
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="text-base font-semibold text-stone-900">{{ category.label }}</h3>
                                                        <span class="inline-flex rounded-full bg-stone-200 px-2.5 py-0.5 text-xs font-medium text-stone-700">
                                                            Optional
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-sm text-stone-600">{{ category.description }}</p>
                                                </div>

                                                <div class="flex items-center gap-3">
                                                    <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-stone-700 shadow-sm">
                                                        {{ selectedCount(category.key) }} selected
                                                    </span>
                                                    <span class="text-sm text-stone-500">{{ openGroups[category.key] ? '−' : '+' }}</span>
                                                </div>
                                            </div>

                                            <div v-if="openGroups[category.key]" class="border-t border-stone-200 bg-white px-5 py-5">
                                                <div
                                                    v-if="props.config.ui.show_search_for_groups.includes(category.key)"
                                                    class="mb-4"
                                                >
                                                    <input
                                                        v-model="searchTerms[category.key]"
                                                        type="text"
                                                        :placeholder="`Search ${category.label.toLowerCase()}...`"
                                                        class="w-full rounded-2xl border border-stone-300 px-4 py-2.5 text-sm text-stone-800 outline-none placeholder:text-stone-400 focus:border-teal-600"
                                                    />
                                                </div>

                                                <div
                                                    v-if="props.config.ui.show_select_all_for_groups.includes(category.key)"
                                                    class="mb-4 flex flex-wrap items-center gap-2"
                                                >
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border border-stone-300 bg-white px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                                        @click.stop="selectAll(category)"
                                                    >
                                                        Select all
                                                    </button>
                                                    <button
                                                        type="button"
                                                        class="rounded-2xl border border-stone-300 bg-white px-3 py-2 text-xs font-medium text-stone-700 hover:bg-stone-50"
                                                        @click.stop="clearAll(category)"
                                                    >
                                                        Clear all
                                                    </button>
                                                </div>

                                                <div class="space-y-2">
                                                    <div
                                                        v-for="item in filteredItems(category)"
                                                        :key="`${category.key}-${item}`"
                                                        class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                                    >
                                                        <label class="flex items-center gap-3">
                                                            <input
                                                                type="checkbox"
                                                                class="h-4 w-4 rounded border-stone-300 text-teal-700 focus:ring-0"
                                                                :checked="isChecked(category.key, item)"
                                                                @change="toggleItem(category.key, item)"
                                                            />
                                                            <span class="text-sm font-medium text-stone-800">{{ item }}</span>
                                                        </label>

                                                        <div class="flex items-center gap-2">
                                                            <button
                                                                v-for="star in [1, 2, 3]"
                                                                :key="star"
                                                                type="button"
                                                                class="rounded-xl border px-2.5 py-1 text-xs font-medium"
                                                                :class="getStars(category.key, item) === star
                                                                    ? 'border-teal-700 bg-teal-700 text-white'
                                                                    : 'border-stone-300 bg-white text-stone-700 hover:bg-stone-50'"
                                                                @click.stop="setStars(category.key, item, star as 1 | 2 | 3)"
                                                            >
                                                                {{ star }}★
                                                            </button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div v-if="categoryMap.extra_direction" class="rounded-3xl border border-stone-200 bg-stone-50">
                                            <div
                                                class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                                @click="toggleGroup('extra_direction')"
                                            >
                                                <div>
                                                    <div class="flex items-center gap-2">
                                                        <h3 class="text-base font-semibold text-stone-900">
                                                            {{ categoryMap.extra_direction.label }}
                                                        </h3>
                                                        <span class="inline-flex rounded-full bg-stone-200 px-2.5 py-0.5 text-xs font-medium text-stone-700">
                                                            Optional
                                                        </span>
                                                    </div>
                                                    <p class="mt-1 text-sm text-stone-600">
                                                        {{ categoryMap.extra_direction.description }}
                                                    </p>
                                                </div>

                                                <span class="text-sm text-stone-500">{{ openGroups.extra_direction ? '−' : '+' }}</span>
                                            </div>

                                            <div v-if="openGroups.extra_direction" class="border-t border-stone-200 bg-white px-5 py-5">
                                                <textarea
                                                    v-model="extraDirection"
                                                    rows="4"
                                                    placeholder="Example: focus on Trinidad buyers, lean strategic, weave in faith-grounded clarity..."
                                                    class="w-full rounded-2xl border border-stone-300 px-4 py-3 text-sm text-stone-800 outline-none placeholder:text-stone-400 focus:border-teal-600"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex lg:col-span-4">
                        <div class="space-y-6" :class="props.config.ui.sticky_control_center ? 'lg:sticky lg:top-6' : ''">
                            <div class="overflow-hidden rounded-[28px] border border-stone-200 bg-white shadow-[0_18px_50px_rgba(28,25,23,0.07)]">
                                <div class="border-b border-stone-200 px-6 py-5">
                                    <h2 class="text-lg font-semibold text-stone-900">Control Center</h2>
                                    <p class="mt-1 text-sm text-stone-600">
                                        Generate paid-tier batches, continue the same session without structural repeats, or reset into a fresh shuffle with the same selections.
                                    </p>
                                </div>

                                <div class="space-y-5 px-6 py-6">
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="rounded-2xl bg-stone-50 p-4">
                                            <div class="text-xs font-medium uppercase tracking-wide text-stone-500">Selected</div>
                                            <div class="mt-2 text-2xl font-semibold text-stone-900">{{ totalSelectedCount }}</div>
                                        </div>
                                        <div class="rounded-2xl bg-stone-50 p-4">
                                            <div class="text-xs font-medium uppercase tracking-wide text-stone-500">Session Rows</div>
                                            <div class="mt-2 text-2xl font-semibold text-stone-900">{{ meta?.session_generated_count ?? 0 }}</div>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-stone-200 bg-stone-50 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                                                    Batch Size
                                                </label>
                                                <p class="mt-1 text-sm text-stone-600">
                                                    Choose how many ideas to generate in the next batch.
                                                </p>
                                            </div>
                                            <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-stone-900 shadow-sm">
                                                {{ batchSize }}
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            <input
                                                v-model.number="batchSize"
                                                type="number"
                                                min="1"
                                                :max="props.config.generator.max_result_count"
                                                class="w-full rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm text-stone-800 outline-none placeholder:text-stone-400 focus:border-teal-600"
                                                @change="clampBatchSize"
                                            />
                                            <p class="mt-2 text-xs text-stone-500">
                                                Max {{ props.config.generator.max_result_count }} ideas per batch.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="rounded-3xl border border-stone-200 bg-stone-50 p-4">
                                        <div class="flex items-center justify-between gap-3">
                                            <div>
                                                <label class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                                                    Article Length Range
                                                </label>
                                                <p class="mt-1 text-sm text-stone-600">
                                                    Word range only affects prompt instructions.
                                                </p>
                                            </div>
                                            <div class="rounded-full bg-white px-3 py-1 text-sm font-semibold text-stone-900 shadow-sm">
                                                {{ minWords }} - {{ maxWords }}
                                            </div>
                                        </div>

                                        <div class="mt-5">
                                            <div class="relative h-10">
                                                <div class="absolute inset-x-0 top-1/2 h-2 -translate-y-1/2 rounded-full" :style="wordSliderStyle"></div>
                                                <input
                                                    :value="minWords"
                                                    type="range"
                                                    :min="props.config.generator.word_range.min"
                                                    :max="props.config.generator.word_range.max"
                                                    class="pointer-events-none absolute inset-0 h-10 w-full appearance-none bg-transparent [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:bg-teal-700 [&::-webkit-slider-thumb]:shadow-md [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:bg-teal-700"
                                                    @input="handleMinSlider(Number(($event.target as HTMLInputElement).value))"
                                                />
                                                <input
                                                    :value="maxWords"
                                                    type="range"
                                                    :min="props.config.generator.word_range.min"
                                                    :max="props.config.generator.word_range.max"
                                                    class="pointer-events-none absolute inset-0 h-10 w-full appearance-none bg-transparent [&::-webkit-slider-thumb]:pointer-events-auto [&::-webkit-slider-thumb]:h-5 [&::-webkit-slider-thumb]:w-5 [&::-webkit-slider-thumb]:appearance-none [&::-webkit-slider-thumb]:rounded-full [&::-webkit-slider-thumb]:border-2 [&::-webkit-slider-thumb]:border-white [&::-webkit-slider-thumb]:bg-stone-900 [&::-webkit-slider-thumb]:shadow-md [&::-moz-range-thumb]:pointer-events-auto [&::-moz-range-thumb]:h-5 [&::-moz-range-thumb]:w-5 [&::-moz-range-thumb]:rounded-full [&::-moz-range-thumb]:border-2 [&::-moz-range-thumb]:border-white [&::-moz-range-thumb]:bg-stone-900"
                                                    @input="handleMaxSlider(Number(($event.target as HTMLInputElement).value))"
                                                />
                                            </div>

                                            <div class="mt-4 grid grid-cols-2 gap-3">
                                                <div>
                                                    <label class="text-xs font-medium uppercase tracking-wide text-stone-500">Min Words</label>
                                                    <input
                                                        v-model.number="minWords"
                                                        type="number"
                                                        :min="props.config.generator.word_range.min"
                                                        :max="props.config.generator.word_range.max"
                                                        class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-2.5 text-sm text-stone-800 outline-none focus:border-teal-600"
                                                        @change="clampWords"
                                                    />
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium uppercase tracking-wide text-stone-500">Max Words</label>
                                                    <input
                                                        v-model.number="maxWords"
                                                        type="number"
                                                        :min="props.config.generator.word_range.min"
                                                        :max="props.config.generator.word_range.max"
                                                        class="mt-2 w-full rounded-2xl border border-stone-300 bg-white px-4 py-2.5 text-sm text-stone-800 outline-none focus:border-teal-600"
                                                        @change="clampWords"
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="space-y-2 rounded-3xl border border-stone-200 bg-stone-50 p-4 text-sm text-stone-700">
                                        <div class="flex items-center justify-between">
                                            <span>Topics</span>
                                            <span class="font-medium">{{ selectedCount('topics') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Article Types</span>
                                            <span class="font-medium">{{ selectedCount('article_types') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Formats</span>
                                            <span class="font-medium">{{ selectedCount('article_formats') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Vibes</span>
                                            <span class="font-medium">{{ selectedCount('vibes') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Reader Impact</span>
                                            <span class="font-medium">{{ selectedCount('reader_impacts') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Audience</span>
                                            <span class="font-medium">{{ selectedCount('audiences') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Context</span>
                                            <span class="font-medium">{{ selectedCount('contexts') }}</span>
                                        </div>
                                        <div class="flex items-center justify-between">
                                            <span>Perspective</span>
                                            <span class="font-medium">{{ selectedCount('perspectives') }}</span>
                                        </div>
                                    </div>

                                    <div class="space-y-3">
                                        <button
                                            type="button"
                                            class="inline-flex w-full items-center justify-center rounded-2xl bg-stone-900 px-4 py-3 text-sm font-semibold text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-60"
                                            :disabled="isLoading"
                                            @click="submitAction('generate')"
                                        >
                                            {{ loadingAction === 'generate' ? 'Generating...' : `Generate ${batchSize} Ideas` }}
                                        </button>

                                        <div class="grid grid-cols-2 gap-3">
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-2xl border border-teal-700 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800 hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="!canContinue"
                                                @click="submitAction('continue')"
                                            >
                                                {{ loadingAction === 'continue' ? 'Continuing...' : 'Continue' }}
                                            </button>
                                            <button
                                                type="button"
                                                class="inline-flex items-center justify-center rounded-2xl border border-stone-300 bg-white px-4 py-3 text-sm font-semibold text-stone-800 hover:bg-stone-50 disabled:cursor-not-allowed disabled:opacity-50"
                                                :disabled="!canReset"
                                                @click="submitAction('reset')"
                                            >
                                                {{ loadingAction === 'reset' ? 'Resetting...' : 'Reset' }}
                                            </button>
                                        </div>
                                    </div>

                                    <p v-if="errorMessage" class="rounded-2xl bg-red-50 px-4 py-3 text-sm text-red-700">
                                        {{ errorMessage }}
                                    </p>
                                    <p v-if="successMessage" class="rounded-2xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                                        {{ successMessage }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="meta"
                    class="mt-6 overflow-hidden rounded-[28px] border border-stone-200 bg-white shadow-[0_18px_50px_rgba(28,25,23,0.07)]"
                >
                    <div class="grid grid-cols-1 divide-y divide-stone-200 lg:grid-cols-[1.3fr_1fr_1fr] lg:divide-x lg:divide-y-0">
                        <div class="px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                                Session Summary
                            </p>
                            <div class="mt-3 flex items-end justify-between gap-6">
                                <div>
                                    <p class="text-3xl font-semibold tracking-tight text-stone-900">
                                        {{ meta.session_generated_count }}
                                    </p>
                                    <p class="mt-1 text-sm text-stone-600">
                                        Total rows generated in the current session.
                                    </p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-medium uppercase tracking-wide text-stone-500">
                                        Last batch
                                    </p>
                                    <p class="mt-2 text-2xl font-semibold text-stone-900">
                                        {{ meta.generated_count }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                                Session Status
                            </p>
                            <div class="mt-4 space-y-3 text-sm text-stone-700">
                                <div class="flex items-center justify-between">
                                    <span>Continue available</span>
                                    <span class="font-medium">{{ meta.can_continue ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Reset available</span>
                                    <span class="font-medium">{{ meta.can_reset ? 'Yes' : 'No' }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Batch size</span>
                                    <span class="font-medium">{{ meta.batch_size }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="px-6 py-5">
                            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-stone-500">
                                Current Range
                            </p>
                            <div class="mt-4 space-y-3 text-sm text-stone-700">
                                <div class="flex items-center justify-between">
                                    <span>Word range</span>
                                    <span class="font-medium">{{ meta.word_range.min }} - {{ meta.word_range.max }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Core combinations</span>
                                    <span class="font-medium">{{ meta.estimated_core_combinations }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Tier</span>
                                    <span class="font-medium capitalize">{{ meta.tier.replace('_', ' ') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div v-if="generatedRows.length" class="mt-6 overflow-hidden rounded-[28px] border border-stone-200 bg-white shadow-[0_18px_50px_rgba(28,25,23,0.07)]">
                    <div class="border-b border-stone-200 px-6 py-5">
                        <h2 class="text-lg font-semibold text-stone-900">Generated Ideas</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            Continue appends below this list. Reset replaces the session with a fresh shuffle using the same current settings.
                        </p>
                    </div>

                    <div class="space-y-3 p-4 sm:p-6">
                        <div
                            v-for="(row, index) in generatedRows"
                            :key="rowAccordionKey(row, index)"
                            class="overflow-hidden rounded-[24px] border border-stone-200 bg-stone-50 transition"
                        >
                            <button
                                type="button"
                                class="flex w-full items-center gap-3 px-4 py-4 text-left transition hover:bg-stone-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-teal-600/60"
                                :aria-expanded="isRowExpanded(row, index)"
                                :aria-controls="`generated-idea-panel-${rowAccordionKey(row, index)}`"
                                @click="toggleRow(row, index)"
                            >
                                <span class="shrink-0 rounded-full bg-white px-3 py-1 text-xs font-semibold uppercase tracking-[0.18em] text-stone-500 shadow-sm">
                                    Idea {{ index + 1 }}
                                </span>
                                <span class="min-w-0 flex-1 truncate text-sm font-semibold text-stone-800" :title="row.summary">
                                    {{ row.summary }}
                                </span>
                                <span
                                    class="shrink-0 text-stone-500 transition-transform duration-200"
                                    :class="isRowExpanded(row, index) ? 'rotate-180' : ''"
                                    aria-hidden="true"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="none">
                                        <path d="M5 8l5 5 5-5" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6" />
                                    </svg>
                                </span>
                            </button>

                            <div
                                v-if="isRowExpanded(row, index)"
                                :id="`generated-idea-panel-${rowAccordionKey(row, index)}`"
                                class="border-t border-stone-200 bg-white p-4 sm:p-5"
                            >
                                <div class="grid gap-5 xl:grid-cols-[0.9fr_1.1fr]">
                                    <div class="rounded-[24px] border border-stone-200 bg-stone-50 p-4">
                                        <div class="mb-4">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-500">Titles</h3>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="(title, titleIndex) in row.title_options"
                                                :key="`${row.id}-title-${titleIndex}`"
                                                class="rounded-[22px] border border-stone-200 bg-white p-4 shadow-sm"
                                            >
                                                <div class="flex items-start justify-between gap-3">
                                                    <div class="min-w-0 flex-1">
                                                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-stone-500">
                                                            Title Option {{ titleIndex + 1 }}
                                                        </p>
                                                        <p class="mt-2 text-sm leading-6 text-stone-800">{{ title }}</p>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        class="shrink-0 rounded-lg border px-2.5 py-1 text-[11px] font-medium transition-colors duration-150"
                                                        :class="isCopied(`${row.id}-title-${titleIndex}`)
                                                            ? 'border-teal-700 bg-teal-700 text-white'
                                                            : 'border-stone-300 bg-white text-stone-600 hover:bg-stone-50'"
                                                        @click.stop="copyText(title, `${row.id}-title-${titleIndex}`)"
                                                    >
                                                        {{ isCopied(`${row.id}-title-${titleIndex}`) ? 'Copied' : 'Copy' }}
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="rounded-[24px] border border-stone-200 bg-stone-900/95 p-4 shadow-[0_18px_45px_rgba(28,25,23,0.18)]">
                                        <div class="mb-4 flex items-center justify-between gap-3">
                                            <h3 class="text-sm font-semibold uppercase tracking-[0.16em] text-stone-300">Prompts</h3>
                                            <div class="flex flex-wrap justify-end gap-2 text-[11px] font-medium text-stone-400">
                                                <span class="rounded-full border border-white/10 px-2.5 py-1">2 Standard</span>
                                                <span class="rounded-full border border-white/10 px-2.5 py-1">3 Optimized</span>
                                            </div>
                                        </div>

                                        <div class="space-y-3">
                                            <div
                                                v-for="item in promptItems(row)"
                                                :key="item.id"
                                                class="overflow-hidden rounded-[22px] border border-white/10 bg-white/6"
                                            >
                                                <div class="flex items-start justify-between gap-3 px-4 py-3">
                                                    <button
                                                        type="button"
                                                        class="min-w-0 flex-1 text-left"
                                                        @click.stop="togglePrompt(item.id)"
                                                    >
                                                        <div class="flex items-center gap-2">
                                                            <span class="text-xs font-semibold uppercase tracking-[0.16em] text-stone-100">
                                                                {{ item.title }}
                                                            </span>
                                                            <span class="rounded-full border border-white/10 px-2 py-0.5 text-[10px] font-medium uppercase tracking-[0.14em] text-stone-300">
                                                                {{ item.label }}
                                                            </span>
                                                        </div>
                                                        <p
                                                            class="mt-2 text-sm leading-6 text-stone-100"
                                                            :class="isPromptExpanded(item.id) ? '' : '[display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:2] overflow-hidden'"
                                                        >
                                                            {{ item.prompt }}
                                                        </p>
                                                    </button>

                                                    <div class="flex shrink-0 items-start gap-2">
                                                        <button
                                                            type="button"
                                                            class="rounded-xl border px-2.5 py-1 text-xs font-medium transition-colors duration-150"
                                                            :class="isCopied(item.id)
                                                                ? 'border-teal-500 bg-teal-600 text-white'
                                                                : 'border-white/15 text-stone-100 hover:bg-white/10'"
                                                            @click.stop="copyText(item.prompt, item.id)"
                                                        >
                                                            {{ isCopied(item.id) ? 'Copied' : 'Copy' }}
                                                        </button>

                                                        <button
                                                            type="button"
                                                            class="flex h-8 w-8 items-center justify-center rounded-full text-stone-300 transition hover:bg-white/10 hover:text-stone-100"
                                                            :aria-expanded="isPromptExpanded(item.id)"
                                                            :aria-label="isPromptExpanded(item.id) ? 'Collapse prompt' : 'Expand prompt'"
                                                            @click.stop="togglePrompt(item.id)"
                                                        >
                                                            <svg
                                                                class="h-4 w-4 transition-transform duration-200"
                                                                :class="isPromptExpanded(item.id) ? 'rotate-180' : ''"
                                                                viewBox="0 0 20 20"
                                                                fill="none"
                                                                aria-hidden="true"
                                                            >
                                                                <path
                                                                    d="M5 8l5 5 5-5"
                                                                    stroke="currentColor"
                                                                    stroke-linecap="round"
                                                                    stroke-linejoin="round"
                                                                    stroke-width="1.6"
                                                                />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="mt-8 overflow-hidden rounded-[32px] border border-stone-200 bg-stone-950 text-stone-100 shadow-[0_26px_80px_rgba(28,25,23,0.26)]">
                    <div class="grid gap-0 lg:grid-cols-[minmax(0,1.7fr)_minmax(300px,1fr)_minmax(0,0.55fr)]">
                        <div class="border-b border-white/10 px-6 py-7 lg:border-b-0 lg:border-r lg:border-white/10 lg:px-8">
                            <div class="flex items-start gap-5">
                                <div class="flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-[22px] border border-white/10 bg-white/5 shadow-inner shadow-black/10">
                                    <img
                                        v-if="footerLogoVisible.nojo"
                                        :src="nojoFooterLogoSrc"
                                        alt="Nojo Consult logo"
                                        class="h-full w-full object-contain"
                                        @error="handleFooterLogoError('nojo')"
                                    />
                                    <span v-else class="text-sm font-semibold uppercase tracking-[0.22em] text-white">NC</span>
                                </div>

                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-teal-300">
                                        Nojo Consult
                                    </p>
                                    <h2 class="mt-2 text-2xl font-semibold tracking-tight text-white">
                                        Consultation-first footer built for the Nojo workspace.
                                    </h2>
                                    <p class="mt-3 max-w-2xl text-sm leading-6 text-stone-300">
                                        This content system belongs to Nojo Consult first. Keep this footer as the branded close for the consultation workspace, the admin planning flow, and the broader Nojo site presence.
                                    </p>

                                    <div class="mt-5 flex flex-wrap gap-2">
                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-stone-200">Nojo Consult</span>
                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-stone-200">Private Workspace</span>
                                        <span class="rounded-full border border-white/10 bg-white/5 px-3 py-1 text-[11px] font-medium uppercase tracking-[0.14em] text-stone-200">Content Formula</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border-b border-white/10 px-6 py-7 lg:border-b-0 lg:border-r lg:border-white/10">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-400">
                                Contact Media Here
                            </p>

                            <div class="mt-4 rounded-[24px] border border-white/10 bg-white/5 p-4">
                                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-stone-400">
                                    Media contact block
                                </p>
                                <p class="mt-2 text-sm leading-6 text-stone-200">
                                    Use this section for your public-facing media contact line, campaign inbox, press contact, or the person handling communication for Nojo Consult.
                                </p>
                            </div>

                            <div class="mt-4 rounded-[22px] border border-white/10 bg-black/20 px-4 py-3 text-xs leading-5 text-stone-400">
                                Keep this area direct and easy to update so the footer can work as both a clean brand close and a real contact anchor.
                            </div>
                        </div>

                        <div class="px-5 py-7 lg:px-4">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-stone-400">
                                Powered by
                            </p>

                            <div class="mt-4 rounded-[22px] border border-white/10 bg-white/5 p-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-11 w-11 shrink-0 items-center justify-center overflow-hidden rounded-2xl border border-white/10 bg-black/20">
                                        <img
                                            v-if="footerLogoVisible.awestruk"
                                            :src="awestrukFooterLogoSrc"
                                            alt="Awestruk Multimedia logo"
                                            class="h-full w-full object-contain"
                                            @error="handleFooterLogoError('awestruk')"
                                        />
                                        <span v-else class="text-[11px] font-semibold uppercase tracking-[0.22em] text-white">AM</span>
                                    </div>

                                    <div class="min-w-0">
                                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-teal-300">
                                            Powered by
                                        </p>
                                        <h3 class="mt-1 text-sm font-semibold text-white">Awestruk Multimedia</h3>
                                    </div>
                                </div>

                                <p class="mt-3 text-xs leading-5 text-stone-400">
                                    Creative and technical support for the Nojo consultation site.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col gap-3 border-t border-white/10 px-6 py-4 text-xs text-stone-400 sm:flex-row sm:items-center sm:justify-between lg:px-8">
                        <p>© {{ currentYear }} Nojo Consult. Internal consultation workspace.</p>
                        <p class="sm:text-right">Powered by Awestruk Multimedia.</p>
                    </div>
                </footer>
            </div>
        </div>
    </div>
</template>
