<?php

namespace Tests\Regression\FailureModes;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentFormulaSessionCoherenceRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_continue_reuses_the_same_session_and_advances_state_without_repeating_rows(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload())
            ->assertOk();

        $sessionId = $first->json('data.session.id');
        $firstIds = collect($first->json('data.rows'))->pluck('id');

        $second = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'continue',
                'session_id' => $sessionId,
            ]))
            ->assertOk();

        $secondIds = collect($second->json('data.rows'))->pluck('id');

        $this->assertSame($sessionId, $second->json('data.session.id'));
        $this->assertTrue($firstIds->intersect($secondIds)->isEmpty());
        $this->assertSame(50, $second->json('data.meta.session_generated_count'));
    }

    public function test_reset_creates_a_new_session_and_does_not_reuse_old_session_identity(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $firstSessionId = $first->json('data.session.id');

        $reset = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'reset',
                'session_id' => $firstSessionId,
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $newSessionId = $reset->json('data.session.id');

        $this->assertNotSame($firstSessionId, $newSessionId);
        $reset->assertJsonPath('data.meta.session_generated_count', 25)
            ->assertJsonPath('data.meta.word_range.min', 700)
            ->assertJsonPath('data.meta.word_range.max', 900);
    }

    public function test_continue_after_reset_requires_the_new_session_id_and_old_state_does_not_bleed_into_it(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $first = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload())
            ->assertOk();

        $oldSessionId = $first->json('data.session.id');

        $reset = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'reset',
                'session_id' => $oldSessionId,
            ]))
            ->assertOk();

        $newSessionId = $reset->json('data.session.id');

        $continuedNew = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'continue',
                'session_id' => $newSessionId,
            ]))
            ->assertOk();

        $continuedOld = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload([
                'action' => 'continue',
                'session_id' => $oldSessionId,
            ]))
            ->assertOk();

        $this->assertSame($newSessionId, $continuedNew->json('data.session.id'));
        $this->assertSame($oldSessionId, $continuedOld->json('data.session.id'));
        $this->assertSame(50, $continuedNew->json('data.meta.session_generated_count'));
        $this->assertSame(50, $continuedOld->json('data.meta.session_generated_count'));
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
}
