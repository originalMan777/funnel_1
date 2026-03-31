<?php

namespace App\Http\Controllers\ContentFormula;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentFormula\GenerateContentFormulaRequest;
use App\Services\ContentFormula\ContentFormulaEventLogger;
use App\Services\ContentFormula\ContentFormulaGenerator;
use App\Services\ContentFormula\ContentFormulaRules;
use App\Services\ContentFormula\ContentFormulaSessionService;
use App\Services\ContentFormula\ContentFormulaTierResolver;
use Illuminate\Http\JsonResponse;
use Throwable;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContentFormulaController extends Controller
{
    public function __construct(
        protected ContentFormulaGenerator $generator,
        protected ContentFormulaEventLogger $events,
        protected ContentFormulaRules $rules,
        protected ContentFormulaTierResolver $tiers,
        protected ContentFormulaSessionService $sessions,
    ) {
    }

    public function index(): Response
    {
        $this->events->info('generator_access_granted', request(), [
            'action_type' => 'index',
        ]);

        return Inertia::render('ContentFormula/Index', [
            'config' => $this->frontendConfig(),
        ]);
    }

    public function generate(GenerateContentFormulaRequest $request): JsonResponse
    {
        $startedAt = microtime(true);
        $payload = $request->normalized();
        $action = $payload['action'];
        $stage = 'resolve_tier';
        $session = null;
        $settings = [];
        $tier = [];

        try {
            $tier = $this->tiers->resolve($request->user());

            $baseContext = $this->eventContext($payload, $action, $tier, [
                'route_name' => $request->route()?->getName(),
            ]);

            $this->events->info('generator_access_granted', $request, $baseContext);
            $this->events->info($this->startedEventFor($action), $request, $baseContext);

            if ($action === 'continue') {
                $stage = 'load_session';

                try {
                    $session = $this->sessions->get((string) $payload['session_id']);
                } catch (Throwable $exception) {
                    $this->logFailure($request, 'generator_failure_session_read', $payload, $action, $tier, $stage, $exception);

                    throw $exception;
                }

                if (!$session) {
                    $this->events->warning('generator_rejected_stale_session', $request, $baseContext);

                    throw ValidationException::withMessages([
                        'session_id' => 'The selected generation session is no longer available. Start a fresh generation.',
                    ]);
                }

                if (!isset($session['id']) || !is_array($session['settings'] ?? null)) {
                    $this->events->error('generator_failure_malformed_state', $request, $this->eventContext($payload, $action, $tier, [
                        'session_id' => (string) ($payload['session_id'] ?? ''),
                        'generator_stage' => $stage,
                    ]));

                    throw ValidationException::withMessages([
                        'session_id' => 'The selected generation session is no longer available. Start a fresh generation.',
                    ]);
                }

                $settings = (array) $session['settings'];
            } else {
                $settings = $this->buildSettings($payload, $tier);
                $stage = 'persist_session';

                try {
                    $session = $this->sessions->create($settings, $tier);
                } catch (Throwable $exception) {
                    $this->logFailure($request, 'generator_failure_session_write', $payload, $action, $tier, $stage, $exception);

                    throw $exception;
                }

                if ($action === 'reset' && $payload['session_id']) {
                    $stage = 'load_session';

                    try {
                        $previous = $this->sessions->get((string) $payload['session_id']);
                    } catch (Throwable $exception) {
                        $this->logFailure($request, 'generator_failure_session_read', $payload, $action, $tier, $stage, $exception);

                        throw $exception;
                    }

                    if ($previous) {
                        $session['reset_count'] = (int) (($previous['reset_count'] ?? 0) + 1);
                    }
                }
            }

            $stage = 'generate_batch';
            $result = $this->generator->generateBatch($settings, $session);
            $updatedSession = array_merge($session, $result['session']);

            if ($action === 'continue') {
                $updatedSession['continue_count'] = (int) (($session['continue_count'] ?? 0) + 1);
            }

            $stage = 'persist_session';

            try {
                $this->sessions->put($updatedSession);
            } catch (Throwable $exception) {
                $this->logFailure($request, 'generator_failure_session_write', $payload, $action, $tier, $stage, $exception, $session, $updatedSession['id'] ?? null);

                throw $exception;
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            $this->logFailure(
                $request,
                'generator_failure_exception',
                $payload,
                $action,
                $tier,
                $stage,
                $exception,
                $session
            );

            throw $exception;
        }

        $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
        $completedContext = $this->eventContext($payload, $action, $tier, [
            'session_id' => $updatedSession['id'] ?? null,
            'previous_session_id' => $action === 'reset' ? $payload['session_id'] : null,
            'generated_row_count' => count($result['rows']),
            'exhausted' => (bool) ($updatedSession['exhausted'] ?? false),
            'continue_count' => isset($updatedSession['continue_count']) ? (int) $updatedSession['continue_count'] : null,
            'reset_count' => isset($updatedSession['reset_count']) ? (int) $updatedSession['reset_count'] : null,
            'duration_ms' => $durationMs,
        ]);

        $this->events->info($this->completedEventFor($action), $request, $completedContext);

        if ((bool) ($updatedSession['exhausted'] ?? false)) {
            $this->events->info('generator_generate_exhausted', $request, $completedContext);
        }

        return response()->json([
            'success' => true,
            'message' => $this->messageFor($action, count($result['rows']), $updatedSession['exhausted'] ?? false),
            'data' => [
                'session' => [
                    'id' => $updatedSession['id'],
                ],
                'meta' => $this->buildMeta($tier, $updatedSession, $result['meta']),
                'rows' => $result['rows'],
            ],
        ]);
    }

    public function config(): JsonResponse
    {
        $this->events->info('generator_config_requested', request(), [
            'action_type' => 'config',
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'config' => $this->frontendConfig(),
            ],
        ]);
    }

    protected function frontendConfig(): array
    {
        $config = config('content_formula', []);
        $tier = $this->tiers->resolve(request()->user());

        return [
            'generator' => [
                'allowed_result_counts' => $this->rules->allowedResultCounts(),
                'default_result_count' => $this->rules->defaultResultCount(),
                'max_result_count' => $this->rules->maxResultCount(),
                'max_selections_per_group' => $this->rules->maxSelectionsPerGroup(),
                'min_combination_threshold' => $this->rules->minimumUnlockCombinations(),
                'combination_group_keys' => $this->rules->trackedCombinationGroups(),
                'required_groups' => data_get($config, 'generator.required_groups', []),
                'tier_1_groups' => data_get($config, 'generator.tier_1_groups', []),
                'tier_2_groups' => data_get($config, 'generator.tier_2_groups', []),
                'word_range' => [
                    'min' => data_get($config, 'generator.word_range.min', 0),
                    'max' => data_get($config, 'generator.word_range.max', 2000),
                    'default_min' => data_get($config, 'generator.word_range.default_min', 800),
                    'default_max' => data_get($config, 'generator.word_range.default_max', 1400),
                ],
                'prompt_families' => [
                    'standard' => [
                        'label' => data_get($config, 'generator.prompt_families.standard.label', 'Standard Prompts'),
                        'count' => data_get($config, 'generator.prompt_families.standard.count', 2),
                    ],
                    'optimized' => [
                        'label' => data_get($config, 'generator.prompt_families.optimized.label', 'Optimized Prompts'),
                        'count' => data_get($config, 'generator.prompt_families.optimized.count', 3),
                    ],
                ],
                'tier' => $tier,
            ],
            'ui' => [
                'core_groups_open_by_default' => data_get($config, 'ui.core_groups_open_by_default', []),
                'optional_groups_open_by_default' => data_get($config, 'ui.optional_groups_open_by_default', []),
                'show_search_for_groups' => data_get($config, 'ui.show_search_for_groups', []),
                'show_select_all_for_groups' => data_get($config, 'ui.show_select_all_for_groups', []),
                'sticky_control_center' => (bool) data_get($config, 'ui.sticky_control_center', true),
                'left_panel_scrollable' => (bool) data_get($config, 'ui.left_panel_scrollable', true),
            ],
            'categories' => collect((array) data_get($config, 'categories', []))
                ->map(function ($category, $key) {
                    return [
                        'key' => $key,
                        'label' => data_get($category, 'label', $key),
                        'description' => data_get($category, 'description', ''),
                        'required' => (bool) data_get($category, 'required', false),
                        'controlled' => (bool) data_get($category, 'controlled', false),
                        'editable' => (bool) data_get($category, 'editable', false),
                        'tier' => data_get($category, 'tier', 'core'),
                        'searchable' => (bool) data_get($category, 'searchable', false),
                        'input_type' => data_get($category, 'input_type', 'checkboxes'),
                        'items' => array_values((array) data_get($category, 'items', [])),
                    ];
                })
                ->values()
                ->all(),
            'title_styles' => array_values((array) data_get($config, 'title_styles', [])),
            'prompt_styles' => (array) data_get($config, 'prompt_styles', []),
        ];
    }

    protected function buildSettings(array $payload, array $tier): array
    {
        return [
            'groups' => (array) $payload['groups'],
            'extra_direction' => (string) ($payload['extra_direction'] ?? ''),
            'min_words' => (int) $payload['min_words'],
            'max_words' => (int) $payload['max_words'],
            'result_count' => $this->rules->normalizeResultCount(
                isset($payload['result_count']) ? (int) $payload['result_count'] : null
            ),
        ];
    }

    protected function buildMeta(array $tier, array $session, array $meta): array
    {
        $continueLimit = $tier['continue_limit'];
        $resetLimit = $tier['reset_limit'];
        $continueCount = (int) ($session['continue_count'] ?? 0);
        $resetCount = (int) ($session['reset_count'] ?? 0);
        $exhausted = (bool) ($session['exhausted'] ?? false);

        return [
            'tier' => $tier['name'],
            'batch_size' => (int) ($session['settings']['result_count'] ?? $meta['requested_count'] ?? $tier['batch_size']),
            'can_continue' => !$exhausted && ($continueLimit === null || $continueCount < $continueLimit),
            'can_reset' => $resetLimit === null || $resetCount < $resetLimit,
            'remaining_continue_count' => $continueLimit === null ? null : max(0, $continueLimit - $continueCount),
            'remaining_reset_count' => $resetLimit === null ? null : max(0, $resetLimit - $resetCount),
            'requested_count' => (int) ($meta['requested_count'] ?? $session['settings']['result_count'] ?? $tier['batch_size']),
            'generated_count' => (int) ($meta['generated_count'] ?? 0),
            'estimated_core_combinations' => (int) ($meta['estimated_core_combinations'] ?? 0),
            'session_generated_count' => (int) ($session['generated_count'] ?? 0),
            'exhausted' => $exhausted,
            'word_range' => [
                'min' => (int) ($session['settings']['min_words'] ?? 0),
                'max' => (int) ($session['settings']['max_words'] ?? 0),
            ],
        ];
    }

    protected function messageFor(string $action, int $generatedCount, bool $exhausted): string
    {
        if ($action === 'continue') {
            return $exhausted
                ? "Generated {$generatedCount} more rows. This session has no unseen combinations left."
                : "Generated {$generatedCount} more content formula rows.";
        }

        if ($action === 'reset') {
            return "Started a fresh content formula session with {$generatedCount} rows.";
        }

        return "Generated {$generatedCount} content formula rows.";
    }

    protected function eventContext(array $payload, string $action, array $tier = [], array $overrides = []): array
    {
        return array_merge([
            'action_type' => $action,
            'session_id' => $payload['session_id'] ?? null,
            'previous_session_id' => $action === 'reset' ? ($payload['session_id'] ?? null) : null,
            'tier' => $tier['name'] ?? null,
            'requested_result_count' => isset($payload['result_count']) ? (int) $payload['result_count'] : null,
            'word_min' => isset($payload['min_words']) ? (int) $payload['min_words'] : null,
            'word_max' => isset($payload['max_words']) ? (int) $payload['max_words'] : null,
            'selected_group_counts' => $this->events->selectedGroupCounts($payload['groups'] ?? []),
        ], $overrides);
    }

    protected function startedEventFor(string $action): string
    {
        return match ($action) {
            'continue' => 'generator_continue_started',
            'reset' => 'generator_reset_started',
            default => 'generator_generate_started',
        };
    }

    protected function completedEventFor(string $action): string
    {
        return match ($action) {
            'continue' => 'generator_continue_completed',
            'reset' => 'generator_reset_completed',
            default => 'generator_generate_completed',
        };
    }

    protected function logFailure(
        GenerateContentFormulaRequest $request,
        string $event,
        array $payload,
        string $action,
        array $tier,
        string $stage,
        Throwable $exception,
        ?array $session = null,
        ?string $sessionIdOverride = null,
    ): void {
        $this->events->error($event, $request, $this->eventContext($payload, $action, $tier, [
            'session_id' => $sessionIdOverride ?? ($session['id'] ?? ($payload['session_id'] ?? null)),
            'continue_count' => isset($session['continue_count']) ? (int) $session['continue_count'] : null,
            'reset_count' => isset($session['reset_count']) ? (int) $session['reset_count'] : null,
            'generator_stage' => $stage,
            'exception_class' => $exception::class,
            'safe_exception_message' => $this->events->safeExceptionMessage($exception),
        ]));
    }
}
