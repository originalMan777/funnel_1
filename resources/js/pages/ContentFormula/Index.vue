<script setup lang="ts">
import { computed, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';

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
        star_weights: Record<number, number>;
        required_groups: string[];
        tier_1_groups: string[];
        tier_2_groups: string[];
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
    title_styles: Array<{ key: string; label: string; template: string }>;
    prompt_styles: Array<{ key: string; label: string; template: string }>;
};

type SelectedItem = {
    label: string;
    stars: 1 | 2 | 3;
};

type GeneratedRow = {
    topic: string;
    article_type: string;
    article_format: string;
    vibe: string;
    reader_impact: string | null;
    audience: string | null;
    context: string | null;
    perspective: string | null;
    extra_direction: string | null;
    summary: string;
    title_options: string[];
    prompt_options: string[];
};

type GenerateResponse = {
    success: boolean;
    message: string;
    data: {
        meta: {
            requested_count: number;
            generated_count: number;
            estimated_core_combinations: number;
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
const loading = ref(false);
const errorMessage = ref('');
const successMessage = ref('');
const resultCount = ref<number>(props.config.generator.default_result_count || 50);
const extraDirection = ref('');
const generatedRows = ref<GeneratedRow[]>([]);
const meta = ref<GenerateResponse['data']['meta'] | null>(null);
const activeRowIndex = ref<number | null>(null);



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

    selected[groupKey][label] = {
        label,
        stars: 1,
    };
}

function setStars(groupKey: string, label: string, stars: 1 | 2 | 3) {
    if (!selected[groupKey]) {
        selected[groupKey] = {};
    }

    if (!selected[groupKey][label]) {
        selected[groupKey][label] = {
            label,
            stars,
        };
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
            selected[category.key][item] = {
                label: item,
                stars: 1,
            };
        }
    }
}
function toggleRow(index: number) {
    activeRowIndex.value = activeRowIndex.value === index ? null : index;
}

function clearAll(category: CategoryConfig) {
    selected[category.key] = {};
}

function selectedCount(groupKey: string): number {
    return Object.keys(selected[groupKey] || {}).length;
}

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

function buildPayload() {
    const groups: Record<string, SelectedItem[]> = {};

    for (const [groupKey, items] of Object.entries(selected)) {
        groups[groupKey] = Object.values(items);
    }

    return {
        result_count: resultCount.value,
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

async function generate() {
    successMessage.value = '';
    errorMessage.value = '';

    if (!validateRequiredSelections()) {
        return;
    }

    loading.value = true;

    try {
        const response = await axios.post<GenerateResponse>('/admin/content-formula/generate', buildPayload());

        if (response.data?.success) {
            generatedRows.value = response.data.data.rows || [];
            meta.value = response.data.data.meta || null;
            successMessage.value = response.data.message || 'Content ideas generated successfully.';
        } else {
            errorMessage.value = 'Unable to generate content ideas.';
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
        loading.value = false;
    }
}

async function copyText(text: string) {
    try {
        await navigator.clipboard.writeText(text);
        successMessage.value = 'Copied successfully.';
    } catch {
        errorMessage.value = 'Unable to copy to clipboard.';
    }
}
</script>

<template>
    <Head title="Content Formula Tool" />

    <div class="min-h-screen bg-gray-100">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
            <div class="mb-6 rounded-2xl bg-white p-6 shadow-sm">
                <h1 class="text-2xl font-semibold text-gray-900">Content Formula Tool</h1>
                <p class="mt-2 text-sm text-gray-600">
                    Build weighted content pools for your real estate consulting site and generate structured article ideas for admin use.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
                <!-- LEFT BUILDER -->
                <div class="lg:col-span-8">
                    <div class="rounded-2xl bg-white shadow-sm">
                        <div class="border-b border-gray-200 px-6 py-4">
                            <h2 class="text-lg font-semibold text-gray-900">Build Your Content Pool</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Check the options you want included. Use stars to control emphasis:
                                1 = active, 2 = slight emphasis, 3 = major emphasis.
                            </p>
                        </div>

                        <div class="max-h-[78vh] overflow-y-auto px-6 py-6">
                            <!-- REQUIRED CORE -->
                            <div class="space-y-6">
                                <div
                                    v-for="category in requiredCategories"
                                    :key="category.key"
                                    class="rounded-2xl border border-gray-200 bg-gray-50"
                                >
                                    <div
                                        class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                        @click="toggleGroup(category.key)"
                                    >
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-base font-semibold text-gray-900">
                                                    {{ category.label }}
                                                </h3>
                                                <span
                                                    class="inline-flex rounded-full bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700"
                                                >
                                                    Required
                                                </span>
                                            </div>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ category.description }}
                                            </p>
                                        </div>

                                        <div class="flex items-center gap-3">
                                            <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm">
                                                {{ selectedCount(category.key) }} selected
                                            </span>
                                            <span class="text-sm text-gray-500">
                                                {{ openGroups[category.key] ? '−' : '+' }}
                                            </span>
                                        </div>
                                    </div>

                                    <div v-if="openGroups[category.key]" class="border-t border-gray-200 bg-white px-5 py-5">
                                        <div
                                            v-if="props.config.ui.show_search_for_groups.includes(category.key)"
                                            class="mb-4"
                                        >
                                            <input
                                                v-model="searchTerms[category.key]"
                                                type="text"
                                                :placeholder="`Search ${category.label.toLowerCase()}...`"
                                                class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-800 outline-none ring-0 placeholder:text-gray-400 focus:border-gray-400"
                                            />
                                        </div>

                                        <div
                                            v-if="props.config.ui.show_select_all_for_groups.includes(category.key)"
                                            class="mb-4 flex flex-wrap items-center gap-2"
                                        >
                                            <button
                                                type="button"
                                                class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                @click.stop="selectAll(category)"
                                            >
                                                Select all
                                            </button>
                                            <button
                                                type="button"
                                                class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                @click.stop="clearAll(category)"
                                            >
                                                Clear all
                                            </button>
                                        </div>

                                        <div class="space-y-2">
                                            <div
                                                v-for="item in filteredItems(category)"
                                                :key="`${category.key}-${item}`"
                                                class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                            >
                                                <label class="flex items-center gap-3">
                                                    <input
                                                        type="checkbox"
                                                        class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-0"
                                                        :checked="isChecked(category.key, item)"
                                                        @change="toggleItem(category.key, item)"
                                                    />
                                                    <span class="text-sm font-medium text-gray-800">
                                                        {{ item }}
                                                    </span>
                                                </label>

                                                <div class="flex items-center gap-2">
                                                    <button
                                                        v-for="star in [1, 2, 3]"
                                                        :key="star"
                                                        type="button"
                                                        class="rounded-lg border px-2.5 py-1 text-xs font-medium"
                                                        :class="getStars(category.key, item) === star
                                                            ? 'border-blue-600 bg-blue-600 text-white'
                                                            : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
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

                            <!-- OPTIONAL -->
                            <div class="mt-8">
                                <div class="mb-4">
                                    <h2 class="text-lg font-semibold text-gray-900">Optional Refinements</h2>
                                    <p class="mt-1 text-sm text-gray-600">
                                        These help shape the article further. Audience, Context, and Reader Impact flow into every row if selected. Perspective is used lightly.
                                    </p>
                                </div>

                                <div class="space-y-6">
                                    <div
                                        v-for="category in optionalCategories"
                                        :key="category.key"
                                        class="rounded-2xl border border-gray-200 bg-gray-50"
                                    >
                                        <div
                                            class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                            @click="toggleGroup(category.key)"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-base font-semibold text-gray-900">
                                                        {{ category.label }}
                                                    </h3>
                                                    <span
                                                        class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700"
                                                    >
                                                        Optional
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    {{ category.description }}
                                                </p>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <span class="rounded-full bg-white px-3 py-1 text-xs font-medium text-gray-700 shadow-sm">
                                                    {{ selectedCount(category.key) }} selected
                                                </span>
                                                <span class="text-sm text-gray-500">
                                                    {{ openGroups[category.key] ? '−' : '+' }}
                                                </span>
                                            </div>
                                        </div>

                                        <div v-if="openGroups[category.key]" class="border-t border-gray-200 bg-white px-5 py-5">
                                            <div
                                                v-if="props.config.ui.show_search_for_groups.includes(category.key)"
                                                class="mb-4"
                                            >
                                                <input
                                                    v-model="searchTerms[category.key]"
                                                    type="text"
                                                    :placeholder="`Search ${category.label.toLowerCase()}...`"
                                                    class="w-full rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-800 outline-none ring-0 placeholder:text-gray-400 focus:border-gray-400"
                                                />
                                            </div>

                                            <div
                                                v-if="props.config.ui.show_select_all_for_groups.includes(category.key)"
                                                class="mb-4 flex flex-wrap items-center gap-2"
                                            >
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                    @click.stop="selectAll(category)"
                                                >
                                                    Select all
                                                </button>
                                                <button
                                                    type="button"
                                                    class="rounded-xl border border-gray-300 bg-white px-3 py-2 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                    @click.stop="clearAll(category)"
                                                >
                                                    Clear all
                                                </button>
                                            </div>

                                            <div class="space-y-2">
                                                <div
                                                    v-for="item in filteredItems(category)"
                                                    :key="`${category.key}-${item}`"
                                                    class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between"
                                                >
                                                    <label class="flex items-center gap-3">
                                                        <input
                                                            type="checkbox"
                                                            class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-0"
                                                            :checked="isChecked(category.key, item)"
                                                            @change="toggleItem(category.key, item)"
                                                        />
                                                        <span class="text-sm font-medium text-gray-800">
                                                            {{ item }}
                                                        </span>
                                                    </label>

                                                    <div class="flex items-center gap-2">
                                                        <button
                                                            v-for="star in [1, 2, 3]"
                                                            :key="star"
                                                            type="button"
                                                            class="rounded-lg border px-2.5 py-1 text-xs font-medium"
                                                            :class="getStars(category.key, item) === star
                                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                                : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'"
                                                            @click.stop="setStars(category.key, item, star as 1 | 2 | 3)"
                                                        >
                                                            {{ star }}★
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- EXTRA DIRECTION -->
                                    <div
                                        v-if="categoryMap.extra_direction"
                                        class="rounded-2xl border border-gray-200 bg-gray-50"
                                    >
                                        <div
                                            class="flex cursor-pointer items-start justify-between gap-4 px-5 py-4"
                                            @click="toggleGroup('extra_direction')"
                                        >
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <h3 class="text-base font-semibold text-gray-900">
                                                        {{ categoryMap.extra_direction.label }}
                                                    </h3>
                                                    <span
                                                        class="inline-flex rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700"
                                                    >
                                                        Optional
                                                    </span>
                                                </div>
                                                <p class="mt-1 text-sm text-gray-600">
                                                    {{ categoryMap.extra_direction.description }}
                                                </p>
                                            </div>

                                            <span class="text-sm text-gray-500">
                                                {{ openGroups.extra_direction ? '−' : '+' }}
                                            </span>
                                        </div>

                                        <div v-if="openGroups.extra_direction" class="border-t border-gray-200 bg-white px-5 py-5">
                                            <textarea
                                                v-model="extraDirection"
                                                rows="4"
                                                placeholder="Example: for Trinidad, first-time homebuyers, luxury market, faith-based perspective..."
                                                class="w-full rounded-xl border border-gray-300 px-4 py-3 text-sm text-gray-800 outline-none ring-0 placeholder:text-gray-400 focus:border-gray-400"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT CONTROL CENTER -->
                <div class="lg:col-span-4">
                    <div
                        class="space-y-6"
                        :class="props.config.ui.sticky_control_center ? 'lg:sticky lg:top-6' : ''"
                    >
                        <div class="rounded-2xl bg-white p-6 shadow-sm">
                            <h2 class="text-lg font-semibold text-gray-900">Control Center</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Review your current pool, then generate a balanced set of article ideas.
                            </p>

                            <div class="mt-5 space-y-4">
                                <div class="grid grid-cols-2 gap-3">
                                    <div class="rounded-xl bg-gray-50 p-4">
                                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                            Total Selected
                                        </div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                                            {{ totalSelectedCount }}
                                        </div>
                                    </div>

                                    <div class="rounded-xl bg-gray-50 p-4">
                                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                            Core Combos
                                        </div>
                                        <div class="mt-2 text-2xl font-semibold text-gray-900">
                                            {{ estimatedCoreCombinations }}
                                        </div>
                                    </div>
                                </div>

                                <div class="rounded-xl bg-gray-50 p-4">
                                    <label class="block text-xs font-medium uppercase tracking-wide text-gray-500">
                                        Number of results
                                    </label>
                                    <input
                                        v-model="resultCount"
                                        type="number"
                                        min="1"
                                        :max="props.config.generator.max_result_count"
                                        class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-2 text-sm text-gray-800 outline-none ring-0 focus:border-gray-400"
                                    />
                                </div>

                                <div class="space-y-2 rounded-xl bg-gray-50 p-4 text-sm text-gray-700">
                                    <div class="flex items-center justify-between">
                                        <span>Topics</span>
                                        <span class="font-medium">{{ selectedCount('topics') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Type of Article</span>
                                        <span class="font-medium">{{ selectedCount('article_types') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Article Format</span>
                                        <span class="font-medium">{{ selectedCount('article_formats') }}</span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span>Vibe</span>
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

                                <button
                                    type="button"
                                    class="inline-flex w-full items-center justify-center rounded-2xl bg-gray-900 px-4 py-3 text-sm font-semibold text-white hover:bg-black disabled:cursor-not-allowed disabled:opacity-60"
                                    :disabled="loading"
                                    @click="generate"
                                >
                                    {{ loading ? 'Generating...' : 'Generate 50 Ideas' }}
                                </button>

                                <p v-if="errorMessage" class="rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700">
                                    {{ errorMessage }}
                                </p>

                                <p v-if="successMessage" class="rounded-xl bg-green-50 px-4 py-3 text-sm text-green-700">
                                    {{ successMessage }}
                                </p>
                            </div>
                        </div>

                        <div v-if="meta" class="rounded-2xl bg-white p-6 shadow-sm">
                            <h3 class="text-base font-semibold text-gray-900">Generation Summary</h3>
                            <div class="mt-4 space-y-2 text-sm text-gray-700">
                                <div class="flex items-center justify-between">
                                    <span>Requested</span>
                                    <span class="font-medium">{{ meta.requested_count }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Generated</span>
                                    <span class="font-medium">{{ meta.generated_count }}</span>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span>Estimated core combinations</span>
                                    <span class="font-medium">{{ meta.estimated_core_combinations }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- RESULTS -->
<div v-if="generatedRows.length" class="mt-6 rounded-2xl bg-white p-6 shadow-sm">
    <div class="mb-5">
        <h2 class="text-lg font-semibold text-gray-900">Generated Ideas</h2>
        <p class="mt-1 text-sm text-gray-600">
            Click any row to expand it. Only one idea opens at a time.
        </p>
    </div>

    <div class="space-y-2">
        <div
            v-for="(row, index) in generatedRows"
            :key="`${row.summary}-${index}`"
            class="overflow-hidden rounded-xl border border-gray-200 bg-gray-50"
        >
            <!-- Collapsed / Header Row -->
            <button
                type="button"
                class="flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-gray-100"
                @click="toggleRow(index)"
            >
                <span class="shrink-0 text-xs font-semibold uppercase tracking-wide text-gray-500">
                    Idea {{ index + 1 }}
                </span>

                <span
                    class="min-w-0 flex-1 truncate text-sm font-medium text-gray-800"
                    :title="row.summary"
                >
                    {{ row.summary }}
                </span>

                <span class="shrink-0 text-sm text-gray-500">
                    {{ activeRowIndex === index ? '▴' : '▾' }}
                </span>
            </button>

            <!-- Expanded Content -->
            <div
                v-if="activeRowIndex === index"
                class="border-t border-gray-200 bg-white p-4"
            >
                <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
                    <div class="rounded-xl bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900">Title Options</h4>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(title, titleIndex) in row.title_options"
                                :key="`${index}-title-${titleIndex}`"
                                class="flex items-start justify-between gap-3 rounded-xl border border-gray-200 bg-white px-3 py-3"
                            >
                                <p class="text-sm text-gray-800">
                                    {{ title }}
                                </p>

                                <button
                                    type="button"
                                    class="shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                    @click="copyText(title)"
                                >
                                    Copy
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-xl bg-gray-50 p-4">
                        <div class="mb-3 flex items-center justify-between gap-3">
                            <h4 class="text-sm font-semibold text-gray-900">Prompt Options</h4>
                        </div>

                        <div class="space-y-2">
                            <div
                                v-for="(prompt, promptIndex) in row.prompt_options"
                                :key="`${index}-prompt-${promptIndex}`"
                                class="rounded-xl border border-gray-200 bg-white px-3 py-3"
                            >
                                <div class="flex items-start justify-between gap-3">
                                    <p class="text-sm leading-6 text-gray-800">
                                        {{ prompt }}
                                    </p>

                                    <button
                                        type="button"
                                        class="shrink-0 rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        @click="copyText(prompt)"
                                    >
                                        Copy
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div
                    v-if="row.audience || row.context || row.reader_impact || row.perspective || row.extra_direction"
                    class="mt-4 flex flex-wrap gap-2"
                >
                    <span
                        v-if="row.audience"
                        class="rounded-full bg-blue-50 px-3 py-1 text-xs font-medium text-blue-700"
                    >
                        Audience: {{ row.audience }}
                    </span>

                    <span
                        v-if="row.context"
                        class="rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700"
                    >
                        Context: {{ row.context }}
                    </span>

                    <span
                        v-if="row.reader_impact"
                        class="rounded-full bg-purple-50 px-3 py-1 text-xs font-medium text-purple-700"
                    >
                        Impact: {{ row.reader_impact }}
                    </span>

                    <span
                        v-if="row.perspective"
                        class="rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700"
                    >
                        Perspective: {{ row.perspective }}
                    </span>

                    <span
                        v-if="row.extra_direction"
                        class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium text-gray-700"
                    >
                        Extra: {{ row.extra_direction }}
                    </span>
                </div>
            </div>
    </div>
</div>
            </div>
        </div>
    </div>
</template>
