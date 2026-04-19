<?php

namespace App\Services\Communications;

class CommunicationRuntimeConfig
{
    public function __construct(
        private readonly CommunicationSettingsRepository $settings,
    ) {}

    public function transactionalProvider(): string
    {
        return (string) $this->settings->get(
            'transactional_provider',
            config('communications.transactional_provider', 'log'),
        );
    }

    public function marketingProvider(): string
    {
        return (string) $this->settings->get(
            'marketing_provider',
            config('communications.marketing.provider', 'null'),
        );
    }

    public function adminNotificationEmail(): ?string
    {
        $value = $this->settings->get(
            'admin_notification_email',
            config('communications.admin_notification_email'),
        );

        return filled($value) ? (string) $value : null;
    }

    public function adminNotificationName(): ?string
    {
        $value = $this->settings->get(
            'admin_notification_name',
            config('communications.admin_notification_name'),
        );

        return filled($value) ? (string) $value : null;
    }

    public function defaultMarketingAudienceKey(): string
    {
        return (string) $this->settings->get(
            'marketing_default_audience_key',
            config('communications.marketing.default_audience_key', 'audience.general'),
        );
    }

    public function mailchimpAudiences(): array
    {
        return (array) $this->settings->get(
            'mailchimp_audiences',
            config('communications.marketing.mailchimp.audiences', []),
        );
    }

    public function mailchimpTags(): array
    {
        return (array) $this->settings->get(
            'mailchimp_tags',
            config('communications.marketing.mailchimp.tags', []),
        );
    }

    public function mailchimpTriggers(): array
    {
        return (array) $this->settings->get(
            'mailchimp_triggers',
            config('communications.marketing.mailchimp.triggers', []),
        );
    }
}
