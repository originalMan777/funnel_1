<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Communications\TestSendCommunicationTemplateRequest;
use App\Models\CommunicationTemplate;
use App\Services\Communications\CommunicationTemplateTestSendService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;

class CommunicationTemplateTestSendController extends Controller
{
    public function __construct(
        private readonly CommunicationTemplateTestSendService $testSendService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function store(TestSendCommunicationTemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $recipient = $request->validatedRecipient();

        $this->testSendService->sendTest(
            $template,
            $request->validatedDraftContent(),
            $request->validatedSamplePayload(),
            $recipient['to_email'],
            $recipient['to_name'] ?? null,
        );

        $this->adminLogger->info(
            event: 'communication_template_test_sent',
            request: $request,
            entity: $template,
            entityType: 'communication_template',
            entityId: $template->id,
            outcome: 'sent',
            context: [
                'template_id' => $template->id,
                'template_key' => $template->key,
                'has_current_version' => $template->current_version_id !== null,
            ],
        );

        return back()->with('success', 'Communication template test email sent.');
    }
}
