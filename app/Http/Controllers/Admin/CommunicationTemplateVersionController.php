<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Communications\PublishCommunicationTemplateVersionRequest;
use App\Http\Requests\Requests\Admin\Communications\StoreCommunicationTemplateVersionRequest;
use App\Models\CommunicationTemplate;
use App\Models\CommunicationTemplateVersion;
use App\Services\Communications\CommunicationTemplatePublishingService;
use App\Services\Communications\CommunicationTemplateVersionService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;

class CommunicationTemplateVersionController extends Controller
{
    public function __construct(
        private readonly CommunicationTemplateVersionService $versionService,
        private readonly CommunicationTemplatePublishingService $publishingService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function store(StoreCommunicationTemplateVersionRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $version = $this->versionService->createVersion(
            $template,
            $request->validatedVersionData(),
            auth()->id(),
        );

        $this->adminLogger->info(
            event: 'communication_template_updated',
            request: $request,
            entity: $template,
            entityType: 'communication_template',
            entityId: $template->id,
            outcome: 'updated',
            context: [
                'template_id' => $template->id,
                'template_key' => $template->key,
                'template_version_id' => $version->id,
                'version_number' => $version->version_number,
                'changed_fields' => ['template_version'],
            ],
        );

        return back()->with('success', 'Communication template version created.');
    }

    public function publish(PublishCommunicationTemplateVersionRequest $request, CommunicationTemplate $template, CommunicationTemplateVersion $version): RedirectResponse
    {
        abort_unless((int) $version->communication_template_id === (int) $template->id, 404);

        $this->publishingService->publish($template, $version);

        $template->refresh();
        $version->refresh();

        $this->adminLogger->info(
            event: 'communication_template_published',
            request: $request,
            entity: $template,
            entityType: 'communication_template',
            entityId: $template->id,
            outcome: 'published',
            context: [
                'template_id' => $template->id,
                'template_key' => $template->key,
                'template_version_id' => $version->id,
                'version_number' => $version->version_number,
                'current_version_id' => $template->current_version_id,
            ],
        );

        return back()->with('success', 'Communication template version published.');
    }
}
