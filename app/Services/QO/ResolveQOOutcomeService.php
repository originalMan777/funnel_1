<?php

namespace App\Services\QO;

use App\Models\QOItem;
use App\Models\QOOutcome;
use App\Models\QOSubmission;

class ResolveQOOutcomeService
{
    public function resolve(QOSubmission $submission): array
    {
        $submission->loadMissing([
            'item.outcomes',
            'answers',
        ]);

        $item = $submission->item;

        $finalScore = (int) $submission->answers->sum(fn ($answer) => (int) ($answer->score_value ?? 0));

        $categoryTotals = $submission->answers
            ->filter(fn ($answer) => filled($answer->category_key))
            ->groupBy('category_key')
            ->map(fn ($answers) => $answers->count());

        $finalCategoryKey = $categoryTotals->sortDesc()->keys()->first();

        $outcome = match ($item->result_mode) {
            'category' => $this->resolveByCategory($item, $finalCategoryKey),
            'mixed' => $this->resolveMixed($item, $finalScore, $finalCategoryKey),
            default => $this->resolveByScoreRange($item, $finalScore),
        };

        return [
            'outcome' => $outcome,
            'final_score' => $finalScore,
            'final_category_key' => $finalCategoryKey,
            'final_outcome_key' => $outcome?->outcome_key,
        ];
    }

    protected function resolveByScoreRange(QOItem $item, int $score): ?QOOutcome
    {
        return $item->outcomes
            ->first(function (QOOutcome $outcome) use ($score) {
                $min = $outcome->min_score;
                $max = $outcome->max_score;

                if ($min !== null && $score < $min) {
                    return false;
                }

                if ($max !== null && $score > $max) {
                    return false;
                }

                return $min !== null || $max !== null;
            })
            ?? $item->outcomes->sortBy('sort_order')->first();
    }

    protected function resolveByCategory(QOItem $item, ?string $categoryKey): ?QOOutcome
    {
        if ($categoryKey) {
            $matched = $item->outcomes->first(
                fn (QOOutcome $outcome) => $outcome->category_key === $categoryKey
            );

            if ($matched) {
                return $matched;
            }
        }

        return $item->outcomes->sortBy('sort_order')->first();
    }

    protected function resolveMixed(QOItem $item, int $score, ?string $categoryKey): ?QOOutcome
    {
        return $this->resolveByCategory($item, $categoryKey)
            ?? $this->resolveByScoreRange($item, $score);
    }
}
