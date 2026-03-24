<?php

namespace App\Services\ContentFormula;

class ContentFormulaGenerator
{
    protected array $config;
    protected array $starWeights;
    protected array $requiredGroups;
    protected array $variationConfig;

    public function __construct()
    {
        $this->config = config('content_formula', []);
        $this->starWeights = (array) data_get($this->config, 'generator.star_weights', [
            1 => 1,
            2 => 2,
            3 => 4,
        ]);
        $this->requiredGroups = (array) data_get($this->config, 'generator.required_groups', []);
        $this->variationConfig = (array) data_get($this->config, 'generator.variation', []);
    }

    public function generateBatch(array $settings, array $session): array
    {
        $groups = (array) ($settings['groups'] ?? []);
        $resultCount = (int) ($settings['result_count'] ?? data_get($this->config, 'generator.default_result_count', 50));
        $extraDirection = trim((string) ($settings['extra_direction'] ?? ''));
        $minWords = (int) ($settings['min_words'] ?? data_get($this->config, 'generator.word_range.default_min', 800));
        $maxWords = (int) ($settings['max_words'] ?? data_get($this->config, 'generator.word_range.default_max', 1400));

        $pools = $this->buildPools($groups);
        $this->ensureRequiredPoolsExist($pools);

        $usage = $this->restoreUsage($pools, (array) ($session['usage'] ?? []));
        $usedSignatures = array_fill_keys((array) ($session['used_signatures'] ?? []), true);
        $rows = [];
        $lastAcceptedRow = null;

        $maxPerRowAttempts = (int) ($this->variationConfig['max_attempts_per_row'] ?? 80);
        $strictAttempts = (int) ($this->variationConfig['strict_attempts'] ?? 30);
        $softSimilarityThreshold = (int) ($this->variationConfig['soft_similarity_threshold'] ?? 3);
        $preventExactDuplicates = (bool) ($this->variationConfig['prevent_exact_duplicates'] ?? true);
        $softBlockHighSimilarity = (bool) ($this->variationConfig['soft_block_high_similarity_to_previous'] ?? true);

        while (count($rows) < $resultCount) {
            $accepted = false;

            for ($attempt = 1; $attempt <= $maxPerRowAttempts; $attempt++) {
                $row = $this->buildCandidateRow($pools, $usage, $extraDirection);
                $signature = $this->buildSignature($row);

                if ($preventExactDuplicates && isset($usedSignatures[$signature])) {
                    continue;
                }

                if ($lastAcceptedRow && $softBlockHighSimilarity) {
                    $similarity = $this->coreSimilarityCount($row, $lastAcceptedRow);

                    if ($attempt <= $strictAttempts && $similarity >= $softSimilarityThreshold) {
                        continue;
                    }
                }

                $decorated = $this->decorateRow($row, $minWords, $maxWords);
                $rows[] = $decorated;
                $usedSignatures[$signature] = true;
                $this->incrementUsage($usage, $row);
                $lastAcceptedRow = $row;
                $accepted = true;
                break;
            }

            if (!$accepted) {
                break;
            }
        }

        return [
            'rows' => $rows,
            'meta' => [
                'requested_count' => $resultCount,
                'generated_count' => count($rows),
                'estimated_core_combinations' => $this->estimateCoreCombinationCount($pools),
            ],
            'session' => [
                'usage' => $usage,
                'used_signatures' => array_keys($usedSignatures),
                'generated_count' => (int) ($session['generated_count'] ?? 0) + count($rows),
                'exhausted' => count($rows) < $resultCount,
            ],
        ];
    }

    protected function buildPools(array $groups): array
    {
        return [
            'topics' => $this->normalizePool($groups['topics'] ?? []),
            'article_types' => $this->normalizePool($groups['article_types'] ?? []),
            'article_formats' => $this->normalizePool($groups['article_formats'] ?? []),
            'vibes' => $this->normalizePool($groups['vibes'] ?? []),
            'reader_impacts' => $this->normalizePool($groups['reader_impacts'] ?? []),
            'audiences' => $this->normalizePool($groups['audiences'] ?? []),
            'contexts' => $this->normalizePool($groups['contexts'] ?? []),
            'perspectives' => $this->normalizePool($groups['perspectives'] ?? []),
        ];
    }

    protected function ensureRequiredPoolsExist(array $pools): void
    {
        foreach ($this->requiredGroups as $groupKey) {
            if (empty($pools[$groupKey])) {
                throw new \InvalidArgumentException("The required group [{$groupKey}] is empty.");
            }
        }
    }

    protected function normalizePool(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item))
            ->map(function (array $item) {
                $label = trim((string) ($item['label'] ?? ''));
                $stars = (int) ($item['stars'] ?? 1);

                return [
                    'label' => $label,
                    'stars' => max(1, min(3, $stars)),
                    'weight' => $this->mapStarsToWeight($stars),
                ];
            })
            ->filter(fn ($item) => $item['label'] !== '')
            ->values()
            ->all();
    }

    protected function restoreUsage(array $pools, array $sessionUsage): array
    {
        $usage = [];

        foreach ($pools as $groupKey => $items) {
            $usage[$groupKey] = [];

            foreach ($items as $item) {
                $label = $item['label'];
                $usage[$groupKey][$label] = (int) ($sessionUsage[$groupKey][$label] ?? 0);
            }
        }

        return $usage;
    }

    protected function buildCandidateRow(array $pools, array $usage, string $extraDirection): array
    {
        return [
            'topic' => $this->weightedPick('topics', $pools['topics'], $usage)['label'],
            'article_type' => $this->weightedPick('article_types', $pools['article_types'], $usage)['label'],
            'article_format' => $this->weightedPick('article_formats', $pools['article_formats'], $usage)['label'],
            'vibe' => $this->weightedPick('vibes', $pools['vibes'], $usage)['label'],
            'reader_impact' => $this->pickOptional('reader_impacts', $pools, $usage, true),
            'audience' => $this->pickOptional('audiences', $pools, $usage, true),
            'context' => $this->pickOptional('contexts', $pools, $usage, true),
            'perspective' => $this->pickOptional('perspectives', $pools, $usage, false),
            'extra_direction' => $extraDirection !== '' ? $extraDirection : null,
        ];
    }

    protected function pickOptional(string $groupKey, array $pools, array $usage, bool $alwaysInclude): ?string
    {
        if (empty($pools[$groupKey])) {
            return null;
        }

        if (!$alwaysInclude) {
            $includeProbability = (float) data_get($this->config, 'generator.tier_2.default_include_probability', 0.35);

            if ((mt_rand(1, 1000) / 1000) > $includeProbability) {
                return null;
            }
        }

        return $this->weightedPick($groupKey, $pools[$groupKey], $usage)['label'];
    }

    protected function weightedPick(string $groupKey, array $items, array $usage): array
    {
        if (count($items) === 1) {
            return $items[0];
        }

        $scored = [];
        $totalScore = 0.0;

        foreach ($items as $item) {
            $label = $item['label'];
            $weight = (float) ($item['weight'] ?? 1);
            $used = (int) ($usage[$groupKey][$label] ?? 0);
            $score = $weight / ($used + 1);

            $scored[] = [
                'item' => $item,
                'score' => $score,
            ];

            $totalScore += $score;
        }

        if ($totalScore <= 0) {
            return $items[array_rand($items)];
        }

        $pick = (mt_rand(1, 1000000) / 1000000) * $totalScore;
        $running = 0.0;

        foreach ($scored as $entry) {
            $running += $entry['score'];

            if ($pick <= $running) {
                return $entry['item'];
            }
        }

        return $scored[array_key_last($scored)]['item'];
    }

    protected function buildSignature(array $row): string
    {
        return implode('|', [
            $row['topic'] ?? '',
            $row['article_type'] ?? '',
            $row['article_format'] ?? '',
            $row['vibe'] ?? '',
            $row['reader_impact'] ?? '',
            $row['audience'] ?? '',
            $row['context'] ?? '',
            $row['perspective'] ?? '',
        ]);
    }

    protected function coreSimilarityCount(array $a, array $b): int
    {
        $same = 0;

        foreach (['topic', 'article_type', 'article_format', 'vibe'] as $field) {
            if (($a[$field] ?? null) === ($b[$field] ?? null)) {
                $same++;
            }
        }

        return $same;
    }

    protected function incrementUsage(array &$usage, array $row): void
    {
        $map = [
            'topics' => 'topic',
            'article_types' => 'article_type',
            'article_formats' => 'article_format',
            'vibes' => 'vibe',
            'reader_impacts' => 'reader_impact',
            'audiences' => 'audience',
            'contexts' => 'context',
            'perspectives' => 'perspective',
        ];

        foreach ($map as $groupKey => $rowKey) {
            $label = $row[$rowKey] ?? null;

            if ($label !== null && isset($usage[$groupKey][$label])) {
                $usage[$groupKey][$label]++;
            }
        }
    }

    protected function estimateCoreCombinationCount(array $pools): int
    {
        $topics = max(1, count($pools['topics'] ?? []));
        $types = max(1, count($pools['article_types'] ?? []));
        $formats = max(1, count($pools['article_formats'] ?? []));
        $vibes = max(1, count($pools['vibes'] ?? []));

        return $topics * $types * $formats * $vibes;
    }

    protected function decorateRow(array $row, int $minWords, int $maxWords): array
    {
        $standardCount = (int) data_get($this->config, 'generator.prompt_families.standard.count', 2);
        $optimizedCount = (int) data_get($this->config, 'generator.prompt_families.optimized.count', 3);
        $lengthInstructions = $this->buildLengthInstructions($minWords, $maxWords, $standardCount + $optimizedCount);
        $signature = $this->buildSignature($row);

        return [
            'id' => substr(sha1($signature), 0, 20),
            'summary' => $this->buildSummary($row, $minWords, $maxWords),
            'profile' => [
                'topic' => $row['topic'],
                'article_type' => $row['article_type'],
                'article_format' => $row['article_format'],
                'vibe' => $row['vibe'],
                'reader_impact' => $row['reader_impact'],
                'audience' => $row['audience'],
                'context' => $row['context'],
                'perspective' => $row['perspective'],
                'word_range' => [
                    'min' => $minWords,
                    'max' => $maxWords,
                ],
            ],
            'badges' => $this->buildBadges($row, $minWords, $maxWords),
            'title_options' => $this->buildTitleOptions($row),
            'standard_prompts' => $this->buildPromptFamily($row, 'standard', array_slice($lengthInstructions, 0, $standardCount)),
            'optimized_prompts' => $this->buildPromptFamily($row, 'optimized', array_slice($lengthInstructions, $standardCount, $optimizedCount)),
        ];
    }

    protected function buildSummary(array $row, int $minWords, int $maxWords): string
    {
        $parts = [
            "{$row['topic']} / {$row['article_type']}",
            "{$row['article_format']} structure",
            "{$row['vibe']} tone",
        ];

        if ($row['audience']) {
            $parts[] = "for {$row['audience']}";
        }

        if ($row['context']) {
            $parts[] = $row['context'];
        }

        $parts[] = $minWords === $maxWords
            ? "about {$minWords} words"
            : "{$minWords}-{$maxWords} words";

        return implode(' • ', $parts);
    }

    protected function buildBadges(array $row, int $minWords, int $maxWords): array
    {
        $badges = [
            $row['article_format'],
            $row['article_type'],
            "{$row['vibe']} tone",
            $minWords === $maxWords ? "{$minWords} words" : "{$minWords}-{$maxWords} words",
        ];

        foreach (['audience', 'context', 'reader_impact', 'perspective'] as $field) {
            if (!empty($row[$field])) {
                $badges[] = $row[$field];
            }
        }

        return array_values(array_slice(array_unique($badges), 0, 8));
    }

    protected function buildTitleOptions(array $row): array
    {
        $audienceFallback = $row['audience'] ?? 'Readers';

        $titles = collect((array) data_get($this->config, 'title_styles', []))
            ->map(fn (array $style) => $this->applyTemplate((string) ($style['template'] ?? ''), $this->titleTemplateReplacements($row)))
            ->filter()
            ->values()
            ->all();

        $fallbacks = [
            "{$row['topic']}: {$row['article_type']} in a {$row['article_format']} Format",
            "{$row['topic']} for {$row['audience']}" . ($row['context'] ? " {$row['context']}" : ''),
            "A {$row['vibe']} Take on {$row['topic']} {$row['article_type']}",
            "{$row['article_format']} Ideas for {$row['topic']} {$row['article_type']}",
            "What {$audienceFallback} Should Know About {$row['topic']}",
        ];

        $titles = array_values(array_unique(array_filter(array_merge($titles, $fallbacks))));

        return array_slice($titles, 0, 5);
    }

    protected function titleTemplateReplacements(array $row): array
    {
        return [
            ':topic' => $row['topic'],
            ':article_type' => $row['article_type'],
            ':article_format' => $row['article_format'],
            ':vibe' => $row['vibe'],
        ];
    }

    protected function buildPromptFamily(array $row, string $family, array $lengthInstructions): array
    {
        $templates = (array) data_get($this->config, "prompt_styles.{$family}", []);
        $prompts = [];

        foreach ($templates as $index => $template) {
            $prompts[] = $this->applyTemplate(
                (string) ($template['template'] ?? ''),
                $this->templateReplacements($row, $lengthInstructions[$index] ?? '')
            );
        }

        return array_values(array_slice(array_unique(array_filter($prompts)), 0, count($templates)));
    }

    protected function buildLengthInstructions(int $minWords, int $maxWords, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        if ($minWords === $maxWords) {
            return array_fill(0, $count, "Target approximately {$minWords} words.");
        }

        $range = max(1, $maxWords - $minWords);
        $window = max(75, (int) floor($range / 4));
        $anchors = [0.14, 0.32, 0.5, 0.72, 0.9];
        $instructions = [];

        for ($i = 0; $i < $count; $i++) {
            $anchor = $anchors[$i] ?? min(1, $i / max(1, $count - 1));
            $center = (int) round($minWords + ($range * $anchor));
            $targetMin = max($minWords, $center - (int) floor($window / 2));
            $targetMax = min($maxWords, $center + (int) floor($window / 2));

            if ($targetMin >= $targetMax) {
                $instructions[] = "Target approximately {$center} words.";
                continue;
            }

            $instructions[] = "Aim for roughly {$targetMin} to {$targetMax} words.";
        }

        return $instructions;
    }

    protected function templateReplacements(array $row, string $lengthInstruction): array
    {
        $readerImpact = $row['reader_impact'] ? $this->lowercaseValue($row['reader_impact']) : 'well equipped';

        return [
            ':topic' => $row['topic'],
            ':article_type' => $this->lowercaseValue($row['article_type']),
            ':article_format' => $this->lowercaseValue($row['article_format']),
            ':vibe' => $this->lowercaseValue($row['vibe']),
            ':audience_clause' => $row['audience'] ? ' for ' . $row['audience'] : '',
            ':context_clause' => $row['context'] ? ' in the context of ' . $row['context'] : '',
            ':perspective_clause' => $row['perspective'] ? ' from a ' . $row['perspective'] . ' perspective' : '',
            ':impact_sentence' => $row['reader_impact'] ? 'Leave the reader ' . $readerImpact . '. ' : '',
            ':length_instruction' => $lengthInstruction !== '' ? $lengthInstruction : '',
            ':extra_direction_sentence' => $row['extra_direction'] ? ' Also incorporate this extra direction: ' . $row['extra_direction'] . '.' : '',
            ':reader_impact_lower' => $readerImpact,
            ':audience_fallback' => $row['audience'] ?? 'the intended reader',
            ':context_fallback' => $row['context'] ?? 'the current market context',
        ];
    }

    protected function applyTemplate(string $template, array $replacements): string
    {
        $text = strtr($template, $replacements);
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = preg_replace('/\s+\./', '.', $text) ?? $text;
        $text = preg_replace('/\.\s+\./', '.', $text) ?? $text;

        return trim($text);
    }

    protected function lowercaseValue(?string $value): string
    {
        if (!$value) {
            return '';
        }

        return lcfirst($value);
    }

    protected function mapStarsToWeight(int $stars): int
    {
        return (int) ($this->starWeights[$stars] ?? 1);
    }
}
