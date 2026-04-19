<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Logging\AdminActivityLogger;
use App\Services\Communications\CommunicationRuntimeConfig;
use App\Services\Communications\CommunicationSettingsRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CommunicationSettingsController extends Controller
{
    public function __construct(
        private readonly CommunicationSettingsRepository $settings,
        private readonly CommunicationRuntimeConfig $runtimeConfig,
        private readonly AdminActivityLogger $adminLogger,
    ) {}

    public function index(): Response
    {
        $defaults = $this->settings->defaults();

        return Inertia::render('Admin/Communications/Settings', [
            'settings' => [
                'transactional_provider' => $this->runtimeConfig->transactionalProvider(),
                'marketing_provider' => $this->runtimeConfig->marketingProvider(),
                'admin_notification_email' => $this->runtimeConfig->adminNotificationEmail(),
                'admin_notification_name' => $this->runtimeConfig->adminNotificationName(),
                'marketing_default_audience_key' => $this->runtimeConfig->defaultMarketingAudienceKey(),
                'mailchimp_audiences' => $this->normalizeKeyValueMap($this->runtimeConfig->mailchimpAudiences()),
                'mailchimp_tags' => $this->normalizeKeyValueMap($this->runtimeConfig->mailchimpTags()),
                'mailchimp_triggers' => $this->normalizeTriggerMap($this->runtimeConfig->mailchimpTriggers()),
            ],
            'providerStatus' => [
                'postmark_configured' => filled(config('services.postmark.key')),
                'mailchimp_configured' => filled(config('services.mailchimp.api_key')) && filled(config('services.mailchimp.server_prefix')),
            ],
            'defaults' => [
                'transactional_provider' => (string) $defaults['transactional_provider'],
                'marketing_provider' => (string) $defaults['marketing_provider'],
                'admin_notification_email' => $defaults['admin_notification_email'],
                'admin_notification_name' => $defaults['admin_notification_name'],
                'marketing_default_audience_key' => (string) $defaults['marketing_default_audience_key'],
            ],
            'options' => [
                'transactional_providers' => ['log', 'postmark'],
                'marketing_providers' => ['null', 'mailchimp'],
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'transactional_provider' => ['required', 'string', 'in:log,postmark'],
            'marketing_provider' => ['required', 'string', 'in:null,mailchimp'],
            'admin_notification_email' => ['nullable', 'email', 'max:255'],
            'admin_notification_name' => ['nullable', 'string', 'max:255'],
            'marketing_default_audience_key' => ['required', 'string', 'max:255'],
            'mailchimp_audiences' => ['array'],
            'mailchimp_audiences.*.key' => ['required', 'string', 'max:255'],
            'mailchimp_audiences.*.value' => ['nullable', 'string', 'max:255'],
            'mailchimp_tags' => ['array'],
            'mailchimp_tags.*.key' => ['required', 'string', 'max:255'],
            'mailchimp_tags.*.value' => ['nullable', 'string', 'max:255'],
            'mailchimp_triggers' => ['array'],
            'mailchimp_triggers.*.key' => ['required', 'string', 'max:255'],
            'mailchimp_triggers.*.audience_key' => ['required', 'string', 'max:255'],
            'mailchimp_triggers.*.tags' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->settings->putMany([
            'transactional_provider' => $validated['transactional_provider'],
            'marketing_provider' => $validated['marketing_provider'],
            'admin_notification_email' => $validated['admin_notification_email'] ?? null,
            'admin_notification_name' => $validated['admin_notification_name'] ?? null,
            'marketing_default_audience_key' => $validated['marketing_default_audience_key'],
            'mailchimp_audiences' => $this->toKeyValueMap($validated['mailchimp_audiences'] ?? []),
            'mailchimp_tags' => $this->toKeyValueMap($validated['mailchimp_tags'] ?? []),
            'mailchimp_triggers' => $this->toTriggerMap($validated['mailchimp_triggers'] ?? []),
        ]);

        $this->adminLogger->info(
            event: 'communication_settings_updated',
            request: $request,
            entityType: 'communication_settings',
            entityId: 'global',
            outcome: 'updated',
            context: [
                'transactional_provider' => $validated['transactional_provider'],
                'marketing_provider' => $validated['marketing_provider'],
                'admin_notification_email_configured' => filled($validated['admin_notification_email'] ?? null),
                'marketing_default_audience_key' => $validated['marketing_default_audience_key'],
                'mailchimp_audience_count' => count($validated['mailchimp_audiences'] ?? []),
                'mailchimp_tag_count' => count($validated['mailchimp_tags'] ?? []),
                'mailchimp_trigger_count' => count($validated['mailchimp_triggers'] ?? []),
            ],
        );

        return back()->with('success', 'Communication settings saved.');
    }

    private function normalizeKeyValueMap(array $map): array
    {
        return collect($map)
            ->map(fn ($value, $key) => [
                'key' => (string) $key,
                'value' => filled($value) ? (string) $value : '',
            ])
            ->values()
            ->all();
    }

    private function normalizeTriggerMap(array $map): array
    {
        return collect($map)
            ->map(fn ($value, $key) => [
                'key' => (string) $key,
                'audience_key' => (string) ($value['audience_key'] ?? ''),
                'tags' => implode(', ', array_filter((array) ($value['tags'] ?? []))),
            ])
            ->values()
            ->all();
    }

    private function toKeyValueMap(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn (array $row) => [
                trim((string) $row['key']) => trim((string) ($row['value'] ?? '')),
            ])
            ->all();
    }

    private function toTriggerMap(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn (array $row) => [
                trim((string) $row['key']) => [
                    'audience_key' => trim((string) $row['audience_key']),
                    'tags' => collect(explode(',', (string) ($row['tags'] ?? '')))
                        ->map(fn (string $tag) => trim($tag))
                        ->filter()
                        ->values()
                        ->all(),
                ],
            ])
            ->all();
    }
}
