<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentFormulaGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_continue_uses_same_session_and_does_not_repeat_structural_rows(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $firstResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->payload([
                'action' => 'generate',
                'result_count' => 2,
            ]))
            ->assertOk();

        $sessionId = $firstResponse->json('data.session.id');
        $firstSummaries = collect($firstResponse->json('data.rows'))->pluck('summary');

        $continueResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->payload([
                'action' => 'continue',
                'session_id' => $sessionId,
                'result_count' => 2,
            ]))
            ->assertOk();

        $continueRows = collect($continueResponse->json('data.rows'));
        $continueSummaries = $continueRows->pluck('summary');

        $this->assertSame($sessionId, $continueResponse->json('data.session.id'));
        $this->assertTrue($firstSummaries->intersect($continueSummaries)->isEmpty());
        $this->assertTrue($continueResponse->json('data.meta.can_reset'));
    }

    public function test_reset_creates_a_new_session_id_and_keeps_word_range_settings(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $firstResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->payload([
                'action' => 'generate',
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $newSessionResponse = $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->payload([
                'action' => 'reset',
                'session_id' => $firstResponse->json('data.session.id'),
                'min_words' => 700,
                'max_words' => 900,
            ]))
            ->assertOk();

        $this->assertNotSame(
            $firstResponse->json('data.session.id'),
            $newSessionResponse->json('data.session.id')
        );
        $this->assertSame(700, $newSessionResponse->json('data.meta.word_range.min'));
        $this->assertSame(900, $newSessionResponse->json('data.meta.word_range.max'));
    }

    public function test_invalid_word_ranges_are_rejected(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin)
            ->postJson(route('admin.content-formula.generate'), $this->payload([
                'min_words' => 1200,
                'max_words' => 400,
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['min_words']);
    }

    private function payload(array $overrides = []): array
    {
        return array_replace_recursive([
            'action' => 'generate',
            'result_count' => 3,
            'min_words' => 600,
            'max_words' => 1100,
            'groups' => [
                'topics' => [
                    ['label' => 'Buying a Home', 'stars' => 1],
                    ['label' => 'Selling a Home', 'stars' => 2],
                ],
                'article_types' => [
                    ['label' => 'Problems', 'stars' => 1],
                    ['label' => 'Strategies', 'stars' => 2],
                ],
                'article_formats' => [
                    ['label' => 'Guide', 'stars' => 1],
                    ['label' => 'Checklist', 'stars' => 2],
                ],
                'vibes' => [
                    ['label' => 'Honest', 'stars' => 1],
                    ['label' => 'Encouraging', 'stars' => 2],
                ],
                'reader_impacts' => [
                    ['label' => 'Prepared', 'stars' => 1],
                ],
                'audiences' => [
                    ['label' => 'First-Time Buyers', 'stars' => 1],
                ],
                'contexts' => [
                    ['label' => 'In Today’s Market', 'stars' => 1],
                ],
            ],
        ], $overrides);
    }
}
