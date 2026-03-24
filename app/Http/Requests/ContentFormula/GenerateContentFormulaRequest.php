<?php

namespace App\Http\Requests\ContentFormula;

use App\Services\ContentFormula\ContentFormulaSessionService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class GenerateContentFormulaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_admin;
    }

    public function rules(): array
    {
        $wordMin = (int) config('content_formula.generator.word_range.min', 0);
        $wordMax = (int) config('content_formula.generator.word_range.max', 2000);

        return [
            'action' => ['nullable', 'string', 'in:generate,continue,reset'],
            'session_id' => ['nullable', 'string', 'max:100'],
            'result_count' => ['nullable', 'integer', 'min:1', 'max:' . (int) config('content_formula.generator.max_result_count', 50)],
            'min_words' => ['nullable', 'integer', 'min:' . $wordMin, 'max:' . $wordMax],
            'max_words' => ['nullable', 'integer', 'min:' . $wordMin, 'max:' . $wordMax],
            'groups' => ['required', 'array'],

            'groups.topics' => ['required', 'array', 'min:1'],
            'groups.article_types' => ['required', 'array', 'min:1'],
            'groups.article_formats' => ['required', 'array', 'min:1'],
            'groups.vibes' => ['required', 'array', 'min:1'],

            'groups.reader_impacts' => ['nullable', 'array'],
            'groups.audiences' => ['nullable', 'array'],
            'groups.contexts' => ['nullable', 'array'],
            'groups.perspectives' => ['nullable', 'array'],

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
            'groups.required' => 'The content formula groups are required.',
            'groups.topics.required' => 'Please select at least one topic.',
            'groups.topics.min' => 'Please select at least one topic.',
            'groups.article_types.required' => 'Please select at least one type of article.',
            'groups.article_types.min' => 'Please select at least one type of article.',
            'groups.article_formats.required' => 'Please select at least one article format.',
            'groups.article_formats.min' => 'Please select at least one article format.',
            'groups.vibes.required' => 'Please select at least one vibe.',
            'groups.vibes.min' => 'Please select at least one vibe.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $groups = $this->input('groups', []);
            $requiredGroups = (array) config('content_formula.generator.required_groups', []);
            $action = $this->action();
            $sessionId = trim((string) $this->input('session_id', ''));

            $minWords = $this->input('min_words', config('content_formula.generator.word_range.default_min', 800));
            $maxWords = $this->input('max_words', config('content_formula.generator.word_range.default_max', 1400));

            if ((int) $minWords > (int) $maxWords) {
                $validator->errors()->add('min_words', 'Minimum words cannot be greater than maximum words.');
            }

            if (in_array($action, ['continue', 'reset'], true) && $sessionId === '') {
                $validator->errors()->add('session_id', 'A valid generation session is required for this action.');
            }

            if ($sessionId !== '' && in_array($action, ['continue', 'reset'], true)) {
                $sessions = app(ContentFormulaSessionService::class);

                if (!$sessions->exists($sessionId)) {
                    $validator->errors()->add('session_id', 'The selected generation session is no longer available. Start a fresh generation.');
                }
            }

            foreach ($requiredGroups as $groupKey) {
                $items = $groups[$groupKey] ?? [];

                if (!is_array($items) || count($items) < 1) {
                    $validator->errors()->add("groups.{$groupKey}", "Please select at least one option for {$groupKey}.");
                    continue;
                }

                foreach ($items as $index => $item) {
                    $this->validateGroupItem($validator, $groupKey, $index, $item, true);
                }
            }

            foreach (['reader_impacts', 'audiences', 'contexts', 'perspectives'] as $optionalGroup) {
                $items = $groups[$optionalGroup] ?? [];

                if (!is_array($items)) {
                    continue;
                }

                foreach ($items as $index => $item) {
                    $this->validateGroupItem($validator, $optionalGroup, $index, $item, false);
                }
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

        $resultCount = (int) ($this->input('result_count') ?: config('content_formula.generator.default_result_count', 50));

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
}
