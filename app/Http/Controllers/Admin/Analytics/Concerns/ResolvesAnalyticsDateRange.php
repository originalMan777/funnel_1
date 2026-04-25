<?php

namespace App\Http\Controllers\Admin\Analytics\Concerns;

use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

trait ResolvesAnalyticsDateRange
{
    /**
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    protected function resolveRange(Request $request, int $defaultDays = 30): array
    {
        $fallbackTo = CarbonImmutable::today();
        $fallbackFrom = $fallbackTo->subDays(max($defaultDays - 1, 0));

        $to = $this->safeDate($request->string('to')->toString(), $fallbackTo);
        $from = $this->safeDate($request->string('from')->toString(), $fallbackFrom);

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }

    protected function safeDate(?string $value, CarbonImmutable $fallback): CarbonImmutable
    {
        if (! is_string($value) || trim($value) === '') {
            return $fallback;
        }

        try {
            return CarbonImmutable::parse($value)->startOfDay();
        } catch (\Throwable) {
            return $fallback;
        }
    }

    /**
     * @return array{
     *     from:string,
     *     to:string,
     *     presets:array<int, array{label:string, days:int}>
     * }
     */
    protected function analyticsFilters(string $from, string $to): array
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
