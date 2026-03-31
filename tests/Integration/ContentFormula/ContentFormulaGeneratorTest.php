<?php

namespace Tests\Integration\ContentFormula;

use App\Services\ContentFormula\ContentFormulaGenerator;
use Tests\TestCase;

class ContentFormulaGeneratorTest extends TestCase
{
    public function test_valid_generator_inputs_produce_coherent_structured_rows(): void
    {
        $generator = app(ContentFormulaGenerator::class);

        $result = $generator->generateBatch($this->settings([
            'result_count' => 25,
        ]), $this->sessionData());

        $this->assertCount(25, $result['rows']);
        $this->assertSame(25, $result['meta']['requested_count']);
        $this->assertSame(25, $result['meta']['generated_count']);
        $this->assertSame(25, $result['session']['generated_count']);
        $this->assertFalse($result['session']['exhausted']);

        $row = $result['rows'][0];

        $this->assertSame([
            'id',
            'summary',
            'profile',
            'badges',
            'title_options',
            'standard_prompts',
            'optimized_prompts',
        ], array_keys($row));
        $this->assertCount(5, $row['title_options']);
        $this->assertCount(2, $row['standard_prompts']);
        $this->assertCount(3, $row['optimized_prompts']);
        $this->assertSame(600, $row['profile']['word_range']['min']);
        $this->assertSame(1100, $row['profile']['word_range']['max']);
    }

    public function test_generator_normalizes_unsupported_result_counts_to_the_current_default_rule(): void
    {
        $generator = app(ContentFormulaGenerator::class);

        $result = $generator->generateBatch($this->settings([
            'result_count' => 75,
        ]), $this->sessionData());

        $this->assertSame(50, $result['meta']['requested_count']);
        $this->assertCount(50, $result['rows']);
    }

    public function test_generator_throws_when_required_topic_pool_is_missing(): void
    {
        $generator = app(ContentFormulaGenerator::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('required group [topics]');

        $generator->generateBatch([
            'groups' => [
                'topics' => [],
                'article_types' => $this->items('Type', 10),
                'article_formats' => $this->items('Format', 10),
                'vibes' => [],
                'reader_impacts' => [],
                'audiences' => [],
                'contexts' => [],
                'perspectives' => [],
            ],
            'extra_direction' => '',
            'min_words' => 600,
            'max_words' => 1100,
            'result_count' => 25,
        ], $this->sessionData());
    }

    public function test_generator_marks_session_exhausted_when_it_cannot_produce_requested_unique_rows(): void
    {
        $generator = app(ContentFormulaGenerator::class);

        $result = $generator->generateBatch([
            'groups' => [
                'topics' => [['label' => 'Single Topic', 'stars' => 1]],
                'article_types' => [['label' => 'Single Type', 'stars' => 1]],
                'article_formats' => [['label' => 'Single Format', 'stars' => 1]],
                'vibes' => [],
                'reader_impacts' => [],
                'audiences' => [],
                'contexts' => [],
                'perspectives' => [],
            ],
            'extra_direction' => '',
            'min_words' => 600,
            'max_words' => 1100,
            'result_count' => 25,
        ], $this->sessionData());

        $this->assertCount(1, $result['rows']);
        $this->assertTrue($result['session']['exhausted']);
        $this->assertSame(1, $result['meta']['generated_count']);
    }

    public function test_generator_continue_state_avoids_reemitting_used_signatures(): void
    {
        $generator = app(ContentFormulaGenerator::class);

        $first = $generator->generateBatch($this->settings(), $this->sessionData());

        $second = $generator->generateBatch($this->settings(), array_merge($this->sessionData(), $first['session']));

        $firstIds = collect($first['rows'])->pluck('id');
        $secondIds = collect($second['rows'])->pluck('id');

        $this->assertTrue($firstIds->intersect($secondIds)->isEmpty());
        $this->assertSame(50, $second['session']['generated_count']);
    }

    private function settings(array $overrides = []): array
    {
        return array_replace_recursive([
            'groups' => [
                'topics' => $this->items('Topic', 10),
                'article_types' => $this->items('Type', 10),
                'article_formats' => $this->items('Format', 10),
                'vibes' => [],
                'reader_impacts' => [],
                'audiences' => [],
                'contexts' => [],
                'perspectives' => [],
            ],
            'extra_direction' => '',
            'min_words' => 600,
            'max_words' => 1100,
            'result_count' => 25,
        ], $overrides);
    }

    private function sessionData(): array
    {
        return [
            'id' => 'test-session',
            'tier' => 'paid',
            'settings' => [],
            'used_signatures' => [],
            'usage' => [],
            'generated_count' => 0,
            'continue_count' => 0,
            'reset_count' => 0,
            'exhausted' => false,
        ];
    }

    private function items(string $prefix, int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $index) => ['label' => "{$prefix} {$index}", 'stars' => 1])
            ->all();
    }
}
