<?php

namespace Tests\Contracts\Json;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentFormulaContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_response_contract_matches_current_frontend_shape(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload());

        $response->assertOk()->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'session' => ['id'],
                'meta' => [
                    'tier',
                    'batch_size',
                    'can_continue',
                    'can_reset',
                    'remaining_continue_count',
                    'remaining_reset_count',
                    'requested_count',
                    'generated_count',
                    'estimated_core_combinations',
                    'session_generated_count',
                    'exhausted',
                    'word_range' => ['min', 'max'],
                ],
                'rows' => [
                    '*' => [
                        'id',
                        'summary',
                        'profile' => [
                            'topic',
                            'article_type',
                            'article_format',
                            'vibe',
                            'reader_impact',
                            'audience',
                            'context',
                            'perspective',
                            'word_range' => ['min', 'max'],
                        ],
                        'badges',
                        'title_options',
                        'standard_prompts',
                        'optimized_prompts',
                    ],
                ],
            ],
        ]);

        $this->assertSame(true, $response->json('success'));
        $this->assertSame(25, $response->json('data.meta.requested_count'));
        $this->assertSame(25, $response->json('data.meta.generated_count'));
        $this->assertCount(25, $response->json('data.rows'));
    }

    public function test_config_response_contract_matches_current_frontend_shape(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->getJson(route('admin.content-formula.config'));

        $response->assertOk()->assertJsonStructure([
            'success',
            'data' => [
                'config' => [
                    'generator' => [
                        'allowed_result_counts',
                        'default_result_count',
                        'max_result_count',
                        'max_selections_per_group',
                        'min_combination_threshold',
                        'combination_group_keys',
                        'required_groups',
                        'tier_1_groups',
                        'tier_2_groups',
                        'word_range' => ['min', 'max', 'default_min', 'default_max'],
                        'prompt_families' => [
                            'standard' => ['label', 'count'],
                            'optimized' => ['label', 'count'],
                        ],
                        'tier' => ['name', 'batch_size', 'reset_limit', 'continue_limit'],
                    ],
                    'ui',
                    'categories',
                    'title_styles',
                    'prompt_styles',
                ],
            ],
        ]);
    }

    private function validPayload(): array
    {
        return [
            'action' => 'generate',
            'result_count' => 25,
            'min_words' => 600,
            'max_words' => 1100,
            'groups' => [
                'topics' => $this->items('Topic', 10),
                'article_types' => $this->items('Type', 10),
                'article_formats' => $this->items('Format', 10),
            ],
        ];
    }

    private function items(string $prefix, int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $index) => ['label' => "{$prefix} {$index}", 'stars' => 1])
            ->all();
    }
}
