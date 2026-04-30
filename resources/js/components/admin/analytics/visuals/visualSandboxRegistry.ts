import type { Component } from 'vue';

import VisualAreaTrend from '@/components/admin/analytics/visuals/VisualAreaTrend.vue';
import VisualCompactDataTable from '@/components/admin/analytics/visuals/VisualCompactDataTable.vue';
import VisualFlowMovement from '@/components/admin/analytics/visuals/VisualFlowMovement.vue';
import VisualGoalProgress from '@/components/admin/analytics/visuals/VisualGoalProgress.vue';
import VisualHeavyDonutContrast from '@/components/admin/analytics/visuals/VisualHeavyDonutContrast.vue';
import VisualScreenTileNumber from '@/components/admin/analytics/visuals/VisualScreenTileNumber.vue';
import VisualSignedNumberGauge from '@/components/admin/analytics/visuals/VisualSignedNumberGauge.vue';
import VisualSplitFlowTransfer from '@/components/admin/analytics/visuals/VisualSplitFlowTransfer.vue';
import VisualStackedBlockComparison from '@/components/admin/analytics/visuals/VisualStackedBlockComparison.vue';
import VisualStraightStrengthTrack from '@/components/admin/analytics/visuals/VisualStraightStrengthTrack.vue';
import VisualTaperedFunnel from '@/components/admin/analytics/visuals/VisualTaperedFunnel.vue';
import VisualGridHeatmap from '@/components/admin/analytics/visuals/VisualGridHeatmap.vue';
import VisualLinearHeatmap from '@/components/admin/analytics/visuals/VisualLinearHeatmap.vue';


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
        key: 'flow-movement',
        name: 'Flow Movement',
        status: 'Testing',
        purpose: 'Shows movement through funnel stages.',
        label: 'Lead Flow',
        meta: 'Movement through funnel stages',
        component: VisualFlowMovement,
        data: [
            { label: 'Visitor', value: 100, color: '#2563eb', meta: 'Entry traffic' },
            { label: 'Engaged', value: 72, color: '#7c3aed', meta: 'Interacted' },
            { label: 'Captured', value: 38, color: '#16a34a', meta: 'Submitted lead' },
            { label: 'Qualified', value: 21, color: '#dc2626', meta: 'High intent' },
        ],
    },
    {
        key: 'split-flow-transfer',
        name: 'Split Flow Transfer',
        status: 'Testing',
        purpose: 'Shows one transition split into continuation and drop-off.',
        label: 'Flow Transfer',
        meta: 'Continue vs drop-off movement',
        component: VisualSplitFlowTransfer,
    },
    {
        key: 'signed-number-gauge',
        name: 'Signed Number Gauge',
        status: 'Testing',
        purpose: 'Number visual for negative, zero, and positive movement.',
        label: 'Net Change',
        meta: 'Negative / zero / positive signal',
        component: VisualSignedNumberGauge,
    },
    {
        key: 'goal-progress',
        name: 'Goal Progress',
        status: 'Testing',
        purpose: 'Shows progress toward a defined target.',
        label: 'Monthly Lead Goal',
        meta: 'Current progress toward target',
        component: VisualGoalProgress,
    },
    {
        key: 'area-trend',
        name: 'Area Trend',
        status: 'Testing',
        purpose: 'Filled trend visual showing activity volume over time.',
        label: 'Lead Activity',
        value: '1,842',
        meta: '30-day activity volume',
        component: VisualAreaTrend,
    },
    {
        key: 'compact-data-table',
        name: 'Compact Data Table',
        status: 'Testing',
        purpose: 'Structured multi-field table for exact analytics values.',
        label: 'Source Performance',
        meta: 'Structured lead source breakdown',
        component: VisualCompactDataTable,
    },
    {
        key: 'tapered-funnel',
        name: 'Tapered Funnel',
        status: 'Testing',
        purpose: 'True funnel shape with inward taper.',
        label: 'Funnel',
        meta: 'Tapered conversion flow',
        component: VisualTaperedFunnel,
    },

    {
    key: 'grid-heatmap',
    name: 'Grid Heatmap',
    status: 'Testing',
    purpose: 'Shows intensity across a grid using color instead of numbers.',
    label: 'Heatmap',
    meta: 'Activity intensity grid',
    component: VisualGridHeatmap,
},

{
    key: 'linear-heatmap',
    name: 'Linear Heatmap',
    status: 'Testing',
    purpose: 'Shows intensity across a single sequence using heat blocks.',
    label: 'Intensity Strip',
    meta: 'Activity concentration across one sequence',
    component: VisualLinearHeatmap,
},


];
