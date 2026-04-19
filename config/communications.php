<?php

return [

    'transactional_provider' => env('COMMUNICATIONS_TRANSACTIONAL_PROVIDER', 'log'),

    'queue_connection' => env('COMMUNICATIONS_QUEUE_CONNECTION', env('QUEUE_CONNECTION', 'database')),

    'queue' => env('COMMUNICATIONS_QUEUE', 'communications'),

    'retry_tries' => (int) env('COMMUNICATIONS_RETRY_TRIES', 3),

    'retry_backoff_seconds' => array_filter(array_map(
        static fn (string $value): int => (int) trim($value),
        explode(',', (string) env('COMMUNICATIONS_RETRY_BACKOFF_SECONDS', '60,300,900')),
    )),

    'processing_timeout_seconds' => (int) env('COMMUNICATIONS_PROCESSING_TIMEOUT_SECONDS', 300),

    'admin_notification_email' => env('COMMUNICATIONS_ADMIN_NOTIFICATION_EMAIL'),

    'admin_notification_name' => env('COMMUNICATIONS_ADMIN_NOTIFICATION_NAME', env('APP_NAME')),

    'marketing' => [
        'provider' => env('COMMUNICATIONS_MARKETING_PROVIDER', 'null'),
        'default_audience_key' => env('COMMUNICATIONS_MARKETING_DEFAULT_AUDIENCE_KEY', 'audience.general'),

        'mailchimp' => [
            'audiences' => [
                'audience.general' => env('MAILCHIMP_AUDIENCE_GENERAL_ID'),
                'audience.consultation' => env('MAILCHIMP_AUDIENCE_CONSULTATION_ID', env('MAILCHIMP_AUDIENCE_GENERAL_ID')),
            ],

            'tags' => [
                'tag.contact.requested' => env('MAILCHIMP_TAG_CONTACT_REQUESTED', 'contact_requested'),
                'tag.consultation.requested' => env('MAILCHIMP_TAG_CONSULTATION_REQUESTED', 'consultation_requested'),
                'tag.lead.created' => env('MAILCHIMP_TAG_LEAD_CREATED', 'lead_created'),
                'tag.popup.submitted' => env('MAILCHIMP_TAG_POPUP_SUBMITTED', 'popup_submitted'),
            ],

            'triggers' => [
                'trigger.contact.requested' => [
                    'audience_key' => 'audience.general',
                    'tags' => [env('MAILCHIMP_TRIGGER_TAG_CONTACT_REQUESTED', 'automation_contact_requested')],
                ],
                'trigger.consultation.requested' => [
                    'audience_key' => 'audience.consultation',
                    'tags' => [env('MAILCHIMP_TRIGGER_TAG_CONSULTATION_REQUESTED', 'automation_consultation_requested')],
                ],
                'trigger.popup.submitted' => [
                    'audience_key' => 'audience.general',
                    'tags' => [env('MAILCHIMP_TRIGGER_TAG_POPUP_SUBMITTED', 'automation_popup_submitted')],
                ],
            ],
        ],
    ],

    'postmark' => [
        'mailer' => env('COMMUNICATIONS_POSTMARK_MAILER', 'postmark'),
    ],

    'log' => [
        'mailer' => env('COMMUNICATIONS_LOG_MAILER', 'log'),
    ],

];
