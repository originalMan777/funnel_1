<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Admin\Analytics\Concerns\ResolvesAnalyticsDateRange;
use App\Http\Controllers\Controller;
use App\Models\Analytics\AttributionTouch;
use App\Models\Analytics\Conversion;
use App\Models\Analytics\ConversionAttribution;
use App\Models\Analytics\DailyRollup;
use App\Models\Analytics\Event;
use App\Models\Analytics\EventType;
use App\Models\Analytics\Session;
use App\Models\Analytics\Visitor;
use App\Services\Analytics\AnalyticsBootstrapService;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsInterpretationService;
use App\Services\Analytics\AnalyticsNarrativeService;
use App\Services\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class OverviewController extends Controller
{
    use ResolvesAnalyticsDateRange;

    public function __construct(
        private readonly AnalyticsBootstrapService $analyticsBootstrapService,
        private readonly AnalyticsReportService $analyticsReportService,
        private readonly AnalyticsFunnelService $analyticsFunnelService,
        private readonly AnalyticsInterpretationService $analyticsInterpretationService,
        private readonly AnalyticsNarrativeService $analyticsNarrativeService,
    ) {}

    public function index(Request $request): Response
    {
        $ready = $this->tablesReady();
        [$from, $to] = $this->resolveRange($request, defaultDays: 30);
        $hasRollups = $ready && DailyRollup::query()->exists();
        $conversionSummary = $hasRollups ? $this->analyticsReportService->conversionSummary($from, $to) : ['by_type' => collect()];
        $scenarioSummary = $hasRollups ? $this->analyticsReportService->scenarioPerformance($from, $to) : collect();
        $attributionSummary = $ready ? $this->analyticsReportService->attributionSummary($from, $to) : ['overview' => ['attributed_conversions' => 0, 'unattributed_conversions' => 0], 'last_touch' => collect()];
        $funnels = $ready ? $this->analyticsFunnelService->analyze($from, $to) : collect();
        $interpretations = $ready ? $this->analyticsInterpretationService->summarize($from, $to) : collect();
        $summaryCards = $hasRollups
            ? $this->analyticsReportService->overviewSummary($from, $to)
            : [
                'page_views' => 0,
                'cta_clicks' => 0,
                'lead_form_submissions' => 0,
                'popup_submissions' => 0,
                'conversions' => 0,
                'average_session_duration_seconds' => null,
                'median_session_duration_seconds' => null,
                'average_time_to_conversion_seconds' => null,
                'median_time_to_conversion_seconds' => null,
            ];

        return Inertia::render('Admin/Analytics/Overview', [
            'filters' => $this->analyticsFilters($from->toDateString(), $to->toDateString()),
            'readiness' => [
                'enabled' => (bool) config('analytics.enabled'),
                'tables_ready' => $ready,
                'ingest_route' => route('analytics.ingest', absolute: false),
                'session_inactivity_timeout_minutes' => (int) config('analytics.session.inactivity_timeout_minutes', 30),
                'bootstrap_ready' => $this->analyticsBootstrapService->isReady(),
            ],
            'summary' => [
                'visitors' => $ready ? Visitor::query()->count() : 0,
                'sessions' => $ready ? Session::query()->count() : 0,
                'event_types' => $ready ? EventType::query()->count() : 0,
                'events' => $ready ? Event::query()->count() : 0,
                'attribution_touches' => $ready ? AttributionTouch::query()->count() : 0,
                'conversions' => $ready ? Conversion::query()->count() : 0,
                'conversion_attributions' => $ready && Schema::hasTable('analytics_conversion_attributions')
                    ? ConversionAttribution::query()->count()
                    : 0,
                'daily_rollups' => $ready ? DailyRollup::query()->count() : 0,
            ],
            'overview' => [
                'range' => [
                    'from' => $from->toDateString(),
                    'to' => $to->toDateString(),
                ],
                'ready' => $hasRollups,
                'summary_cards' => $summaryCards,
                'trend' => $hasRollups
                    ? $this->analyticsReportService->overviewTrend($from, $to)->values()
                    : [],
                'conversion_types' => $hasRollups
                    ? $conversionSummary['by_type']->values()
                    : [],
                'top_scenarios' => $scenarioSummary->take(5)->values(),
                'top_funnels' => $funnels->take(3)->values(),
                'attribution' => [
                    'overview' => $attributionSummary['overview'],
                ],
                'interpretations' => $interpretations->values(),
            ],
            'overviewReport' => $this->analyticsNarrativeService->overviewReport($summaryCards),
        ]);
    }

    private function tablesReady(): bool
    {
        return Schema::hasTable('analytics_visitors')
            && Schema::hasTable('analytics_sessions')
            && Schema::hasTable('analytics_event_types')
            && Schema::hasTable('analytics_events')
            && Schema::hasTable('analytics_attribution_touches')
            && Schema::hasTable('analytics_conversions')
            && Schema::hasTable('analytics_daily_rollups')
            && Schema::hasTable('analytics_scenario_definitions')
            && Schema::hasTable('analytics_session_scenarios')
            && Schema::hasTable('analytics_conversion_attributions');
    }
}
