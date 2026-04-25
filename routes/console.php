<?php

use App\Services\Analytics\AnalyticsAttributionService;
use App\Services\Analytics\AnalyticsRetentionService;
use App\Services\Analytics\AnalyticsScenarioService;
use App\Services\Analytics\RollupService;
use App\Services\Campaigns\CampaignRunnerService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('campaigns:run-due', function (CampaignRunnerService $runner) {
    $processed = $runner->runDue();

    $this->info("Processed {$processed} due campaign enrollments.");
})->purpose('Run due campaign enrollments');

Artisan::command('analytics:rollups {--date=} {--from=} {--to=}', function (RollupService $rollups) {
    $singleDate = $this->option('date');
    $from = $this->option('from');
    $to = $this->option('to');

    if ($singleDate && ($from || $to)) {
        $this->error('Use either --date or --from/--to.');

        return self::FAILURE;
    }

    if (($from && ! $to) || (! $from && $to)) {
        $this->error('Both --from and --to are required for a backfill.');

        return self::FAILURE;
    }

    try {
        if ($singleDate) {
            $date = CarbonImmutable::parse($singleDate)->startOfDay();
            $count = $rollups->generateForDate($date)->count();

            $this->info("Generated {$count} analytics rollups for {$date->toDateString()}.");

            return self::SUCCESS;
        }

        if ($from && $to) {
            $start = CarbonImmutable::parse($from)->startOfDay();
            $end = CarbonImmutable::parse($to)->startOfDay();

            if ($start->gt($end)) {
                $this->error('The --from date must be on or before --to.');

                return self::FAILURE;
            }

            $results = $rollups->backfill($start, $end);
            $count = $results->sum(fn ($day) => $day->count());

            $this->info("Generated {$count} analytics rollups from {$start->toDateString()} to {$end->toDateString()}.");

            return self::SUCCESS;
        }

        $date = CarbonImmutable::yesterday();
        $count = $rollups->generateForDate($date)->count();

        $this->info("Generated {$count} analytics rollups for {$date->toDateString()}.");

        return self::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }
})->purpose('Generate standalone analytics daily rollups for a date or inclusive date range');

Artisan::command('analytics:scenarios {--date=} {--from=} {--to=}', function (AnalyticsScenarioService $scenarios) {
    $singleDate = $this->option('date');
    $from = $this->option('from');
    $to = $this->option('to');

    if ($singleDate && ($from || $to)) {
        $this->error('Use either --date or --from/--to.');

        return self::FAILURE;
    }

    if (($from && ! $to) || (! $from && $to)) {
        $this->error('Both --from and --to are required for a backfill.');

        return self::FAILURE;
    }

    try {
        if ($singleDate) {
            $date = CarbonImmutable::parse($singleDate)->startOfDay();
            $count = $scenarios->assignRange($date, $date)->count();

            $this->info("Assigned {$count} analytics session scenarios for {$date->toDateString()}.");

            return self::SUCCESS;
        }

        if ($from && $to) {
            $start = CarbonImmutable::parse($from)->startOfDay();
            $end = CarbonImmutable::parse($to)->startOfDay();

            if ($start->gt($end)) {
                $this->error('The --from date must be on or before --to.');

                return self::FAILURE;
            }

            $count = $scenarios->assignRange($start, $end)->count();

            $this->info("Assigned {$count} analytics session scenarios from {$start->toDateString()} to {$end->toDateString()}.");

            return self::SUCCESS;
        }

        $date = CarbonImmutable::yesterday();
        $count = $scenarios->assignRange($date, $date)->count();

        $this->info("Assigned {$count} analytics session scenarios for {$date->toDateString()}.");

        return self::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }
})->purpose('Assign standalone analytics session scenarios for a date or inclusive date range');

Artisan::command('analytics:attribution {--date=} {--from=} {--to=}', function (AnalyticsAttributionService $attribution) {
    $singleDate = $this->option('date');
    $from = $this->option('from');
    $to = $this->option('to');

    if ($singleDate && ($from || $to)) {
        $this->error('Use either --date or --from/--to.');

        return self::FAILURE;
    }

    if (($from && ! $to) || (! $from && $to)) {
        $this->error('Both --from and --to are required for a backfill.');

        return self::FAILURE;
    }

    try {
        if ($singleDate) {
            $date = CarbonImmutable::parse($singleDate)->startOfDay();
            $count = $attribution->syncRange($date, $date)->count();

            $this->info("Synced {$count} analytics conversion attributions for {$date->toDateString()}.");

            return self::SUCCESS;
        }

        if ($from && $to) {
            $start = CarbonImmutable::parse($from)->startOfDay();
            $end = CarbonImmutable::parse($to)->startOfDay();

            if ($start->gt($end)) {
                $this->error('The --from date must be on or before --to.');

                return self::FAILURE;
            }

            $count = $attribution->syncRange($start, $end)->count();

            $this->info("Synced {$count} analytics conversion attributions from {$start->toDateString()} to {$end->toDateString()}.");

            return self::SUCCESS;
        }

        $date = CarbonImmutable::yesterday();
        $count = $attribution->syncRange($date, $date)->count();

        $this->info("Synced {$count} analytics conversion attributions for {$date->toDateString()}.");

        return self::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }
})->purpose('Sync standalone analytics conversion attribution snapshots for a date or inclusive date range');

Artisan::command('analytics:retention:plan {--date=}', function (AnalyticsRetentionService $retention) {
    $asOf = $this->option('date')
        ? CarbonImmutable::parse($this->option('date'))->startOfDay()
        : CarbonImmutable::now()->startOfDay();

    try {
        $plan = $retention->plan($asOf);

        $this->info("Analytics retention plan as of {$plan['as_of']}:");
        $this->line(sprintf(
            'Eligible raw data: %d sessions, %d events, %d touches.',
            $plan['eligible_counts']['raw_sessions'],
            $plan['eligible_counts']['raw_events'],
            $plan['eligible_counts']['raw_touches'],
        ));
        $this->line(sprintf(
            'Preserved layers: rollups=%s, conversions=%s, scenarios=%s, attributions=%s.',
            $plan['preservation_targets']['keep_rollups'] ? 'yes' : 'no',
            $plan['preservation_targets']['keep_conversions'] ? 'yes' : 'no',
            $plan['preservation_targets']['keep_session_scenarios'] ? 'yes' : 'no',
            $plan['preservation_targets']['keep_conversion_attributions'] ? 'yes' : 'no',
        ));

        return self::SUCCESS;
    } catch (Throwable $exception) {
        $this->error($exception->getMessage());

        return self::FAILURE;
    }
})->purpose('Preview standalone analytics retention readiness without deleting data');

Schedule::command('campaigns:run-due')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('analytics:rollups')
    ->dailyAt('01:15')
    ->withoutOverlapping();

Schedule::command('analytics:scenarios')
    ->dailyAt('01:25')
    ->withoutOverlapping();

Schedule::command('analytics:attribution')
    ->dailyAt('01:35')
    ->withoutOverlapping();
