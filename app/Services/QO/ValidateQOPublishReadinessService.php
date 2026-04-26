<?php

namespace App\Services\QO;

use App\Models\QOItem;

class ValidateQOPublishReadinessService
{
    public function validate(QOItem $item): array
    {
        $item->loadMissing(['questions.options', 'outcomes']);

        $errors = [];
        $warnings = [];

        if ($item->questions->isEmpty()) {
            $errors[] = 'Assessment must have at least one question.';
        }

        foreach ($item->questions as $question) {
            if (blank($question->prompt)) {
                $errors[] = "Question {$question->sort_order} is missing a prompt.";
            }

            if (in_array($question->type, ['single_choice', 'multiple_choice', 'yes_no'], true)) {
                if ($question->options->isEmpty()) {
                    $errors[] = "Question {$question->sort_order} needs answer options.";
                }

                foreach ($question->options as $option) {
                    if (blank($option->label)) {
                        $errors[] = "Question {$question->sort_order} has an option with no label.";
                    }
                }
            }
        }

        if ($item->outcomes->isEmpty()) {
            $errors[] = 'Assessment must have at least one outcome.';
        }

        foreach ($item->outcomes as $outcome) {
            if (blank($outcome->title)) {
                $errors[] = 'An outcome is missing a title.';
            }

            if ($outcome->min_score !== null && $outcome->max_score !== null && $outcome->min_score > $outcome->max_score) {
                $errors[] = "Outcome {$outcome->title} has min score higher than max score.";
            }
        }

        $scoreOutcomes = $item->outcomes
            ->filter(fn ($outcome) => $outcome->min_score !== null || $outcome->max_score !== null)
            ->sortBy('min_score')
            ->values();

        for ($i = 0; $i < $scoreOutcomes->count() - 1; $i++) {
            $current = $scoreOutcomes[$i];
            $next = $scoreOutcomes[$i + 1];

            if ($current->max_score !== null && $next->min_score !== null && $current->max_score >= $next->min_score) {
                $errors[] = "Outcome {$current->title} overlaps with {$next->title}.";
            }

            if ($current->max_score !== null && $next->min_score !== null && ($current->max_score + 1) < $next->min_score) {
                $warnings[] = "There is a score gap between {$current->title} and {$next->title}.";
            }
        }

        if ($item->result_mode === 'category') {
            $hasCategoryOutcome = $item->outcomes->contains(fn ($outcome) => filled($outcome->category_key));

            if (! $hasCategoryOutcome) {
                $errors[] = 'Category result mode needs at least one outcome with a category key.';
            }
        }

        return [
            'is_publishable' => count($errors) === 0,
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }
}
