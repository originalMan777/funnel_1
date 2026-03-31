<?php

namespace Tests\Integration\ContentFormula;

use App\Services\ContentFormula\ContentFormulaSessionService;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContentFormulaSessionServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Cache::flush();

        parent::tearDown();
    }

    public function test_create_persists_a_new_session_with_expected_default_state(): void
    {
        $service = app(ContentFormulaSessionService::class);

        $session = $service->create($this->settings(), ['name' => 'paid']);

        $this->assertNotEmpty($session['id']);
        $this->assertSame('paid', $session['tier']);
        $this->assertSame(0, $session['generated_count']);
        $this->assertSame(0, $session['continue_count']);
        $this->assertSame(0, $session['reset_count']);
        $this->assertFalse($session['exhausted']);
        $this->assertSame($session, $service->get($session['id']));
    }

    public function test_put_updates_existing_session_state_without_changing_identity(): void
    {
        $service = app(ContentFormulaSessionService::class);
        $session = $service->create($this->settings(), ['name' => 'paid']);

        $updated = $session;
        $updated['generated_count'] = 50;
        $updated['continue_count'] = 1;
        $updated['used_signatures'] = ['alpha'];
        $updated['usage'] = ['topics' => ['Topic 1' => 2]];

        $service->put($updated);

        $stored = $service->get($session['id']);

        $this->assertSame($session['id'], $stored['id']);
        $this->assertSame(50, $stored['generated_count']);
        $this->assertSame(1, $stored['continue_count']);
        $this->assertSame(['alpha'], $stored['used_signatures']);
        $this->assertSame(['topics' => ['Topic 1' => 2]], $stored['usage']);
    }

    public function test_unknown_or_stale_sessions_fail_safely(): void
    {
        $service = app(ContentFormulaSessionService::class);

        $this->assertFalse($service->exists('missing-session'));
        $this->assertNull($service->get('missing-session'));
    }

    private function settings(): array
    {
        return [
            'groups' => [
                'topics' => [['label' => 'Topic 1', 'stars' => 1]],
            ],
            'extra_direction' => '',
            'min_words' => 600,
            'max_words' => 1100,
            'result_count' => 25,
        ];
    }
}
