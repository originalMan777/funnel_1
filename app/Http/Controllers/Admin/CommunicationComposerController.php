<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Communications\PreviewCommunicationComposerRequest;
use App\Http\Requests\Requests\Admin\Communications\SendCommunicationComposerRequest;
use App\Services\Communications\CommunicationComposerService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationComposerController extends Controller
{
    public function __construct(
        private readonly CommunicationComposerService $composerService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Communications/EmailComposer', [
            'defaults' => [
                'from_email' => (string) config('mail.from.address', ''),
                'from_name' => (string) config('mail.from.name', ''),
            ],
        ]);
    }

    public function preview(PreviewCommunicationComposerRequest $request): JsonResponse
    {
        return response()->json([
            'rendered' => $this->composerService->render(
                $request->validatedDraftContent(),
                $request->validatedSamplePayload(),
            ),
        ]);
    }

    public function send(SendCommunicationComposerRequest $request): RedirectResponse
    {
        $recipient = $request->validatedRecipient();
        $sender = $request->validatedSender();

        $this->composerService->send(
            draftContent: $request->validatedDraftContent(),
            samplePayload: $request->validatedSamplePayload(),
            toEmail: $recipient['to_email'],
            toName: $recipient['to_name'],
            fromEmail: $sender['from_email'],
            fromName: $sender['from_name'],
        );

        $this->adminLogger->info(
            event: 'communication_manual_email_sent',
            request: $request,
            entityType: 'manual_communication_email',
            entityId: 'manual',
            outcome: 'sent',
            context: [
                'to_email' => $recipient['to_email'],
                'has_to_name' => filled($recipient['to_name']),
                'from_email' => $sender['from_email'],
                'has_from_name' => filled($sender['from_name']),
                'subject' => $request->validated('subject'),
            ],
        );

        return back()->with('success', 'Manual email sent.');
    }
}
