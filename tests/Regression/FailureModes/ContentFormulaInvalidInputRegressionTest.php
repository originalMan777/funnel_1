<?php

namespace Tests\Regression\FailureModes;

use App\Models\User;
use App\Services\ContentFormula\ContentFormulaEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use Tests\TestCase;

class ContentFormulaInvalidInputRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_invalid_topic_selection_is_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), [
                'action' => 'generate',
                'result_count' => 25,
                'min_words' => 600,
                'max_words' => 1100,
                'groups' => [
                    'topics' => [['label' => '', 'stars' => 1]],
                    'article_types' => $this->items('Type', 10),
                    'article_formats' => $this->items('Format', 10),
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups.topics.0.label']);
    }

    public function test_invalid_row_count_requests_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'result_count' => 999,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['result_count']);
    }

    public function test_invalid_word_range_requests_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'min_words' => 2500,
                'max_words' => 2600,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_words', 'max_words']);
    }

    public function test_malformed_payload_shapes_do_not_silently_succeed(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_rejected_malformed_payload'
                    && $context['action_type'] === 'generate'
                    && $context['validation_field'] === 'groups.topics.0'
                    && $context['rejection_reason'] === 'group_item_must_be_object';
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), [
                'action' => 'generate',
                'result_count' => 25,
                'min_words' => 600,
                'max_words' => 1100,
                'groups' => [
                    'topics' => ['not-an-object'],
                    'article_types' => $this->items('Type', 10),
                    'article_formats' => $this->items('Format', 10),
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups.topics.0']);
    }

    public function test_top_level_malformed_groups_payload_emits_failure_visibility_and_is_rejected(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_rejected_malformed_payload'
                    && $context['validation_field'] === 'groups'
                    && $context['rejection_reason'] === 'groups_must_be_array';
            });
        $logger->shouldReceive('error')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_failure_unexpected_payload_shape'
                    && $context['generator_stage'] === 'validate'
                    && $context['validation_field'] === 'groups'
                    && $context['rejection_reason'] === 'groups_must_be_array';
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), [
                'action' => 'generate',
                'result_count' => 25,
                'min_words' => 600,
                'max_words' => 1100,
                'groups' => 'not-an-array',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups']);
    }

    public function test_continue_and_reset_require_a_valid_existing_session_id(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_rejected_stale_session'
                    && $context['action_type'] === 'continue'
                    && $context['session_id'] === 'missing-session'
                    && $context['validation_field'] === 'session_id';
            });
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_rejected_stale_session'
                    && $context['action_type'] === 'reset'
                    && $context['session_id'] === 'missing-session'
                    && $context['validation_field'] === 'session_id';
            });

        $admin = User::factory()->create(['is_admin' => true]);

        foreach (['continue', 'reset'] as $action) {
            $this->actingAs($admin)
                ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                    'action' => $action,
                    'session_id' => 'missing-session',
                ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['session_id']);
        }
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace_recursive([
            'action' => 'generate',
            'result_count' => 25,
            'min_words' => 600,
            'max_words' => 1100,
            'groups' => [
                'topics' => $this->items('Topic', 10),
                'article_types' => $this->items('Type', 10),
                'article_formats' => $this->items('Format', 10),
            ],
        ], $overrides);
    }

    private function items(string $prefix, int $count): array
    {
        return collect(range(1, $count))
            ->map(fn (int $index) => ['label' => "{$prefix} {$index}", 'stars' => 1])
            ->all();
    }

    private function mockEventLogger(): MockInterface
    {
        $logger = \Mockery::mock(ContentFormulaEventLogger::class);
        $logger->shouldIgnoreMissing();

        $this->app->instance(ContentFormulaEventLogger::class, $logger);

        return $logger;
    }
}
