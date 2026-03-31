<?php

namespace Tests\Feature\Admin\ContentFormula;

use App\Models\User;
use App\Services\ContentFormula\ContentFormulaEventLogger;
use App\Services\ContentFormula\ContentFormulaGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class ContentFormulaGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_valid_generation_request_succeeds_and_returns_the_requested_allowed_row_count(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_generate_started'
                    && $context['action_type'] === 'generate'
                    && $context['requested_result_count'] === 50;
            });
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_generate_completed'
                    && $context['action_type'] === 'generate'
                    && $context['requested_result_count'] === 50
                    && $context['generated_row_count'] === 50
                    && is_int($context['duration_ms']);
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'result_count' => 50,
            ]));

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.meta.requested_count', 50)
            ->assertJsonPath('data.meta.generated_count', 50)
            ->assertJsonPath('data.meta.batch_size', 50)
            ->assertJsonCount(50, 'data.rows');
    }

    public function test_generation_requires_valid_topic_selection(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), [
                'action' => 'generate',
                'result_count' => 25,
                'min_words' => 600,
                'max_words' => 1100,
                'groups' => [
                    'topics' => [],
                    'article_types' => $this->items('Type', 10),
                    'article_formats' => $this->items('Format', 10),
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups.topics']);
    }

    public function test_invalid_word_ranges_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'min_words' => 1200,
                'max_words' => 400,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_words']);
    }

    public function test_unsupported_result_counts_are_rejected(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        foreach ([10, 75, 200] as $count) {
            $this->actingAs($admin)
                ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                    'result_count' => $count,
                ]))
                ->assertStatus(422)
                ->assertJsonValidationErrors(['result_count']);
        }
    }

    public function test_generation_rejects_more_than_twenty_selections_in_a_single_group(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'groups' => [
                    'topics' => $this->items('Topic', 21),
                    'article_types' => $this->items('Type', 10),
                    'article_formats' => $this->items('Format', 10),
                ],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups.topics']);
    }

    public function test_generation_rejects_payloads_that_do_not_meet_the_combination_threshold(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_rejected_threshold_not_met'
                    && $context['action_type'] === 'generate'
                    && $context['validation_field'] === 'groups'
                    && $context['threshold_required'] === 1000
                    && $context['combination_count'] === 4;
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), [
                'action' => 'generate',
                'result_count' => 25,
                'min_words' => 600,
                'max_words' => 1100,
                'groups' => [
                    'topics' => $this->items('Topic', 2),
                    'audiences' => $this->items('Audience', 2),
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['groups']);
    }

    public function test_generation_is_accepted_when_the_combination_threshold_is_exactly_met(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload())
            ->assertOk()
            ->assertJsonPath('data.meta.estimated_core_combinations', 1000)
            ->assertJsonCount(25, 'data.rows');
    }

    public function test_continue_and_reset_follow_the_current_session_semantics(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(fn (string $event, $request, array $context): bool => $event === 'generator_continue_started' && $context['action_type'] === 'continue');
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(fn (string $event, $request, array $context): bool => $event === 'generator_continue_completed' && $context['action_type'] === 'continue' && $context['generated_row_count'] === 25);
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(fn (string $event, $request, array $context): bool => $event === 'generator_reset_started' && $context['action_type'] === 'reset');
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_reset_completed'
                    && $context['action_type'] === 'reset'
                    && $context['generated_row_count'] === 25
                    && !empty($context['previous_session_id']);
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $generateResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'result_count' => 25,
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $sessionId = $generateResponse->json('data.session.id');

        $continueResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'continue',
                'session_id' => $sessionId,
                'result_count' => 25,
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $continueResponse
            ->assertJsonPath('data.session.id', $sessionId)
            ->assertJsonPath('data.meta.session_generated_count', 50)
            ->assertJsonPath('data.meta.word_range.min', 700)
            ->assertJsonPath('data.meta.word_range.max', 900);

        $resetResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'reset',
                'session_id' => $sessionId,
                'result_count' => 25,
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $this->assertNotSame($sessionId, $resetResponse->json('data.session.id'));
        $resetResponse
            ->assertJsonPath('data.meta.session_generated_count', 25)
            ->assertJsonPath('data.meta.word_range.min', 700)
            ->assertJsonPath('data.meta.word_range.max', 900);
    }

    public function test_unexpected_generation_failures_emit_generator_specific_context(): void
    {
        $logger = $this->fakeEventLogger();

        $this->app->instance(ContentFormulaGenerator::class, new class extends ContentFormulaGenerator
        {
            public function __construct() {}

            public function generateBatch(array $settings, array $session): array
            {
                throw new RuntimeException('generation exploded');
            }
        });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->withoutExceptionHandling();

        try {
            $this->actingAs($admin)
                ->postJson(route('admin.content-formula.generate'), $this->validPayload());
            $this->fail('The generator failure request should have thrown a RuntimeException.');
        } catch (RuntimeException $exception) {
            $this->assertSame('generation exploded', $exception->getMessage());
        }

        $this->assertTrue(collect($logger->entries)->contains(function (array $entry): bool {
            return $entry['level'] === 'error'
                && $entry['event'] === 'generator_failure_exception'
                && $entry['context']['action_type'] === 'generate'
                && $entry['context']['generator_stage'] === 'generate_batch'
                && $entry['context']['exception_class'] === RuntimeException::class
                && $entry['context']['safe_exception_message'] === 'generation exploded';
        }));
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

    private function fakeEventLogger(): object
    {
        $logger = new class extends ContentFormulaEventLogger
        {
            public array $entries = [];

            public function info(string $event, ?\Illuminate\Http\Request $request = null, array $context = []): void
            {
                $this->entries[] = ['level' => 'info', 'event' => $event, 'context' => $context];
            }

            public function warning(string $event, ?\Illuminate\Http\Request $request = null, array $context = []): void
            {
                $this->entries[] = ['level' => 'warning', 'event' => $event, 'context' => $context];
            }

            public function error(string $event, ?\Illuminate\Http\Request $request = null, array $context = []): void
            {
                $this->entries[] = ['level' => 'error', 'event' => $event, 'context' => $context];
            }
        };

        $this->app->instance(ContentFormulaEventLogger::class, $logger);

        return $logger;
    }
}
