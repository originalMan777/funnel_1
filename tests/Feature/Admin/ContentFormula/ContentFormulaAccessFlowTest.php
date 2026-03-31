<?php

namespace Tests\Feature\Admin\ContentFormula;

use App\Models\User;
use App\Services\ContentFormula\ContentFormulaEventLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery\MockInterface;
use Tests\TestCase;

class ContentFormulaAccessFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_generator_entry_points(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_access_denied_guest'
                    && $context['action_type'] === 'authentication'
                    && $request->route()?->getName() === 'admin.content-formula.index';
            });
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_access_denied_guest'
                    && $request->route()?->getName() === 'admin.content-formula.config';
            });
        $logger->shouldReceive('warning')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_access_denied_guest'
                    && $request->route()?->getName() === 'admin.content-formula.generate';
            });

        $this->get(route('admin.content-formula.index'))
            ->assertRedirect(route('login'));

        $this->get(route('admin.content-formula.config'))
            ->assertRedirect(route('login'));

        $this->post(route('admin.content-formula.generate'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_non_admin_users_are_forbidden_from_generator_entry_points(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('warning')
            ->times(3)
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_access_denied_forbidden'
                    && $context['action_type'] === 'admin_middleware';
            });

        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get(route('admin.content-formula.index'))
            ->assertForbidden();

        $this->actingAs($user)
            ->get(route('admin.content-formula.config'))
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson(route('admin.content-formula.generate'), $this->validPayload())
            ->assertForbidden();
    }

    public function test_authorized_admin_can_reach_generator_entry_points(): void
    {
        $logger = $this->mockEventLogger();
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_access_granted'
                    && $context['action_type'] === 'index'
                    && $request->route()?->getName() === 'admin.content-formula.index';
            });
        $logger->shouldReceive('info')
            ->once()
            ->withArgs(function (string $event, $request, array $context): bool {
                return $event === 'generator_config_requested'
                    && $context['action_type'] === 'config'
                    && $request->route()?->getName() === 'admin.content-formula.config';
            });

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.content-formula.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('ContentFormula/Index')
                ->has('config.generator.allowed_result_counts')
                ->has('config.categories')
            );

        $this->actingAs($admin)
            ->getJson(route('admin.content-formula.config'))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.config.generator.default_result_count', 50);
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

    private function mockEventLogger(): MockInterface
    {
        $logger = \Mockery::mock(ContentFormulaEventLogger::class);
        $logger->shouldIgnoreMissing();

        $this->app->instance(ContentFormulaEventLogger::class, $logger);

        return $logger;
    }
}
