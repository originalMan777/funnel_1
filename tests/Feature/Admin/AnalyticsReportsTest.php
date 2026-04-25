<?php

namespace Tests\Feature\Admin;

use App\Models\Analytics\Conversion;
use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\Cta;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Page;
use App\Models\Analytics\ScenarioDefinition;
use App\Models\Analytics\Session;
use App\Models\Analytics\SessionScenario;
use App\Models\Analytics\Visitor;
use App\Models\LeadBox;
use App\Models\Popup;
use App\Models\User;
use App\Services\Analytics\RollupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class AnalyticsReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_admin_can_open_dimension_report_pages(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.metrics.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Metrics/Index'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.pages.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Pages'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.ctas.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Ctas'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.lead-boxes.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/LeadBoxes'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.popups.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Popups'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.conversions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Conversions'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.attribution.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Attribution'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.funnels.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Funnels'));

        $this->actingAs($admin)
            ->get(route('admin.analytics.scenarios.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Analytics/Scenarios'));
    }

    public function test_admin_can_open_supported_cluster_pages_through_generic_cluster_view(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'traffic']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'traffic')
                ->where('subClusters.0.key', 'pages')
                ->where('subClusters.1.key', 'ctas')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'capture']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'capture')
                ->where('subClusters.0.key', 'lead_boxes')
                ->where('subClusters.1.key', 'popups')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'flow']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'flow')
                ->where('subClusters.0.key', 'funnels')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'behavior']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'behavior')
                ->where('subClusters.0.key', 'scenarios')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'results']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'results')
                ->where('subClusters.0.key', 'conversions')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', ['clusterKey' => 'source']))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'source')
                ->where('subClusters.0.key', 'attribution')
            );
    }

    public function test_admin_can_open_supported_sub_cluster_pages_through_generic_view(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $cases = [
            ['clusterKey' => 'traffic', 'subClusterKey' => 'pages'],
            ['clusterKey' => 'traffic', 'subClusterKey' => 'ctas'],
            ['clusterKey' => 'capture', 'subClusterKey' => 'lead_boxes'],
            ['clusterKey' => 'capture', 'subClusterKey' => 'popups'],
            ['clusterKey' => 'flow', 'subClusterKey' => 'funnels'],
            ['clusterKey' => 'behavior', 'subClusterKey' => 'scenarios'],
            ['clusterKey' => 'results', 'subClusterKey' => 'conversions'],
            ['clusterKey' => 'source', 'subClusterKey' => 'attribution'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($admin)
                ->get(route('admin.analytics.sub-clusters.show', $case))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->component('Admin/Analytics/SubClusters/Show')
                    ->where('cluster.key', $case['clusterKey'])
                    ->where('subCluster.key', $case['subClusterKey'])
                );
        }
    }

    public function test_pages_and_ctas_reports_use_selected_date_range(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDays(5)->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 99,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 15,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_CONVERSIONS,
            'metric_value' => 3,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDays(4)->toDateString(),
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_CLICKS,
            'metric_value' => 40,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_IMPRESSIONS,
            'metric_value' => 20,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_CLICKS,
            'metric_value' => 5,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => now()->subDay()->toDateString(),
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_CONVERSIONS,
            'metric_value' => 2,
        ]);

        $range = [
            'from' => now()->subDay()->toDateString(),
            'to' => now()->subDay()->toDateString(),
        ];

        $this->actingAs($admin)
            ->get(route('admin.analytics.pages.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Pages')
                ->where('report.rows.0.views', 15)
                ->where('report.rows.0.conversions', 3)
                ->where('report.rows.0.conversion_rate', 20)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.ctas.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Ctas')
                ->where('report.rows.0.impressions', 20)
                ->where('report.rows.0.clicks', 5)
                ->where('report.rows.0.ctr', 25)
                ->where('report.rows.0.conversion_rate', 40)
            );
    }

    public function test_popups_and_conversions_reports_render_rollup_backed_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $popup = Popup::factory()->create([
            'name' => 'Analytics Popup',
            'slug' => 'analytics-popup',
        ]);

        $leadBox = LeadBox::factory()->active()->create([
            'internal_name' => 'analytics-lead-box',
            'title' => 'Analytics Lead Box',
        ]);

        $date = now()->subDay()->toDateString();

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_IMPRESSIONS,
            'metric_value' => 20,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_OPENS,
            'metric_value' => 10,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_SUBMISSIONS,
            'metric_value' => 4,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_LEAD_BOX,
            'dimension_id' => $leadBox->id,
            'metric_key' => RollupService::METRIC_LEAD_FORM_SUBMISSIONS,
            'metric_value' => 6,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 9,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CONVERSION_TYPE,
            'dimension_id' => 1,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 6,
        ]);

        $range = ['from' => $date, 'to' => $date];

        $this->actingAs($admin)
            ->get(route('admin.analytics.popups.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Popups')
                ->where('report.rows.0.opens', 10)
                ->where('report.rows.0.open_rate', 50)
                ->where('report.rows.0.submission_rate', 40)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.conversions.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Conversions')
                ->where('report.total', 9)
                ->where('report.conversion_types.0.label', 'Lead Form Submission')
                ->where('report.conversion_types.0.total', 6)
            );
    }

    public function test_results_cluster_renders_through_generic_cluster_page_with_conversion_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $date = now()->subDay()->toDateString();

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 10,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CONVERSION_TYPE,
            'dimension_id' => 1,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 6,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CONVERSION_TYPE,
            'dimension_id' => 2,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 4,
        ]);

        $range = ['from' => $date, 'to' => $date];

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', [
                'clusterKey' => 'results',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'results')
                ->where('cluster.label', 'Conversions')
                ->where('subClusters.0.key', 'conversions')
                ->where('subClusters.0.href', route('admin.analytics.sub-clusters.show', ['clusterKey' => 'results', 'subClusterKey' => 'conversions', ...$range]))
                ->where('subClusters.0.flatHref', route('admin.analytics.conversions.index', $range))
                ->where('subClusters.0.metricGroups.0.label', 'Lead Form Submission')
                ->where('subClusters.0.metricGroups.0.detailHref', route('admin.analytics.metric-groups.show', ['clusterKey' => 'results', 'subClusterKey' => 'conversions', 'metricGroupKey' => 'conversion-type-1', ...$range]))
                ->where('subClusters.0.metricGroups.0.metrics.0.label', 'Total Conversions')
                ->where('subClusters.0.metricGroups.0.metrics.0.value', '6')
                ->where('subClusters.0.metricGroups.0.metrics.1.label', 'Share of Conversions')
                ->where('subClusters.0.metricGroups.0.metrics.1.value', '60.00%')
                ->where('subClusters.0.metricGroups.1.label', 'Popup Submission')
                ->where('subClusters.0.metricGroups.1.metrics.0.value', '4')
                ->where('subClusters.0.metricGroups.1.metrics.1.value', '40.00%')
            );
    }

    public function test_source_cluster_renders_through_generic_cluster_page_with_attribution_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $date = '2026-04-21 10:00:00';

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => $date,
            'last_seen_at' => $date,
        ]);

        $conversion = Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'conversion_type_id' => 1,
            'occurred_at' => $date,
        ]);

        ConversionAttribution::query()->create([
            'conversion_id' => $conversion->id,
            'visitor_id' => $visitor->id,
            'attribution_scope' => 'first_touch',
            'source_key' => 'google|cpc|brand',
            'source_label' => 'google / cpc',
            'attribution_method' => 'session_entry_fallback',
            'attribution_confidence' => 0.6,
            'occurred_at' => $date,
        ]);

        ConversionAttribution::query()->create([
            'conversion_id' => $conversion->id,
            'visitor_id' => $visitor->id,
            'attribution_scope' => 'last_touch',
            'source_key' => 'newsletter|email|spring',
            'source_label' => 'newsletter / email',
            'attribution_method' => 'observed_touch',
            'attribution_confidence' => 0.9,
            'occurred_at' => $date,
        ]);

        ConversionAttribution::query()->create([
            'conversion_id' => $conversion->id,
            'visitor_id' => $visitor->id,
            'attribution_scope' => 'conversion_touch',
            'source_key' => 'consultation|cta|hero',
            'source_label' => 'consultation / hero cta',
            'attribution_method' => 'observed_touch',
            'attribution_confidence' => 0.95,
            'occurred_at' => $date,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 2,
        ]);

        $range = [
            'from' => '2026-04-21',
            'to' => '2026-04-21',
        ];

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', [
                'clusterKey' => 'source',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'source')
                ->where('cluster.label', 'Source')
                ->where('subClusters.0.key', 'attribution')
                ->where('subClusters.0.href', route('admin.analytics.sub-clusters.show', ['clusterKey' => 'source', 'subClusterKey' => 'attribution', ...$range]))
                ->where('subClusters.0.flatHref', route('admin.analytics.attribution.index', $range))
                ->where('subClusters.0.metricGroups.0.key', 'first_touch')
                ->where('subClusters.0.metricGroups.0.detailHref', route('admin.analytics.metric-groups.show', ['clusterKey' => 'source', 'subClusterKey' => 'attribution', 'metricGroupKey' => 'first_touch', ...$range]))
                ->where('subClusters.0.metricGroups.0.metrics.0.label', 'Attributed Conversions')
                ->where('subClusters.0.metricGroups.0.metrics.0.value', '1')
                ->where('subClusters.0.metricGroups.0.metrics.1.value', '100.00%')
                ->where('subClusters.0.metricGroups.0.metrics.2.value', '50.00%')
                ->where('subClusters.0.metricGroups.0.metrics.3.value', '1')
                ->where('subClusters.0.metricGroups.1.key', 'last_touch')
                ->where('subClusters.0.metricGroups.1.metrics.0.value', '1')
                ->where('subClusters.0.metricGroups.2.key', 'conversion_touch')
                ->where('subClusters.0.metricGroups.2.metrics.0.value', '1')
            );
    }

    public function test_behavior_cluster_renders_through_generic_cluster_page_with_scenario_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $page = Page::query()->create([
            'page_key' => 'behavior-home',
            'label' => 'Behavior Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $scenarioDefinition = ScenarioDefinition::query()->create([
            'scenario_key' => 'popup_assisted_conversion',
            'label' => 'Popup Assisted Conversion',
            'description' => 'Sessions that converted after popup assistance.',
            'priority' => 100,
            'is_active' => true,
        ]);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2026-04-21 10:00:00',
            'last_seen_at' => '2026-04-21 10:02:00',
        ]);
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2026-04-21 10:00:00',
            'entry_page_id' => $page->id,
        ]);

        SessionScenario::query()->create([
            'session_id' => $session->id,
            'scenario_definition_id' => $scenarioDefinition->id,
            'assignment_type' => SessionScenario::TYPE_PRIMARY,
            'assigned_at' => '2026-04-21 10:00:00',
            'evidence' => ['source' => 'test'],
        ]);

        $this->createTimedEvent($session, 'page.view', ['page_id' => $page->id], '2026-04-21 10:00:00');
        $this->createTimedEvent($session, 'popup.impression', ['page_id' => $page->id], '2026-04-21 10:00:20');
        $this->createTimedEvent($session, 'popup.submitted', ['page_id' => $page->id], '2026-04-21 10:01:00');

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 2,
            'page_id' => $page->id,
            'occurred_at' => '2026-04-21 10:01:30',
        ]);

        $range = [
            'from' => '2026-04-21',
            'to' => '2026-04-21',
        ];

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', [
                'clusterKey' => 'behavior',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'behavior')
                ->where('cluster.label', 'Behavior')
                ->where('subClusters.0.key', 'scenarios')
                ->where('subClusters.0.href', route('admin.analytics.sub-clusters.show', ['clusterKey' => 'behavior', 'subClusterKey' => 'scenarios', ...$range]))
                ->where('subClusters.0.flatHref', route('admin.analytics.scenarios.index', $range))
                ->where('subClusters.0.metricGroups.0.key', 'popup_assisted_conversion')
                ->where('subClusters.0.metricGroups.0.label', 'Popup Assisted Conversion')
                ->where('subClusters.0.metricGroups.0.detailHref', route('admin.analytics.metric-groups.show', ['clusterKey' => 'behavior', 'subClusterKey' => 'scenarios', 'metricGroupKey' => 'popup_assisted_conversion', ...$range]))
                ->where('subClusters.0.metricGroups.0.metrics.0.label', 'Sessions')
                ->where('subClusters.0.metricGroups.0.metrics.0.value', '1')
                ->where('subClusters.0.metricGroups.0.metrics.1.label', 'Converted Sessions')
                ->where('subClusters.0.metricGroups.0.metrics.1.value', '1')
                ->where('subClusters.0.metricGroups.0.metrics.2.label', 'Conversion Total')
                ->where('subClusters.0.metricGroups.0.metrics.2.value', '1')
                ->where('subClusters.0.metricGroups.0.metrics.3.value', '100.00%')
                ->where('subClusters.0.metricGroups.0.metrics.4.value', '3')
                ->where('subClusters.0.metricGroups.0.metrics.5.value', '60')
                ->where('subClusters.0.metricGroups.0.metrics.6.value', '60')
            );
    }

    public function test_flow_cluster_renders_through_generic_cluster_page_with_funnel_metrics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $page = Page::query()->create([
            'page_key' => 'flow-home',
            'label' => 'Flow Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2026-04-21 10:00:00',
            'last_seen_at' => '2026-04-21 10:02:00',
        ]);
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2026-04-21 10:00:00',
            'entry_page_id' => $page->id,
        ]);

        $this->createTimedEvent($session, 'page.view', ['page_id' => $page->id], '2026-04-21 10:00:00');
        $this->createTimedEvent($session, 'cta.click', ['page_id' => $page->id], '2026-04-21 10:00:15');
        $this->createTimedEvent($session, 'lead_form.submitted', ['page_id' => $page->id], '2026-04-21 10:00:45');

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'occurred_at' => '2026-04-21 10:01:00',
        ]);

        $range = [
            'from' => '2026-04-21',
            'to' => '2026-04-21',
        ];

        $this->actingAs($admin)
            ->get(route('admin.analytics.clusters.show', [
                'clusterKey' => 'flow',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->component('Admin/Analytics/Clusters/Show')
                ->where('cluster.key', 'flow')
                ->where('cluster.label', 'Flow')
                ->where('subClusters.0.key', 'funnels')
                ->where('subClusters.0.href', route('admin.analytics.sub-clusters.show', ['clusterKey' => 'flow', 'subClusterKey' => 'funnels', ...$range]))
                ->where('subClusters.0.flatHref', route('admin.analytics.funnels.index', $range))
                ->where('subClusters.0.metricGroups.0.key', 'page_to_cta_to_lead_to_conversion')
                ->where('subClusters.0.metricGroups.0.label', 'Page to CTA to Lead to Conversion')
                ->where('subClusters.0.metricGroups.0.detailHref', route('admin.analytics.metric-groups.show', ['clusterKey' => 'flow', 'subClusterKey' => 'funnels', 'metricGroupKey' => 'page_to_cta_to_lead_to_conversion', ...$range]))
                ->where('subClusters.0.metricGroups.0.metrics.0.label', 'Conversion Count')
                ->where('subClusters.0.metricGroups.0.metrics.0.value', '1')
                ->where('subClusters.0.metricGroups.0.metrics.1.label', 'Completion Rate')
                ->where('subClusters.0.metricGroups.0.metrics.1.value', '100.00%')
                ->where('subClusters.0.metricGroups.0.metrics.2.label', 'Top Drop-Off Loss')
                ->where('subClusters.0.metricGroups.0.metrics.2.value', '—')
                ->where('subClusters.0.metricGroups.0.metrics.3.label', 'Average Elapsed')
                ->where('subClusters.0.metricGroups.0.metrics.3.value', '60')
                ->where('subClusters.0.metricGroups.0.metrics.4.label', 'Step Count')
                ->where('subClusters.0.metricGroups.0.metrics.4.value', '4')
            );
    }

    public function test_dimension_reports_include_event_based_time_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);
        $cta = Cta::query()->create([
            'cta_key' => 'home.hero.consultation',
            'label' => 'Home Hero Consultation',
            'cta_type_id' => 2,
            'intent_key' => 'consultation',
            'is_active' => true,
        ]);
        $leadBox = LeadBox::factory()->active()->create([
            'internal_name' => 'timed-lead-box',
            'title' => 'Timed Lead Box',
        ]);
        $popup = Popup::factory()->create([
            'name' => 'Timed Popup',
            'slug' => 'timed-popup',
        ]);
        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => '2026-04-21 10:00:00',
            'last_seen_at' => '2026-04-21 10:02:00',
        ]);
        $session = Session::query()->create([
            'session_key' => (string) fake()->uuid(),
            'visitor_id' => $visitor->id,
            'started_at' => '2026-04-21 10:00:00',
            'entry_page_id' => $page->id,
        ]);

        $this->createTimedEvent($session, 'page.view', ['page_id' => $page->id], '2026-04-21 10:00:00');
        $this->createTimedEvent($session, 'cta.impression', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-21 10:00:10');
        $this->createTimedEvent($session, 'cta.click', ['page_id' => $page->id, 'cta_id' => $cta->id], '2026-04-21 10:00:30');
        $this->createTimedEvent($session, 'lead_box.impression', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-21 10:00:40');
        $this->createTimedEvent($session, 'lead_box.click', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-21 10:01:00');
        $this->createTimedEvent($session, 'lead_form.submitted', ['page_id' => $page->id, 'lead_box_id' => $leadBox->id], '2026-04-21 10:01:20');
        $this->createTimedEvent($session, 'popup.impression', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-21 10:01:30');
        $this->createTimedEvent($session, 'popup.opened', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-21 10:01:40');
        $this->createTimedEvent($session, 'popup.submitted', ['page_id' => $page->id, 'popup_id' => $popup->id], '2026-04-21 10:02:10');

        Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'session_id' => $session->id,
            'conversion_type_id' => 1,
            'page_id' => $page->id,
            'cta_id' => $cta->id,
            'lead_box_id' => $leadBox->id,
            'popup_id' => $popup->id,
            'occurred_at' => '2026-04-21 10:02:30',
        ]);

        $date = '2026-04-21';

        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_CONVERSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_IMPRESSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_CLICKS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CTA,
            'dimension_id' => $cta->id,
            'metric_key' => RollupService::METRIC_CTA_CONVERSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_LEAD_BOX,
            'dimension_id' => $leadBox->id,
            'metric_key' => RollupService::METRIC_LEAD_BOX_IMPRESSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_LEAD_BOX,
            'dimension_id' => $leadBox->id,
            'metric_key' => RollupService::METRIC_LEAD_BOX_CLICKS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_LEAD_BOX,
            'dimension_id' => $leadBox->id,
            'metric_key' => RollupService::METRIC_LEAD_FORM_SUBMISSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_IMPRESSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_OPENS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_POPUP,
            'dimension_id' => $popup->id,
            'metric_key' => RollupService::METRIC_POPUP_SUBMISSIONS,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 1,
        ]);
        DailyRollup::query()->create([
            'rollup_date' => $date,
            'dimension_type' => RollupService::DIMENSION_CONVERSION_TYPE,
            'dimension_id' => 1,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 1,
        ]);

        $range = ['from' => $date, 'to' => $date];

        $this->actingAs($admin)
            ->get(route('admin.analytics.pages.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->where('report.rows.0.avg_time_to_cta_click_seconds', 30)
                ->where('report.rows.0.avg_time_to_conversion_seconds', 150)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.ctas.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->where('report.rows.0.avg_time_to_click_seconds', 30)
                ->where('report.rows.0.avg_click_to_conversion_seconds', 120)
                ->where('report.rows.0.conversion_touch_conversions', 0)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.lead-boxes.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->where('report.rows.0.avg_impression_to_submit_seconds', 40)
                ->where('report.rows.0.avg_click_to_submit_seconds', 20)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.popups.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->where('report.rows.0.avg_open_to_submit_seconds', 30)
                ->where('report.rows.0.avg_open_to_dismiss_seconds', null)
                ->where('report.rows.0.conversion_touch_conversions', 0)
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.conversions.index', $range))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->where('report.average_time_to_conversion_seconds', 150)
            );
    }

    public function test_attribution_report_renders_snapshot_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $date = '2026-04-21 10:00:00';

        $visitor = Visitor::query()->create([
            'visitor_key' => (string) fake()->uuid(),
            'first_seen_at' => $date,
            'last_seen_at' => $date,
        ]);

        $conversion = Conversion::query()->create([
            'visitor_id' => $visitor->id,
            'conversion_type_id' => 1,
            'occurred_at' => $date,
        ]);

        ConversionAttribution::query()->create([
            'conversion_id' => $conversion->id,
            'visitor_id' => $visitor->id,
            'attribution_scope' => 'last_touch',
            'source_key' => 'newsletter|email|spring',
            'source_label' => 'newsletter / email',
            'attribution_method' => 'observed_touch',
            'attribution_confidence' => 0.9,
            'occurred_at' => $date,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.attribution.index', [
                'from' => '2026-04-21',
                'to' => '2026-04-21',
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Admin/Analytics/Attribution')
                ->where('report.overview.attributed_conversions', 1)
                ->where('report.last_touch.0.source_label', 'newsletter / email')
                ->missing('report.last_touch.0.average_confidence')
            );
    }

    public function test_metric_group_pages_render_real_rows_for_generic_hierarchy_routes(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $page = Page::query()->create([
            'page_key' => 'home',
            'label' => 'Home',
            'category' => 'public',
            'is_active' => true,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_VIEWS,
            'metric_value' => 15,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_PAGE,
            'dimension_id' => $page->id,
            'metric_key' => RollupService::METRIC_PAGE_CONVERSIONS,
            'metric_value' => 3,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_TOTAL,
            'dimension_id' => null,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 9,
        ]);

        DailyRollup::query()->create([
            'rollup_date' => '2026-04-21',
            'dimension_type' => RollupService::DIMENSION_CONVERSION_TYPE,
            'dimension_id' => 1,
            'metric_key' => RollupService::METRIC_CONVERSION_TOTAL,
            'metric_value' => 6,
        ]);

        $range = [
            'from' => '2026-04-21',
            'to' => '2026-04-21',
        ];

        $this->actingAs($admin)
            ->get(route('admin.analytics.metric-groups.show', [
                'clusterKey' => 'traffic',
                'subClusterKey' => 'pages',
                'metricGroupKey' => 'home',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->component('Admin/Analytics/MetricGroups/Show')
                ->where('cluster.key', 'traffic')
                ->where('subCluster.key', 'pages')
                ->where('metricGroup.key', 'home')
                ->where('metricGroup.label', 'Home')
                ->where('metrics.0.label', 'Views')
                ->where('metrics.0.value', '15')
                ->where('metrics.1.label', 'Conversions')
                ->where('metrics.1.value', '3')
                ->where('metrics.2.value', '20.00%')
            );

        $this->actingAs($admin)
            ->get(route('admin.analytics.metric-groups.show', [
                'clusterKey' => 'results',
                'subClusterKey' => 'conversions',
                'metricGroupKey' => 'conversion-type-1',
                ...$range,
            ]))
            ->assertOk()
            ->assertInertia(fn (Assert $pageAssert) => $pageAssert
                ->component('Admin/Analytics/MetricGroups/Show')
                ->where('cluster.key', 'results')
                ->where('subCluster.key', 'conversions')
                ->where('metricGroup.key', 'conversion-type-1')
                ->where('metricGroup.label', 'Lead Form Submission')
                ->where('metrics.0.value', '6')
                ->where('metrics.1.value', '66.67%')
            );
    }

    public function test_invalid_sub_cluster_or_metric_group_routes_return_404(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.analytics.sub-clusters.show', [
                'clusterKey' => 'traffic',
                'subClusterKey' => 'missing',
            ]))
            ->assertNotFound();

        $this->actingAs($admin)
            ->get(route('admin.analytics.metric-groups.show', [
                'clusterKey' => 'traffic',
                'subClusterKey' => 'pages',
                'metricGroupKey' => 'missing',
            ]))
            ->assertNotFound();
    }

    /**
     * @param  array<string, int|null>  $attributes
     */
    private function createTimedEvent(Session $session, string $eventKey, array $attributes, string $occurredAt): Event
    {
        $eventType = EventType::query()->firstOrCreate(
            ['event_key' => $eventKey],
            [
                'label' => $eventKey,
                'category' => 'test',
            ],
        );

        return Event::query()->create([
            'visitor_id' => $session->visitor_id,
            'session_id' => $session->id,
            'event_type_id' => $eventType->id,
            'page_id' => $attributes['page_id'] ?? null,
            'cta_id' => $attributes['cta_id'] ?? null,
            'lead_box_id' => $attributes['lead_box_id'] ?? null,
            'popup_id' => $attributes['popup_id'] ?? null,
            'occurred_at' => $occurredAt,
            'created_at' => $occurredAt,
        ]);
    }
}
