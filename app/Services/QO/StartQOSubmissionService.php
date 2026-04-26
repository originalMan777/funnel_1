<?php

namespace App\Services\QO;

use App\Models\QOItem;
use App\Models\QOSubmission;
use Illuminate\Support\Str;

class StartQOSubmissionService
{
    public function start(QOItem $item, array $context = []): array
    {
        $firstQuestion = $item->questions()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();

        $submission = $item->submissions()->create([
            'session_uuid' => $context['session_uuid'] ?? (string) Str::uuid(),
            'status' => 'started',
            'current_question_id' => $firstQuestion?->id,
            'source_url' => $context['source_url'] ?? null,
            'referrer_url' => $context['referrer_url'] ?? null,
            'utm_json' => $context['utm_json'] ?? null,
            'started_at' => now(),
        ]);

        return [
            'submission' => $submission,
            'current_question' => $firstQuestion,
            'state' => $this->determineInitialState($item),
            'requires_capture' => $item->capture_mode === 'before_start',
        ];
    }

    protected function determineInitialState(QOItem $item): string
    {
        if ($item->capture_mode === 'before_start') {
            return 'capture_before_start';
        }

        return 'question';
    }
}
