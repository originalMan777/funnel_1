<?php

namespace App\Http\Requests\ContentFormula;

use App\Services\ContentFormula\ContentFormulaEventLogger;
use App\Services\ContentFormula\ContentFormulaRules;
use App\Services\ContentFormula\ContentFormulaSessionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class GenerateContentFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $contentFormulaRules = app(ContentFormulaRules::class);
        $wordMin = (int) config('content_formula.generator.word_range.min', 0);
        $wordMax = (int) config('content_formula.generator.word_range.max', 2000);
        $maxSelectionsPerGroup = $contentFormulaRules->maxSelectionsPerGroup();

        return [
            'action' => ['nullable', 'string', 'in:generate,continue,reset'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'result_count' => ['nullable', 'integer', Rule::in($contentFormulaRules->allowedResultCounts())],
            'min_words' => ['nullable', 'integer', 'min:' . $wordMin, 'max:' . $wordMax],
            'max_words' => ['nullable', 'integer', 'min:' . $wordMin, 'max:' . $wordMax],
            'groups' => ['required', 'array'],

            'groups.topics' => ['required', 'array', 'min:1', 'max:' . $maxSelectionsPerGroup],
            'groups.article_types' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],
            'groups.article_formats' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],
            'groups.vibes' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],

            'groups.reader_impacts' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],
            'groups.audiences' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],
            'groups.contexts' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],
            'groups.perspectives' => ['nullable', 'array', 'max:' . $maxSelectionsPerGroup],

            'groups.topics.*.label' => ['required', 'string', 'max:255'],
            'groups.article_types.*.label' => ['required', 'string', 'max:255'],
            'groups.article_formats.*.label' => ['required', 'string', 'max:255'],
            'groups.vibes.*.label' => ['required', 'string', 'max:255'],

            'groups.reader_impacts.*.label' => ['nullable', 'string', 'max:255'],
            'groups.audiences.*.label' => ['nullable', 'string', 'max:255'],
            'groups.contexts.*.label' => ['nullable', 'string', 'max:255'],
            'groups.perspectives.*.label' => ['nullable', 'string', 'max:255'],

            'groups.topics.*.stars' => ['required', 'integer', 'min:1', 'max:3'],
            'groups.article_types.*.stars' => ['required', 'integer', 'min:1', 'max:3'],
            'groups.article_formats.*.stars' => ['required', 'integer', 'min:1', 'max:3'],
            'groups.vibes.*.stars' => ['required', 'integer', 'min:1', 'max:3'],

            'groups.reader_impacts.*.stars' => ['nullable', 'integer', 'min:1', 'max:3'],
            'groups.audiences.*.stars' => ['nullable', 'integer', 'min:1', 'max:3'],
            'groups.contexts.*.stars' => ['nullable', 'integer', 'min:1', 'max:3'],
            'groups.perspectives.*.stars' => ['nullable', 'integer', 'min:1', 'max:3'],

            'extra_direction' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'result_count.in' => 'Please choose 25, 50, 100, or 150 variations.',
            'groups.required' => 'The content formula groups are required.',
            'groups.topics.required' => 'Please select at least one topic.',
            'groups.topics.min' => 'Please select at least one topic.',
            'groups.topics.max' => 'You can select up to 20 topics.',
            'groups.article_types.max' => 'You can select up to 20 article types.',
            'groups.article_formats.max' => 'You can select up to 20 formats.',
            'groups.vibes.max' => 'You can select up to 20 vibes.',
            'groups.reader_impacts.max' => 'You can select up to 20 reader impacts.',
            'groups.audiences.max' => 'You can select up to 20 audiences.',
            'groups.contexts.max' => 'You can select up to 20 contexts.',
            'groups.perspectives.max' => 'You can select up to 20 perspectives.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $contentFormulaRules = app(ContentFormulaRules::class);
            $events = app(ContentFormulaEventLogger::class);
            $groups = $this->input('groups', []);
            $requiredGroups = (array) config('content_formula.generator.required_groups', []);
            $action = $this->action();
            $sessionId = trim((string) $this->input('session_id', ''));
            $thresholdRequired = $contentFormulaRules->minimumUnlockCombinations();
            $loggedEvents = [];
            $loggedFailureEvents = [];

            $minWords = $this->input('min_words', config('content_formula.generator.word_range.default_min', 800));
            $maxWords = $this->input('max_words', config('content_formula.generator.word_range.default_max', 1400));

            $logWarning = function (string $event, array $context = []) use ($events, &$loggedEvents, $action, $sessionId, $groups, $minWords, $maxWords): void {
                if (in_array($event, $loggedEvents, true)) {
                    return;
                }

                $loggedEvents[] = $event;

                $events->warning($event, $this, array_merge([
                    'action_type' => $action,
                    'session_id' => $sessionId !== '' ? $sessionId : null,
                    'requested_result_count' => $this->filled('result_count') ? (int) $this->input('result_count') : null,
                    'word_min' => is_numeric((string) $minWords) ? (int) $minWords : null,
                    'word_max' => is_numeric((string) $maxWords) ? (int) $maxWords : null,
                    'selected_group_counts' => $events->selectedGroupCounts($groups),
                ], $context));
            };

            $logError = function (string $event, array $context = []) use ($events, &$loggedFailureEvents, $action, $sessionId, $groups, $minWords, $maxWords): void {
                if (in_array($event, $loggedFailureEvents, true)) {
                    return;
                }

                $loggedFailureEvents[] = $event;

                $events->error($event, $this, array_merge([
                    'action_type' => $action,
                    'session_id' => $sessionId !== '' ? $sessionId : null,
                    'requested_result_count' => $this->filled('result_count') ? (int) $this->input('result_count') : null,
                    'word_min' => is_numeric((string) $minWords) ? (int) $minWords : null,
                    'word_max' => is_numeric((string) $maxWords) ? (int) $maxWords : null,
                    'selected_group_counts' => $events->selectedGroupCounts($groups),
                    'generator_stage' => 'validate',
                    'exception_class' => null,
                    'safe_exception_message' => null,
                ], $context));
            };

            if (!is_array($groups)) {
                $logWarning('generator_rejected_malformed_payload', [
                    'validation_field' => 'groups',
                    'rejection_reason' => 'groups_must_be_array',
                ]);
                $logError('generator_failure_unexpected_payload_shape', [
                    'validation_field' => 'groups',
                    'rejection_reason' => 'groups_must_be_array',
                ]);

                return;
            }

            if ((int) $minWords > (int) $maxWords) {
                $validator->errors()->add('min_words', 'Minimum words cannot be greater than maximum words.');
                $logWarning('generator_rejected_invalid_word_range', [
                    'validation_field' => 'min_words',
                    'rejection_reason' => 'min_words_greater_than_max_words',
                ]);
            }

            if (in_array($action, ['continue', 'reset'], true) && $sessionId === '') {
                $validator->errors()->add('session_id', 'A valid generation session is required for this action.');
                $logWarning('generator_rejected_missing_session', [
                    'validation_field' => 'session_id',
                    'rejection_reason' => 'missing_session_id',
                ]);
            }

            if ($sessionId !== '' && in_array($action, ['continue', 'reset'], true)) {
                $sessions = app(ContentFormulaSessionService::class);

                if (!$sessions->exists($sessionId)) {
                    $validator->errors()->add('session_id', 'The selected generation session is no longer available. Start a fresh generation.');
                    $logWarning('generator_rejected_stale_session', [
                        'validation_field' => 'session_id',
                        'rejection_reason' => 'stale_session_id',
                    ]);
                }
            }

            foreach ($requiredGroups as $groupKey) {
                $items = $groups[$groupKey] ?? [];

                if (!is_array($items) || count($items) < 1) {
                    $validator->errors()->add("groups.{$groupKey}", "Please select at least one option for {$groupKey}.");
                    $logWarning('generator_rejected_missing_required_selection', [
                        'validation_field' => "groups.{$groupKey}",
                        'rejection_reason' => 'missing_required_selection',
                    ]);

                    if (!is_array($items)) {
                        $logError('generator_failure_unexpected_payload_shape', [
                            'validation_field' => "groups.{$groupKey}",
                            'rejection_reason' => 'required_group_must_be_array',
                        ]);
                    }

                    continue;
                }

                foreach ($items as $index => $item) {
                    if (!is_array($item)) {
                        $logWarning('generator_rejected_malformed_payload', [
                            'validation_field' => "groups.{$groupKey}.{$index}",
                            'rejection_reason' => 'group_item_must_be_object',
                        ]);
                    }

                    $this->validateGroupItem($validator, $groupKey, $index, $item, true);
                }
            }

            foreach (['reader_impacts', 'audiences', 'contexts', 'perspectives'] as $optionalGroup) {
                $items = $groups[$optionalGroup] ?? [];

                if (!is_array($items)) {
                    if ($items !== null) {
                        $logWarning('generator_rejected_malformed_payload', [
                            'validation_field' => "groups.{$optionalGroup}",
                            'rejection_reason' => 'optional_group_must_be_array',
                        ]);
                        $logError('generator_failure_unexpected_payload_shape', [
                            'validation_field' => "groups.{$optionalGroup}",
                            'rejection_reason' => 'optional_group_must_be_array',
                        ]);
                    }

                    continue;
                }

                foreach ($items as $index => $item) {
                    if (!is_array($item)) {
                        $logWarning('generator_rejected_malformed_payload', [
                            'validation_field' => "groups.{$optionalGroup}.{$index}",
                            'rejection_reason' => 'group_item_must_be_object',
                        ]);
                    }

                    $this->validateGroupItem($validator, $optionalGroup, $index, $item, false);
                }
            }

            $knownGroupKeys = $contentFormulaRules->trackedCombinationGroups();
            $combinationGroups = collect($knownGroupKeys)
                ->mapWithKeys(fn (string $groupKey) => [$groupKey => $this->groupItems($groups, $groupKey)])
                ->all();

            $activeGroupCount = collect($knownGroupKeys)
                ->filter(fn (string $groupKey) => count($combinationGroups[$groupKey]) > 0)
                ->count();

            if ($activeGroupCount > $contentFormulaRules->maxActiveGroups()) {
                $validator->errors()->add('groups', 'You can use up to 10 categories at a time.');
                $logWarning('generator_rejected_selection_limit', [
                    'validation_field' => 'groups',
                    'rejection_reason' => 'active_group_limit_exceeded',
                ]);
            }

            $combinationCount = $contentFormulaRules->combinationCount($combinationGroups, $knownGroupKeys);

            if (in_array($action, ['generate', 'reset'], true)
                && !$contentFormulaRules->meetsUnlockThreshold($combinationGroups, $knownGroupKeys)) {
                $validator->errors()->add('groups', 'Select fields. Reach 1,000 combinations to proceed.');
                $logWarning('generator_rejected_threshold_not_met', [
                    'validation_field' => 'groups',
                    'rejection_reason' => 'minimum_combination_threshold_not_met',
                    'combination_count' => $combinationCount,
                    'threshold_required' => $thresholdRequired,
                ]);
            }

            if ($validator->errors()->has('result_count')) {
                $logWarning('generator_rejected_invalid_result_count', [
                    'validation_field' => 'result_count',
                    'rejection_reason' => 'result_count_not_allowed',
                ]);
            }

            if ($validator->errors()->has('min_words') || $validator->errors()->has('max_words')) {
                $logWarning('generator_rejected_invalid_word_range', [
                    'validation_field' => $validator->errors()->has('min_words') ? 'min_words' : 'max_words',
                    'rejection_reason' => 'word_range_invalid',
                ]);
            }
        });
    }

    public function normalized(): array
    {
        $groups = $this->input('groups', []);
        $wordConfig = (array) config('content_formula.generator.word_range', []);

        $minWords = $this->clampWordValue(
            (int) $this->input('min_words', $wordConfig['default_min'] ?? 800),
            (int) ($wordConfig['min'] ?? 0),
            (int) ($wordConfig['max'] ?? 2000)
        );

        $maxWords = $this->clampWordValue(
            (int) $this->input('max_words', $wordConfig['default_max'] ?? 1400),
            (int) ($wordConfig['min'] ?? 0),
            (int) ($wordConfig['max'] ?? 2000)
        );

        if ($minWords > $maxWords) {
            [$minWords, $maxWords] = [$maxWords, $minWords];
        }

        $resultCount = app(ContentFormulaRules::class)->normalizeResultCount(
            $this->filled('result_count') ? (int) $this->input('result_count') : null
        );

        return [
            'action' => $this->action(),
            'session_id' => trim((string) $this->input('session_id', '')) ?: null,
            'result_count' => $resultCount,
            'min_words' => $minWords,
            'max_words' => $maxWords,
            'groups' => [
                'topics' => $this->normalizeGroup($groups['topics'] ?? []),
                'article_types' => $this->normalizeGroup($groups['article_types'] ?? []),
                'article_formats' => $this->normalizeGroup($groups['article_formats'] ?? []),
                'vibes' => $this->normalizeGroup($groups['vibes'] ?? []),
                'reader_impacts' => $this->normalizeGroup($groups['reader_impacts'] ?? []),
                'audiences' => $this->normalizeGroup($groups['audiences'] ?? []),
                'contexts' => $this->normalizeGroup($groups['contexts'] ?? []),
                'perspectives' => $this->normalizeGroup($groups['perspectives'] ?? []),
            ],
            'extra_direction' => trim((string) $this->input('extra_direction', '')),
        ];
    }

    public function action(): string
    {
        return (string) $this->input('action', config('content_formula.generator.default_action', 'generate'));
    }

    protected function normalizeGroup(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                return [
                    'label' => trim((string) ($item['label'] ?? '')),
                    'stars' => (int) ($item['stars'] ?? 1),
                ];
            })
            ->filter(fn ($item) => $item['label'] !== '' && $item['stars'] >= 1 && $item['stars'] <= 3)
            ->values()
            ->all();
    }

    protected function clampWordValue(int $value, int $min, int $max): int
    {
        return max($min, min($max, $value));
    }

    protected function validateGroupItem(Validator $validator, string $groupKey, int $index, mixed $item, bool $required): void
    {
        if (!is_array($item)) {
            $validator->errors()->add("groups.{$groupKey}.{$index}", 'Each selected item must be a valid object.');
            return;
        }

        $label = $item['label'] ?? null;
        $stars = $item['stars'] ?? null;

        if ($required || $label !== null) {
            if (!is_string($label) || trim($label) === '') {
                $validator->errors()->add("groups.{$groupKey}.{$index}.label", 'Each selected item must have a valid label.');
            }
        }

        if ($stars === null && !$required) {
            return;
        }

        if (!is_int($stars) && !ctype_digit((string) $stars)) {
            $validator->errors()->add("groups.{$groupKey}.{$index}.stars", 'Each selected item must have a valid star value.');
            return;
        }

        $stars = (int) $stars;

        if ($stars < 1 || $stars > 3) {
            $validator->errors()->add("groups.{$groupKey}.{$index}.stars", 'Star values must be between 1 and 3.');
        }
    }

    protected function groupItems(array $groups, string $groupKey): array
    {
        $items = $groups[$groupKey] ?? [];

        return is_array($items) ? $items : [];
    }
}
