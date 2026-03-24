<?php

namespace App\Http\Controllers\ContentFormula;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentFormula\GenerateContentFormulaRequest;
use App\Services\ContentFormula\ContentFormulaGenerator;
use App\Services\ContentFormula\ContentFormulaSessionService;
use App\Services\ContentFormula\ContentFormulaTierResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class ContentFormulaController extends Controller
{
    public function __construct(
        protected ContentFormulaGenerator $generator,
        protected ContentFormulaTierResolver $tiers,
        protected ContentFormulaSessionService $sessions,
    ) {
    }

    public function index(): Response
    {
        return Inertia::render('ContentFormula/Index', [
            'config' => $this->frontendConfig(),
        ]);
    }

    public function generate(GenerateContentFormulaRequest $request): JsonResponse
    {
        $payload = $request->normalized();
        $tier = $this->tiers->resolve($request->user());
        $action = $payload['action'];

        if ($action === 'continue') {
            $session = $this->sessions->get((string) $payload['session_id']);
            if (!$session) {
                throw ValidationException::withMessages([
                    'session_id' => 'The selected generation session is no longer available. Start a fresh generation.',
                ]);
            }
            $settings = (array) ($session['settings'] ?? []);
        } else {
            $settings = $this->buildSettings($payload, $tier);
            $session = $this->sessions->create($settings, $tier);

            if ($action === 'reset' && $payload['session_id']) {
                $previous = $this->sessions->get((string) $payload['session_id']);
                $session['reset_count'] = (int) (($previous['reset_count'] ?? 0) + 1);
            }
        }

        $result = $this->generator->generateBatch($settings, $session);
        $updatedSession = array_merge($session, $result['session']);

        if ($action === 'continue') {
            $updatedSession['continue_count'] = (int) (($session['continue_count'] ?? 0) + 1);
        }

        $this->sessions->put($updatedSession);

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
                'default_result_count' => data_get($config, 'generator.default_result_count', 50),
                'max_result_count' => data_get($config, 'generator.max_result_count', 50),
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
            'result_count' => min((int) ($payload['result_count'] ?? $tier['batch_size']), (int) $tier['batch_size']),
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
            'batch_size' => $tier['batch_size'],
            'can_continue' => !$exhausted && ($continueLimit === null || $continueCount < $continueLimit),
            'can_reset' => $resetLimit === null || $resetCount < $resetLimit,
            'remaining_continue_count' => $continueLimit === null ? null : max(0, $continueLimit - $continueCount),
            'remaining_reset_count' => $resetLimit === null ? null : max(0, $resetLimit - $resetCount),
            'requested_count' => (int) ($meta['requested_count'] ?? $tier['batch_size']),
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
}
