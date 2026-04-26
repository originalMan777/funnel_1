<?php

namespace App\Services\QO;

use App\Models\QOSubmission;

class CompleteQOSubmissionService
{
    public function complete(QOSubmission $submission): array
    {
        $submission->loadMissing(['item', 'answers']);

        $result = app(ResolveQOOutcomeService::class)->resolve($submission);

        $submission->update([
            'final_score' => $result['final_score'],
            'final_category_key' => $result['final_category_key'],
            'final_outcome_key' => $result['final_outcome_key'],
            'outcome_snapshot_json' => $this->snapshot($result['outcome']),
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        return [
            'submission' => $submission->fresh(),
            'outcome' => $result['outcome'],
            'final_score' => $result['final_score'],
            'final_category_key' => $result['final_category_key'],
            'final_outcome_key' => $result['final_outcome_key'],
        ];
    }

    protected function snapshot($outcome): ?array
    {
        if (!$outcome) {
            return null;
        }

        return [
            'id' => $outcome->id,
            'outcome_key' => $outcome->outcome_key,
            'title' => $outcome->title,
            'summary' => $outcome->summary,
            'body' => $outcome->body,
            'cta_label' => $outcome->cta_label,
            'cta_url' => $outcome->cta_url,
        ];
    }
}
