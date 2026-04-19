<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Communications\PreviewCommunicationTemplateRequest;
use App\Models\CommunicationTemplate;
use App\Services\Communications\CommunicationTemplateRenderer;
use App\Services\Communications\CommunicationTemplateVariableResolver;
use Illuminate\Http\JsonResponse;

class CommunicationTemplatePreviewController extends Controller
{
    public function __construct(
        private readonly CommunicationTemplateVariableResolver $variableResolver,
        private readonly CommunicationTemplateRenderer $renderer,
    ) {}

    public function store(PreviewCommunicationTemplateRequest $request, CommunicationTemplate $template): JsonResponse
    {
        $variables = $this->variableResolver->resolve(
            $request->validatedSamplePayload(),
            [
                'template' => [
                    'key' => $template->key,
                    'name' => $template->name,
                ],
            ],
        );

        return response()->json([
            'rendered' => $this->renderer->render(
                $request->validatedPreviewContent(),
                $variables,
            ),
        ]);
    }
}
