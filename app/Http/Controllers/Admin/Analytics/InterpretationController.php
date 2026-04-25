<?php

namespace App\Http\Controllers\Admin\Analytics;

use App\Http\Controllers\Admin\Analytics\Concerns\ResolvesAnalyticsDateRange;
use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsFunnelService;
use App\Services\Analytics\AnalyticsReportService;
use App\Services\Analytics\AnalyticsSessionJourneyService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InterpretationController extends Controller
{
    use ResolvesAnalyticsDateRange;

    public function __construct(
        private readonly AnalyticsFunnelService $analyticsFunnelService,
        private readonly AnalyticsReportService $analyticsReportService,
        private readonly AnalyticsSessionJourneyService $analyticsSessionJourneyService,
    ) {}

    public function funnels(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);

        return Inertia::render('Admin/Analytics/Funnels', [
            'filters' => $this->filters($from->toDateString(), $to->toDateString()),
            'report' => [
                'funnels' => $this->analyticsFunnelService->analyze($from, $to)->values(),
            ],
        ]);
    }

    public function scenarios(Request $request): Response
    {
        [$from, $to] = $this->resolveRange($request);
        $scenarioRows = $this->analyticsReportService->scenarioPerformance($from, $to)->values();
        $selectedScenario = $request->string('scenario')->toString();

        if ($selectedScenario === '' && $scenarioRows->isNotEmpty()) {
            $selectedScenario = (string) $scenarioRows->first()['scenario_key'];
        }

        return Inertia::render('Admin/Analytics/Scenarios', [
            'filters' => $this->filters($from->toDateString(), $to->toDateString()),
            'report' => [
                'selected_scenario' => $selectedScenario !== '' ? $selectedScenario : null,
                'rows' => $scenarioRows,
                'secondary_rows' => $this->analyticsReportService->secondaryScenarioPerformance($from, $to)->values(),
                'sample_sessions' => $selectedScenario !== ''
                    ? $this->analyticsSessionJourneyService->recentSessions($from, $to, $selectedScenario, 8)->values()
                    : [],
            ],
        ]);
    }

    /**
     * @return array{
     *     from:string,
     *     to:string,
     *     presets:array<int, array{label:string, days:int}>
     * }
     */
    private function filters(string $from, string $to): array
    {
        return [
            'from' => $from,
            'to' => $to,
            'presets' => [
                ['label' => 'Last 7 days', 'days' => 7],
                ['label' => 'Last 30 days', 'days' => 30],
                ['label' => 'Last 90 days', 'days' => 90],
            ],
        ];
    }
}
