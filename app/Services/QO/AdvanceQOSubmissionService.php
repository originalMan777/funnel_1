<?php

namespace App\Services\QO;

use App\Models\QOOption;
use App\Models\QOQuestion;
use App\Models\QOSubmission;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class AdvanceQOSubmissionService
{
    public function advance(QOSubmission $submission, array $payload): array
    {
        $submission->loadMissing(['item', 'currentQuestion', 'answers']);

        if ($submission->status === 'completed') {
            throw ValidationException::withMessages([
                'submission' => 'This submission is already completed.',
            ]);
        }

        $question = $this->resolveQuestion($submission, $payload);

        $options = $this->resolveOptions($question, $payload);

        $attemptNumber = $this->nextAttemptNumber($submission, $question);

        $answers = $this->storeAnswers($submission, $question, $options, $payload, $attemptNumber);

        $feedback = $this->buildFeedback($submission, $question, $options, $attemptNumber);

        if ($feedback['can_retry']) {
            foreach ($answers as $answer) {
                $answer->update(['is_final_attempt' => false]);
            }

            return [
                'submission' => $submission->fresh(),
                'answers' => $answers,
                'answer' => $answers->first(),
                'state' => 'feedback',
                'question_resolved' => false,
                'current_question' => $question,
                'next_question' => null,
                'feedback' => $feedback,
                'is_complete_ready' => false,
            ];
        }

        $nextQuestion = $this->nextQuestion($submission, $question);

        $submission->update([
            'current_question_id' => $nextQuestion?->id,
        ]);

        return [
            'submission' => $submission->fresh(),
            'answers' => $answers,
            'answer' => $answers->first(),
            'state' => $nextQuestion ? 'question' : 'ready_to_complete',
            'question_resolved' => true,
            'current_question' => $nextQuestion,
            'next_question' => $nextQuestion,
            'feedback' => $feedback,
            'is_complete_ready' => $nextQuestion === null,
        ];
    }

    protected function resolveQuestion(QOSubmission $submission, array $payload): QOQuestion
    {
        $questionId = $payload['qo_question_id'] ?? $submission->current_question_id;

        $question = $submission->item
            ->questions()
            ->where('id', $questionId)
            ->first();

        if (! $question) {
            throw ValidationException::withMessages([
                'qo_question_id' => 'Invalid question for this submission.',
            ]);
        }

        return $question;
    }

    protected function resolveOptions(QOQuestion $question, array $payload): Collection
    {
        if ($question->type === 'multiple_choice') {
            $optionIds = collect($payload['qo_option_ids'] ?? [])
                ->filter()
                ->values();

            if ($optionIds->isEmpty()) {
                return collect();
            }

            $options = $question->options()
                ->whereIn('id', $optionIds)
                ->get();

            if ($options->count() !== $optionIds->count()) {
                throw ValidationException::withMessages([
                    'qo_option_ids' => 'One or more selected options are invalid for this question.',
                ]);
            }

            return $options;
        }

        if (! isset($payload['qo_option_id'])) {
            return collect();
        }

        $option = $question
            ->options()
            ->where('id', $payload['qo_option_id'])
            ->first();

        if (! $option) {
            throw ValidationException::withMessages([
                'qo_option_id' => 'Invalid option for this question.',
            ]);
        }

        return collect([$option]);
    }

    protected function storeAnswers(
        QOSubmission $submission,
        QOQuestion $question,
        Collection $options,
        array $payload,
        int $attemptNumber
    ): Collection {
        if ($question->type === 'multiple_choice') {
            return $options->map(function (QOOption $option) use ($submission, $question, $attemptNumber) {
                return $submission->answers()->create([
                    'qo_question_id' => $question->id,
                    'qo_option_id' => $option->id,
                    'score_value' => $option->score_value,
                    'category_key' => $option->category_key,
                    'outcome_key' => $option->outcome_key,
                    'is_correct' => $option->is_correct,
                    'attempt_number' => $attemptNumber,
                    'is_final_attempt' => true,
                ]);
            });
        }

        $option = $options->first();

        return collect([
            $submission->answers()->create([
                'qo_question_id' => $question->id,
                'qo_option_id' => $option?->id,
                'answer_text' => $payload['answer_text'] ?? null,
                'answer_number' => $payload['answer_number'] ?? null,
                'answer_json' => $payload['answer_json'] ?? null,
                'score_value' => $option?->score_value,
                'category_key' => $option?->category_key,
                'outcome_key' => $option?->outcome_key,
                'is_correct' => $option?->is_correct,
                'attempt_number' => $attemptNumber,
                'is_final_attempt' => true,
            ]),
        ]);
    }

    protected function nextAttemptNumber(QOSubmission $submission, QOQuestion $question): int
    {
        return ((int) $submission->answers()
            ->where('qo_question_id', $question->id)
            ->max('attempt_number')) + 1;
    }

    protected function buildFeedback(
        QOSubmission $submission,
        QOQuestion $question,
        Collection $options,
        int $attemptNumber
    ): array {
        $item = $submission->item;

        $showFeedback = $question->show_correctness_feedback_override
            ?? $item->show_correctness_feedback;

        $allowSecondChance = $question->allow_second_chance_override
            ?? $item->allow_second_chance;

        $maxAttempts = $question->max_attempts_override
            ?? $item->max_attempts_per_question
            ?? 1;

        $isCorrectnessMode = $item->interaction_mode === 'correctness';

        $isCorrect = null;

        if ($options->isNotEmpty()) {
            $isCorrect = $question->type === 'multiple_choice'
                ? $options->every(fn (QOOption $option) => (bool) $option->is_correct)
                : (bool) $options->first()?->is_correct;
        }

        $canRetry = $isCorrectnessMode
            && $allowSecondChance
            && $isCorrect === false
            && $attemptNumber < $maxAttempts;

        $message = null;

        if ($isCorrectnessMode && $showFeedback) {
            $message = $isCorrect
                ? 'Correct.'
                : 'Not quite.';
        }

        return [
            'visible' => (bool) ($isCorrectnessMode && $showFeedback),
            'is_correct' => $isCorrect,
            'message' => $message,
            'can_retry' => $canRetry,
            'attempts_used' => $attemptNumber,
            'max_attempts' => (int) $maxAttempts,
        ];
    }

    protected function nextQuestion(QOSubmission $submission, QOQuestion $currentQuestion): ?QOQuestion
    {
        return $submission->item
            ->questions()
            ->where(function ($query) use ($currentQuestion) {
                $query
                    ->where('sort_order', '>', $currentQuestion->sort_order)
                    ->orWhere(function ($query) use ($currentQuestion) {
                        $query
                            ->where('sort_order', $currentQuestion->sort_order)
                            ->where('id', '>', $currentQuestion->id);
                    });
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->first();
    }
}
