<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\QOItem;
use App\Services\LeadSlots\LeadSlotResolver;
use Inertia\Inertia;
use Inertia\Response;

class MagnetEntryController extends Controller
{
    public function __construct(
        private readonly LeadSlotResolver $leadSlotResolver,
    ) {
    }

    public function quiz(): Response
    {
        $quiz = QOItem::query()
            ->where('type', 'quiz')
            ->where('status', 'published')
            ->orderByDesc('published_at')
            ->orderByDesc('id')
            ->first(['id', 'title', 'slug', 'intro_title', 'intro_body', 'start_button_label']);

        return Inertia::render('Entry/Quiz', [
            'primaryOffer' => [
                'title' => $quiz?->intro_title ?: $quiz?->title ?: 'Take the quick quiz',
                'body' => $quiz?->intro_body ?: 'Answer a few focused questions and get pointed toward the next best step. This page gives the visitor context before they enter the quiz experience.',
                'cta_label' => $quiz?->start_button_label ?: 'Start the quiz',
                'href' => $quiz ? route('qo.show', ['slug' => $quiz->slug]) : null,
                'is_available' => (bool) $quiz,
            ],
            'leadSlots' => $this->leadSlotResolver->resolve('quiz_entry'),
        ]);
    }
}
