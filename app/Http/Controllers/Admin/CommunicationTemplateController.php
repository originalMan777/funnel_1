<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Requests\Admin\Communications\StoreCommunicationTemplateRequest;
use App\Http\Requests\Requests\Admin\Communications\UpdateCommunicationTemplateRequest;
use App\Models\CommunicationTemplate;
use App\Services\Communications\CommunicationTemplateService;
use App\Services\Logging\AdminActivityLogger;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationTemplateController extends Controller
{
    public function __construct(
        private readonly CommunicationTemplateService $templateService,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Admin/Communications/Templates/Index', [
            'templates' => CommunicationTemplate::query()
                ->with(['currentVersion', 'bindings'])
                ->latest('id')
                ->get()
                ->map(fn (CommunicationTemplate $template): array => [
                    'id' => $template->id,
                    'key' => $template->key,
                    'name' => $template->name,
                    'status' => $template->status,
                    'channel' => $template->channel,
                    'category' => $template->category,
                    'description' => $template->description,
                    'bindings_count' => $template->bindings->count(),
                    'current_version' => $template->currentVersion ? [
                        'id' => $template->currentVersion->id,
                        'version_number' => $template->currentVersion->version_number,
                        'is_published' => (bool) $template->currentVersion->is_published,
                    ] : null,
                ])
                ->values(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Communications/Templates/Create', [
            'bindingDefinitions' => $this->bindingDefinitions(),
        ]);
    }

    public function store(StoreCommunicationTemplateRequest $request): RedirectResponse
    {
        $template = $this->templateService->create(
            $request->validatedTemplateData(),
            $request->validatedBindings(),
            auth()->id(),
        );

        $this->adminLogger->info(
            event: 'communication_template_created',
            request: $request,
            entity: $template,
            entityType: 'communication_template',
            entityId: $template->id,
            outcome: 'created',
            context: $this->templateLogContext($template, [
                'changed_fields' => ['template', 'bindings'],
            ]),
        );

        return to_route('admin.communications.templates.edit', $template)
            ->with('success', 'Communication template created.');
    }

    public function show(CommunicationTemplate $template): Response
    {
        $template->load(['currentVersion', 'versions', 'bindings']);

        return Inertia::render('Admin/Communications/Templates/Show', [
            'template' => [
                'id' => $template->id,
                'key' => $template->key,
                'name' => $template->name,
                'status' => $template->status,
                'channel' => $template->channel,
                'category' => $template->category,
                'description' => $template->description,
                'from_name_override' => $template->from_name_override,
                'from_email_override' => $template->from_email_override,
                'reply_to_email' => $template->reply_to_email,
                'current_version_id' => $template->current_version_id,
                'current_version' => $template->currentVersion ? [
                    'id' => $template->currentVersion->id,
                    'version_number' => $template->currentVersion->version_number,
                    'subject' => $template->currentVersion->subject,
                    'preview_text' => $template->currentVersion->preview_text,
                    'headline' => $template->currentVersion->headline,
                    'html_body' => $template->currentVersion->html_body,
                    'text_body' => $template->currentVersion->text_body,
                    'sample_payload' => $template->currentVersion->sample_payload ?? [],
                    'is_published' => (bool) $template->currentVersion->is_published,
                    'published_at' => optional($template->currentVersion->published_at)?->toISOString(),
                ] : null,
                'versions' => $template->versions
                    ->sortByDesc('version_number')
                    ->values()
                    ->map(fn ($version): array => [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'subject' => $version->subject,
                        'preview_text' => $version->preview_text,
                        'headline' => $version->headline,
                        'html_body' => $version->html_body,
                        'text_body' => $version->text_body,
                        'sample_payload' => $version->sample_payload ?? [],
                        'notes' => $version->notes,
                        'is_published' => (bool) $version->is_published,
                        'published_at' => optional($version->published_at)?->toISOString(),
                    ]),
                'bindings' => $template->bindings
                    ->sortBy('priority')
                    ->values()
                    ->map(fn ($binding): array => [
                        ...$this->bindingDisplayData($binding->event_key, $binding->action_key),
                        'id' => $binding->id,
                        'event_key' => $binding->event_key,
                        'action_key' => $binding->action_key,
                        'channel' => $binding->channel,
                        'is_enabled' => (bool) $binding->is_enabled,
                        'priority' => $binding->priority,
                    ]),
            ],
            'bindingDefinitions' => $this->bindingDefinitions(),
        ]);
    }

    public function edit(CommunicationTemplate $template): Response
    {
        $template->load(['currentVersion', 'versions', 'bindings']);
        $editorVersion = $template->currentVersion
            ?? $template->versions->sortByDesc('version_number')->first();

        return Inertia::render('Admin/Communications/Templates/Edit', [
            'template' => [
                'id' => $template->id,
                'key' => $template->key,
                'name' => $template->name,
                'status' => $template->status,
                'channel' => $template->channel,
                'category' => $template->category,
                'description' => $template->description,
                'from_name_override' => $template->from_name_override,
                'from_email_override' => $template->from_email_override,
                'reply_to_email' => $template->reply_to_email,
                'current_version_id' => $template->current_version_id,
                'current_version' => $template->currentVersion ? [
                    'id' => $template->currentVersion->id,
                    'version_number' => $template->currentVersion->version_number,
                    'subject' => $template->currentVersion->subject,
                    'preview_text' => $template->currentVersion->preview_text,
                    'headline' => $template->currentVersion->headline,
                    'html_body' => $template->currentVersion->html_body,
                    'text_body' => $template->currentVersion->text_body,
                    'variables_schema' => $template->currentVersion->variables_schema ?? [],
                    'sample_payload' => $template->currentVersion->sample_payload ?? [],
                    'notes' => $template->currentVersion->notes,
                    'is_published' => (bool) $template->currentVersion->is_published,
                    'published_at' => optional($template->currentVersion->published_at)?->toISOString(),
                ] : null,
                'editor_version' => $editorVersion ? [
                    'id' => $editorVersion->id,
                    'version_number' => $editorVersion->version_number,
                    'subject' => $editorVersion->subject,
                    'preview_text' => $editorVersion->preview_text,
                    'headline' => $editorVersion->headline,
                    'html_body' => $editorVersion->html_body,
                    'text_body' => $editorVersion->text_body,
                    'variables_schema' => $editorVersion->variables_schema ?? [],
                    'sample_payload' => $editorVersion->sample_payload ?? [],
                    'notes' => $editorVersion->notes,
                    'is_published' => (bool) $editorVersion->is_published,
                    'published_at' => optional($editorVersion->published_at)?->toISOString(),
                ] : null,
                'versions' => $template->versions
                    ->sortByDesc('version_number')
                    ->values()
                    ->map(fn ($version): array => [
                        'id' => $version->id,
                        'version_number' => $version->version_number,
                        'subject' => $version->subject,
                        'preview_text' => $version->preview_text,
                        'headline' => $version->headline,
                        'text_body' => $version->text_body,
                        'notes' => $version->notes,
                        'sample_payload' => $version->sample_payload ?? [],
                        'variables_schema' => $version->variables_schema ?? [],
                        'is_published' => (bool) $version->is_published,
                        'published_at' => optional($version->published_at)?->toISOString(),
                    ]),
                'bindings' => $template->bindings
                    ->sortBy('priority')
                    ->values()
                    ->map(fn ($binding): array => [
                        ...$this->bindingDisplayData($binding->event_key, $binding->action_key),
                        'id' => $binding->id,
                        'event_key' => $binding->event_key,
                        'action_key' => $binding->action_key,
                        'channel' => $binding->channel,
                        'is_enabled' => (bool) $binding->is_enabled,
                        'priority' => $binding->priority,
                    ]),
            ],
            'bindingDefinitions' => $this->bindingDefinitions(),
        ]);
    }

    public function update(UpdateCommunicationTemplateRequest $request, CommunicationTemplate $template): RedirectResponse
    {
        $template->loadMissing('bindings');

        $before = $this->templateSnapshot($template);
        $templateData = $request->validatedTemplateData();
        $bindings = $request->validatedBindings();

        $this->templateService->update(
            $template,
            $templateData,
            $bindings,
            auth()->id(),
        );

        $template->refresh()->loadMissing('bindings');

        $changedFields = $this->changedTemplateFields($before, $this->templateSnapshot($template));

        if ($changedFields !== []) {
            $this->adminLogger->info(
                event: 'communication_template_updated',
                request: $request,
                entity: $template,
                entityType: 'communication_template',
                entityId: $template->id,
                outcome: 'updated',
                context: $this->templateLogContext($template, [
                    'changed_fields' => $changedFields,
                ]),
            );
        }

        return to_route('admin.communications.templates.edit', $template)
            ->with('success', 'Communication template updated.');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bindingDefinitions(): array
    {
        return collect(config('communication-bindings', []))
            ->map(fn (array $definition): array => [
                'event_key' => (string) ($definition['event_key'] ?? ''),
                'label' => (string) ($definition['label'] ?? $definition['event_key'] ?? ''),
                'actions' => collect($definition['actions'] ?? [])
                    ->map(fn (array $action): array => [
                        'action_key' => (string) ($action['action_key'] ?? ''),
                        'label' => (string) ($action['label'] ?? $action['action_key'] ?? ''),
                    ])
                    ->filter(fn (array $action): bool => filled($action['action_key']))
                    ->values()
                    ->all(),
            ])
            ->filter(fn (array $definition): bool => filled($definition['event_key']))
            ->values()
            ->all();
    }

    /**
     * @return array{event_label: string, action_label: string}
     */
    private function bindingDisplayData(string $eventKey, string $actionKey): array
    {
        $definition = collect($this->bindingDefinitions())
            ->firstWhere('event_key', $eventKey);

        $action = collect($definition['actions'] ?? [])
            ->firstWhere('action_key', $actionKey);

        return [
            'event_label' => (string) ($definition['label'] ?? $eventKey),
            'action_label' => (string) ($action['label'] ?? $actionKey),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function templateLogContext(CommunicationTemplate $template, array $context = []): array
    {
        return [
            'template_id' => $template->id,
            'template_key' => $template->key,
            'channel' => $template->channel,
            'status' => $template->status,
            'bindings_count' => $template->bindings()->count(),
            ...$context,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function templateSnapshot(CommunicationTemplate $template): array
    {
        return [
            'key' => $template->key,
            'name' => $template->name,
            'status' => $template->status,
            'channel' => $template->channel,
            'category' => $template->category,
            'description' => $template->description,
            'from_name_override' => $template->from_name_override,
            'from_email_override' => $template->from_email_override,
            'reply_to_email' => $template->reply_to_email,
            'bindings' => $template->bindings
                ->sortBy(fn ($binding): string => sprintf('%08d-%08d', (int) $binding->priority, (int) $binding->id))
                ->values()
                ->map(fn ($binding): array => [
                    'event_key' => $binding->event_key,
                    'action_key' => $binding->action_key,
                    'is_enabled' => (bool) $binding->is_enabled,
                    'priority' => (int) $binding->priority,
                ])
                ->all(),
        ];
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<int, string>
     */
    private function changedTemplateFields(array $before, array $after): array
    {
        return collect(array_keys($after))
            ->filter(fn (string $field): bool => ($before[$field] ?? null) !== ($after[$field] ?? null))
            ->values()
            ->all();
    }
}
