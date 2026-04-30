import type { Component } from 'vue';

import VisualConsoleDisplayNumber from '@/components/admin/analytics/visuals/VisualConsoleDisplayNumber.vue';
import VisualHalfDonutMovingDot from '@/components/admin/analytics/visuals/VisualHalfDonutMovingDot.vue';
import VisualHeavyDonutContrast from '@/components/admin/analytics/visuals/VisualHeavyDonutContrast.vue';
import VisualHeavyDonutDistribution from '@/components/admin/analytics/visuals/VisualHeavyDonutDistribution.vue';
import VisualScreenTileNumber from '@/components/admin/analytics/visuals/VisualScreenTileNumber.vue';
import VisualStraightStrengthTrack from '@/components/admin/analytics/visuals/VisualStraightStrengthTrack.vue';
import VisualTacticalDonutDistribution from '@/components/admin/analytics/visuals/VisualTacticalDonutDistribution.vue';
import VisualHardEdgeComparisonBars from '@/components/admin/analytics/visuals/VisualHardEdgeComparisonBars.vue';
import VisualStackedBlockComparison from '@/components/admin/analytics/visuals/VisualStackedBlockComparison.vue';
import VisualRankedSourceTable from '@/components/admin/analytics/visuals/VisualRankedSourceTable.vue';
import VisualRangeVariance from '@/components/admin/analytics/visuals/VisualRangeVariance.vue';
import VisualDeltaChange from '@/components/admin/analytics/visuals/VisualDeltaChange.vue';


export type SandboxVisual = {
    key: string;
    name: string;
    status: 'Testing' | 'Accepted';
    purpose?: string;
    label?: string;
    value?: string | number;
    meta?: string;
    foot?: string;
    component: Component;
    data?: unknown;
};

export const visualSandboxRegistry: SandboxVisual[] = [
    {
        key: 'screen-tile-number',
        name: 'Screen Tile Number',
        status: 'Accepted',
        purpose: 'Standalone number display for raw totals without judgment language.',
        label: 'Total Leads',
        value: '1,284',
        meta: 'Current captured volume',
        foot: 'Raw number display',
        component: VisualScreenTileNumber,
    },
    {
        key: 'console-display-number',
        name: 'Console Display Number',
        status: 'Accepted',
        purpose: 'Operator-style numeric display for a high-signal KPI value.',
        label: 'Pipeline Value',
        value: '$128K',
        meta: 'Qualified opportunity total',
        foot: 'Operator display panel',
        component: VisualConsoleDisplayNumber,
    },
    {
        key: 'half-donut-moving-dot',
        name: 'Half Donut — Moving Dot',
        status: 'Accepted',
        purpose: 'Strength indicator where the marker travels across a weak-to-strong arc.',
        label: 'Signal Strength',
        value: '72',
        meta: 'Dot travels across the arc',
        component: VisualHalfDonutMovingDot,
    },
    {
        key: 'straight-strength-track',
        name: 'Straight Strength Track / Hard Marker',
        status: 'Accepted',
        purpose: 'Linear weak-to-strong track with a hard marker for direct scanning.',
        label: 'Signal Strength',
        value: '72',
        meta: 'Hard marker across strength track',
        component: VisualStraightStrengthTrack,
    },
    {
        key: 'heavy-donut-distribution',
        name: 'Donut — Heavy Segments',
        status: 'Testing',
        purpose: 'Bold distribution visual for comparing source share at a glance.',
        label: 'Traffic Sources',
        meta: 'Bold distribution visual',
        component: VisualHeavyDonutDistribution,
        data: [
            { label: 'Google', value: 50, color: '#22c55e' },
            { label: 'Facebook', value: 30, color: '#f59e0b' },
            { label: 'Direct', value: 20, color: '#ef4444' },
        ],
    },
    {
        key: 'heavy-donut-contrast',
        name: 'Donut — Heavy Contrast',
        status: 'Testing',
        purpose: 'High contrast donut with dark segments and white separation.',
        label: 'Traffic Sources',
        meta: 'White separation test',
        component: VisualHeavyDonutContrast,
        data: [
            { label: 'Search', value: 50, color: '#1e3a8a' },
            { label: 'Social', value: 30, color: '#6d28d9' },
            { label: 'Direct', value: 20, color: '#166534' },
        ],
    },
    {
        key: 'tactical-donut-distribution',
        name: 'Donut — Tactical Blocks',
        status: 'Testing',
        purpose: 'Higher-contrast donut candidate for immediate distribution readability.',
        label: 'Traffic Sources',
        meta: 'Immediate readability test',
        component: VisualTacticalDonutDistribution,
        data: [
            { label: 'Google', value: 50, color: '#84cc16' },
            { label: 'Facebook', value: 30, color: '#eab308' },
            { label: 'Direct', value: 20, color: '#dc2626' },
        ],
    },
    {
    key: 'hard-edge-comparison-bars',
    name: 'Hard Edge Comparison Bars',
    status: 'Testing',
    purpose: 'Sharp comparison visual with hard edges and thin white outlines.',
    label: 'Source Comparison',
    meta: 'Hard-edge ranked performance',
    component: VisualHardEdgeComparisonBars,
    data: [
        { label: 'Google', value: 82, color: '#2563eb', meta: 'Highest signal' },
        { label: 'Facebook', value: 64, color: '#7c3aed', meta: 'Strong mid-tier' },
        { label: 'Direct', value: 48, color: '#16a34a', meta: 'Stable baseline' },
        { label: 'Referral', value: 31, color: '#dc2626', meta: 'Needs lift' },
    ],
},

{
    key: 'stacked-block-comparison',
    name: 'Stacked Block Comparison',
    status: 'Testing',
    purpose: 'Hard-edge stacked comparison blocks with a side index.',
    label: 'Source Stack',
    meta: 'Stacked comparison blocks',
    component: VisualStackedBlockComparison,
    data: [
        { label: 'Google', value: 82, color: '#2563eb', meta: 'Highest signal' },
        { label: 'Facebook', value: 64, color: '#7c3aed', meta: 'Strong mid-tier' },
        { label: 'Direct', value: 48, color: '#16a34a', meta: 'Stable baseline' },
        { label: 'Referral', value: 31, color: '#dc2626', meta: 'Needs lift' },
    ],
},

{
    key: 'ranked-source-table',
    name: 'Ranked Source Table',
    status: 'Testing',
    purpose: 'Premium ranking table for top performers with gradient signal bars.',
    label: 'Source Ranking',
    meta: 'Top performers by conversion signal',
    component: VisualRankedSourceTable,
    data: [
        { rank: 1, label: 'Google Search', value: 82, color: '#2563eb', meta: 'Lead quality', change: '+12%' },
        { rank: 2, label: 'LinkedIn', value: 71, color: '#7c3aed', meta: 'B2B intent', change: '+8%' },
        { rank: 3, label: 'Direct Traffic', value: 58, color: '#16a34a', meta: 'Returning visitors', change: '+4%' },
        { rank: 4, label: 'Facebook Ads', value: 43, color: '#dc2626', meta: 'Cold traffic', change: '-3%' },
    ],
},

{
    key: 'range-variance',
    name: 'Range Variance',
    status: 'Testing',
    purpose: 'Shows position within a defined performance range.',
    label: 'Range Variance',
    meta: 'Position inside min → max',
    component: VisualRangeVariance,
    data: [
        { label: 'Google', min: 20, max: 90, value: 72, color: '#2563eb', meta: 'Strong range' },
        { label: 'Facebook', min: 10, max: 80, value: 55, color: '#7c3aed', meta: 'Mid performance' },
        { label: 'Direct', min: 30, max: 85, value: 48, color: '#16a34a', meta: 'Stable baseline' },
        { label: 'Referral', min: 5, max: 60, value: 25, color: '#dc2626', meta: 'Low band' },
    ],
},

{
    key: 'delta-change',
    name: 'Delta Change',
    status: 'Testing',
    purpose: 'Shows movement from previous period to current period.',
    label: 'Delta Change',
    meta: 'Previous vs current movement',
    component: VisualDeltaChange,
    data: [
        { label: 'Google', previous: 64, current: 82, color: '#2563eb', meta: 'Search traffic lift' },
        { label: 'LinkedIn', previous: 58, current: 71, color: '#7c3aed', meta: 'B2B intent rising' },
        { label: 'Direct', previous: 61, current: 54, color: '#16a34a', meta: 'Returning visitors dipped' },
        { label: 'Facebook', previous: 49, current: 43, color: '#dc2626', meta: 'Cold traffic down' },
    ],
},

];
