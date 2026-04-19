<?php

use App\Services\Campaigns\CampaignRunnerService;
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

Schedule::command('campaigns:run-due')
    ->everyMinute()
    ->withoutOverlapping();
