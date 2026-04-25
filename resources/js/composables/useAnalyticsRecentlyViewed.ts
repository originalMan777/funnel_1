import { computed, ref } from 'vue';

const STORAGE_KEY = 'analytics.recently-viewed';
const MAX_ITEMS = 5;

export type AnalyticsRecentlyViewedItem = {
    key: string;
    title: string;
    href: string;
    subtitle?: string | null;
    viewedAt: string;
};

const items = ref<AnalyticsRecentlyViewedItem[]>([]);
let loaded = false;

function canUseStorage() {
    return (
        typeof window !== 'undefined' &&
        typeof window.localStorage !== 'undefined'
    );
}

function normalizeItems(value: unknown): AnalyticsRecentlyViewedItem[] {
    if (!Array.isArray(value)) {
        return [];
    }

    return value
        .filter((item): item is AnalyticsRecentlyViewedItem => {
            return Boolean(
                item &&
                typeof item === 'object' &&
                typeof (item as AnalyticsRecentlyViewedItem).key === 'string' &&
                typeof (item as AnalyticsRecentlyViewedItem).title ===
                    'string' &&
                typeof (item as AnalyticsRecentlyViewedItem).href ===
                    'string' &&
                typeof (item as AnalyticsRecentlyViewedItem).viewedAt ===
                    'string',
            );
        })
        .slice(0, MAX_ITEMS);
}

function persist() {
    if (!canUseStorage()) {
        return;
    }

    window.localStorage.setItem(STORAGE_KEY, JSON.stringify(items.value));
}

function ensureLoaded() {
    if (loaded || !canUseStorage()) {
        loaded = true;

        return;
    }

    try {
        const raw = window.localStorage.getItem(STORAGE_KEY);
        items.value = raw ? normalizeItems(JSON.parse(raw)) : [];
    } catch {
        items.value = [];
    } finally {
        loaded = true;
    }
}

export function useAnalyticsRecentlyViewed() {
    ensureLoaded();

    const recordItem = (
        item: Omit<AnalyticsRecentlyViewedItem, 'viewedAt'>,
    ) => {
        ensureLoaded();

        const nextItem: AnalyticsRecentlyViewedItem = {
            ...item,
            viewedAt: new Date().toISOString(),
        };

        items.value = [
            nextItem,
            ...items.value.filter((existing) => existing.key !== item.key),
        ].slice(0, MAX_ITEMS);

        persist();
    };

    return {
        items: computed(() => items.value),
        recordItem,
    };
}
