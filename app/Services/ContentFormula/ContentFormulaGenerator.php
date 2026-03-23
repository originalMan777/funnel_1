<?php

namespace App\Services\ContentFormula;

class ContentFormulaGenerator
{
    protected array $config;
    protected array $starWeights;
    protected array $requiredGroups;
    protected array $tier1Groups;
    protected array $tier2Groups;
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
        $this->tier1Groups = (array) data_get($this->config, 'generator.tier_1_groups', []);
        $this->tier2Groups = (array) data_get($this->config, 'generator.tier_2_groups', []);
        $this->variationConfig = (array) data_get($this->config, 'generator.variation', []);
    }

    /**
     * Generate structured content formula rows.
     *
     * @param  array  $payload
     * @return array
     */
    public function generate(array $payload): array
    {
        $groups = (array) ($payload['groups'] ?? []);
        $resultCount = (int) ($payload['result_count'] ?? data_get($this->config, 'generator.default_result_count', 50));
        $extraDirection = trim((string) ($payload['extra_direction'] ?? ''));

        $pools = $this->buildPools($groups);
        $this->ensureRequiredPoolsExist($pools);

        $usage = $this->initializeUsage($pools);
        $usedSignatures = [];
        $rows = [];

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

                if (!empty($rows) && $softBlockHighSimilarity) {
                    $previousRow = $rows[count($rows) - 1];
                    $similarity = $this->coreSimilarityCount($row, $previousRow);

                    if ($attempt <= $strictAttempts && $similarity >= $softSimilarityThreshold) {
                        continue;
                    }
                }

                $usedSignatures[$signature] = true;
                $rows[] = $this->decorateRow($row);
                $this->incrementUsage($usage, $row);

                $accepted = true;
                break;
            }

            if (!$accepted) {
                // Avoid infinite loops if the selected pools are too narrow.
                break;
            }
        }

        return [
            'meta' => [
                'requested_count' => $resultCount,
                'generated_count' => count($rows),
                'estimated_core_combinations' => $this->estimateCoreCombinationCount($pools),
            ],
            'rows' => $rows,
        ];
    }

    /**
     * Build normalized pools from payload groups.
     */
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

    /**
     * Ensure required groups are present.
     */
    protected function ensureRequiredPoolsExist(array $pools): void
    {
        foreach ($this->requiredGroups as $groupKey) {
            if (empty($pools[$groupKey])) {
                throw new \InvalidArgumentException("The required group [{$groupKey}] is empty.");
            }
        }
    }

    /**
     * Normalize selected items into a consistent internal structure.
     */
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

    /**
     * Initialize usage counters for all pools.
     */
    protected function initializeUsage(array $pools): array
    {
        $usage = [];

        foreach ($pools as $groupKey => $items) {
            $usage[$groupKey] = [];

            foreach ($items as $item) {
                $usage[$groupKey][$item['label']] = 0;
            }
        }

        return $usage;
    }

    /**
     * Build one candidate row.
     */
    protected function buildCandidateRow(array $pools, array $usage, string $extraDirection): array
    {
        $row = [
            'topic' => $this->weightedPick('topics', $pools['topics'], $usage)['label'],
            'article_type' => $this->weightedPick('article_types', $pools['article_types'], $usage)['label'],
            'article_format' => $this->weightedPick('article_formats', $pools['article_formats'], $usage)['label'],
            'vibe' => $this->weightedPick('vibes', $pools['vibes'], $usage)['label'],

            // Tier 1: always include if selected
            'reader_impact' => $this->pickRequiredOptional('reader_impacts', $pools, $usage),
            'audience' => $this->pickRequiredOptional('audiences', $pools, $usage),
            'context' => $this->pickRequiredOptional('contexts', $pools, $usage),

            // Tier 2: include lightly/variably
            'perspective' => $this->pickTier2Optional('perspectives', $pools, $usage),

            'extra_direction' => $extraDirection !== '' ? $extraDirection : null,
        ];

        return $row;
    }

    /**
     * Pick a Tier 1 optional category if the pool exists.
     * Tier 1 is always included if selected by the user.
     */
    protected function pickRequiredOptional(string $groupKey, array $pools, array $usage): ?string
    {
        if (empty($pools[$groupKey])) {
            return null;
        }

        return $this->weightedPick($groupKey, $pools[$groupKey], $usage)['label'];
    }

    /**
     * Pick a Tier 2 optional category lightly/variably.
     */
    protected function pickTier2Optional(string $groupKey, array $pools, array $usage): ?string
    {
        if (empty($pools[$groupKey])) {
            return null;
        }

        $includeProbability = (float) data_get(
            $this->config,
            'generator.tier_2.default_include_probability',
            0.35
        );

        $random = mt_rand(1, 1000) / 1000;

        if ($random > $includeProbability) {
            return null;
        }

        return $this->weightedPick($groupKey, $pools[$groupKey], $usage)['label'];
    }

    /**
     * Weighted balanced pick using:
     * score = weight / (usage + 1)
     */
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

    /**
     * Build a stable signature for duplicate prevention.
     */
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
            $row['extra_direction'] ?? '',
        ]);
    }

    /**
     * Compare similarity only on the 4 core fields.
     */
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

    /**
     * Increment usage counts after accepting a row.
     */
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

    /**
     * Estimate core combination count for UI summaries/debugging.
     */
    protected function estimateCoreCombinationCount(array $pools): int
    {
        $topics = max(1, count($pools['topics'] ?? []));
        $types = max(1, count($pools['article_types'] ?? []));
        $formats = max(1, count($pools['article_formats'] ?? []));
        $vibes = max(1, count($pools['vibes'] ?? []));

        return $topics * $types * $formats * $vibes;
    }

    /**
     * Decorate the accepted row with display text, title options, and prompt options.
     */
    protected function decorateRow(array $row): array
    {
        return [
            'topic' => $row['topic'],
            'article_type' => $row['article_type'],
            'article_format' => $row['article_format'],
            'vibe' => $row['vibe'],
            'reader_impact' => $row['reader_impact'],
            'audience' => $row['audience'],
            'context' => $row['context'],
            'perspective' => $row['perspective'],
            'extra_direction' => $row['extra_direction'],

            'summary' => $this->buildSummary($row),
            'title_options' => $this->buildTitleOptions($row),
            'prompt_options' => $this->buildPromptOptions($row),
        ];
    }

    /**
     * Human-readable row summary for the admin UI.
     */
    protected function buildSummary(array $row): string
    {
        $parts = [
            $row['topic'] ?? null,
            $row['article_type'] ?? null,
            $row['article_format'] ?? null,
            $row['vibe'] ?? null,
        ];

        $optional = array_filter([
            $row['audience'] ?? null,
            $row['context'] ?? null,
            $row['reader_impact'] ?? null,
            $row['perspective'] ?? null,
        ]);

        $base = implode(' • ', array_filter($parts));

        if (empty($optional)) {
            return $base;
        }

        return $base . ' | ' . implode(' • ', $optional);
    }

    /**
     * Build independent title suggestions from one row.
     */
    protected function buildTitleOptions(array $row): array
    {
        $topic = $row['topic'];
        $articleType = $row['article_type'];
        $articleFormat = $row['article_format'];
        $audience = $row['audience'];
        $context = $row['context'];

        $titles = [
            "{$topic} {$articleType}: A {$articleFormat}",
            "What to Know About {$topic} {$articleType}",
            "How to Navigate {$topic} {$articleType}",
            "{$topic} {$articleType} You Should Know",
            "Common {$topic} {$articleType} to Understand",
        ];

        if ($audience) {
            $titles[] = "{$topic} {$articleType} for {$audience}";
        }

        if ($context) {
            $titles[] = "{$topic} {$articleType} {$context}";
        }

        return array_values(array_unique(array_filter($titles)));
    }

    /**
     * Build independent prompt suggestions from one row.
     */
    protected function buildPromptOptions(array $row): array
    {
        $topic = $row['topic'];
        $articleType = $row['article_type'];
        $articleFormat = $row['article_format'];
        $vibe = strtolower((string) $row['vibe']);

        $audienceClause = $row['audience'] ? " for {$row['audience']}" : '';
        $contextClause = $row['context'] ? " {$row['context']}" : '';
        $impactClause = $row['reader_impact'] ? " and leave the reader {$row['reader_impact']}" : '';
        $perspectiveClause = $row['perspective'] ? " from a {$row['perspective']} perspective" : '';
        $extraDirectionClause = $row['extra_direction'] ? " Also consider this extra direction: {$row['extra_direction']}." : '';

        $prompts = [
            "Write a {$vibe} {$articleFormat} about {$topic} focused on {$articleType}{$audienceClause}{$contextClause}{$impactClause}{$perspectiveClause}.{$extraDirectionClause}",
            "Write a practical {$articleFormat} on {$topic} centered around {$articleType}{$audienceClause}{$contextClause}. Keep it {$vibe}{$impactClause}.{$extraDirectionClause}",
            "Write an engaging article about {$topic} from a {$articleType} angle{$audienceClause}{$contextClause}. Use a {$vibe} tone{$impactClause}{$perspectiveClause}.{$extraDirectionClause}",
            "Write an insightful {$articleFormat} about {$topic} exploring {$articleType}{$audienceClause}{$contextClause}{$perspectiveClause}.{$impactClause}{$extraDirectionClause}",
            "Write a results-focused {$articleFormat} about {$topic} based on {$articleType}{$audienceClause}{$contextClause}. Keep it {$vibe}{$impactClause}.{$extraDirectionClause}",
        ];

        return array_values(array_unique(array_map(fn ($prompt) => trim(preg_replace('/\s+/', ' ', $prompt)), $prompts)));
    }

    /**
     * Map stars to generation weight.
     */
    protected function mapStarsToWeight(int $stars): int
    {
        return (int) ($this->starWeights[$stars] ?? 1);
    }
}
