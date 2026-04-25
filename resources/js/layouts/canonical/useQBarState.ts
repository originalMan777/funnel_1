import { computed, reactive } from 'vue';
import type {
    MetricCategoryKey,
    QuickViewPayload,
} from '@/components/admin/analytics/metricRegistry';

type AnalyticsQBarState = {
    active: boolean;
    title: string;
    description: string;
    metrics: QuickViewPayload[];
    activeGroup: MetricCategoryKey | 'all';
    selectedMetricKey: string | null;
};

const analyticsQBarState = reactive<AnalyticsQBarState>({
    active: false,
    title: 'Analytics Focus',
    description: '',
    metrics: [],
    activeGroup: 'all',
    selectedMetricKey: null,
});

const visibleAnalyticsMetrics = computed(() => {
    if (analyticsQBarState.activeGroup === 'all') {
        return analyticsQBarState.metrics;
    }

    return analyticsQBarState.metrics.filter(
        (metric) => metric.category === analyticsQBarState.activeGroup,
    );
});

const selectedAnalyticsMetric = computed(() => {
    if (!analyticsQBarState.selectedMetricKey) {
        return (
            visibleAnalyticsMetrics.value[0] ??
            analyticsQBarState.metrics[0] ??
            null
        );
    }

    return (
        visibleAnalyticsMetrics.value.find(
            (metric) => metric.key === analyticsQBarState.selectedMetricKey,
        ) ??
        analyticsQBarState.metrics.find(
            (metric) => metric.key === analyticsQBarState.selectedMetricKey,
        ) ??
        visibleAnalyticsMetrics.value[0] ??
        analyticsQBarState.metrics[0] ??
        null
    );
});

function syncSelection() {
    const visible = visibleAnalyticsMetrics.value;

    if (!analyticsQBarState.metrics.length) {
        analyticsQBarState.selectedMetricKey = null;

        return;
    }

    if (!visible.length) {
        analyticsQBarState.activeGroup = 'all';
    }

    const hasSelectedMetric = visibleAnalyticsMetrics.value.some(
        (metric) => metric.key === analyticsQBarState.selectedMetricKey,
    );

    if (!hasSelectedMetric) {
        analyticsQBarState.selectedMetricKey =
            visibleAnalyticsMetrics.value[0]?.key ??
            analyticsQBarState.metrics[0]?.key ??
            null;
    }
}

export function useQBarState() {
    const activateAnalyticsMode = (payload: {
        title?: string;
        description?: string;
        metrics: QuickViewPayload[];
        selectedMetricKey?: string | null;
    }) => {
        analyticsQBarState.active = true;
        analyticsQBarState.title = payload.title ?? 'Analytics Focus';
        analyticsQBarState.description = payload.description ?? '';
        analyticsQBarState.metrics = payload.metrics;
        analyticsQBarState.selectedMetricKey =
            payload.selectedMetricKey ?? analyticsQBarState.selectedMetricKey;

        syncSelection();
    };

    const deactivateAnalyticsMode = () => {
        analyticsQBarState.active = false;
        analyticsQBarState.title = 'Analytics Focus';
        analyticsQBarState.description = '';
        analyticsQBarState.metrics = [];
        analyticsQBarState.activeGroup = 'all';
        analyticsQBarState.selectedMetricKey = null;
    };

    const setAnalyticsGroup = (group: MetricCategoryKey | 'all') => {
        analyticsQBarState.activeGroup = group;
        syncSelection();
    };

    const setAnalyticsMetric = (key: string) => {
        analyticsQBarState.selectedMetricKey = key;
        syncSelection();
    };

    return {
        analyticsQBarState,
        visibleAnalyticsMetrics,
        selectedAnalyticsMetric,
        activateAnalyticsMode,
        deactivateAnalyticsMode,
        setAnalyticsGroup,
        setAnalyticsMetric,
    };
}
