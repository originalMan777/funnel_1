<?php

namespace App\Http\Controllers\ContentFormula;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContentFormula\GenerateContentFormulaRequest;
use App\Services\ContentFormula\ContentFormulaGenerator;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\JsonResponse;

class ContentFormulaController extends Controller
{
    public function __construct(
        protected ContentFormulaGenerator $generator
    ) {
    }

    /**
     * Show the admin content formula tool page.
     */
    public function index(): Response
    {
        return Inertia::render('ContentFormula/Index', [
            'config' => $this->frontendConfig(),
        ]);
    }

    /**
     * Generate content formula rows from the selected pools.
     */
    public function generate(GenerateContentFormulaRequest $request): JsonResponse
    {
        $payload = $request->normalized();
        $results = $this->generator->generate($payload);

        return response()->json([
            'success' => true,
            'message' => 'Content ideas generated successfully.',
            'data' => $results,
        ]);
    }

    /**
     * Optional helper endpoint if you ever want to refresh config only.
     * Safe to keep even if unused.
     */
    public function config(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'config' => $this->frontendConfig(),
            ],
        ]);
    }

    /**
     * Shape config for the frontend.
     *
     * This keeps the Vue side simpler and avoids exposing unnecessary backend-only
     * settings later if you expand the config file.
     */
    protected function frontendConfig(): array
    {
        $config = config('content_formula', []);

        return [
            'generator' => [
                'default_result_count' => data_get($config, 'generator.default_result_count', 50),
                'max_result_count' => data_get($config, 'generator.max_result_count', 50),
                'star_weights' => data_get($config, 'generator.star_weights', [
                    1 => 1,
                    2 => 2,
                    3 => 4,
                ]),
                'required_groups' => data_get($config, 'generator.required_groups', []),
                'tier_1_groups' => data_get($config, 'generator.tier_1_groups', []),
                'tier_2_groups' => data_get($config, 'generator.tier_2_groups', []),
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
            'prompt_styles' => array_values((array) data_get($config, 'prompt_styles', [])),
        ];
    }
}
