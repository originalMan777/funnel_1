<?php

return [
    'enabled' => env('ANALYTICS_ENABLED', true),

    'cookies' => [
        'visitor' => env('ANALYTICS_VISITOR_COOKIE', 'nojo_analytics_visitor'),
        'session' => env('ANALYTICS_SESSION_COOKIE', 'nojo_analytics_session'),
        'minutes' => (int) env('ANALYTICS_COOKIE_MINUTES', 60 * 24 * 365 * 2),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => false,
        'same_site' => 'lax',
    ],

    'session' => [
        'inactivity_timeout_minutes' => (int) env('ANALYTICS_SESSION_INACTIVITY_TIMEOUT', 30),
        'absolute_timeout_minutes' => (int) env('ANALYTICS_SESSION_ABSOLUTE_TIMEOUT', 240),
    ],

    'ingest' => [
        'batch_limit' => (int) env('ANALYTICS_INGEST_BATCH_LIMIT', 50),
    ],

    'events' => [
        'default_types' => [
            ['event_key' => 'page.view', 'label' => 'Page View', 'category' => 'navigation'],
            ['event_key' => 'cta.impression', 'label' => 'CTA Impression', 'category' => 'engagement'],
            ['event_key' => 'cta.click', 'label' => 'CTA Click', 'category' => 'engagement'],
            ['event_key' => 'lead_box.impression', 'label' => 'Lead Box Impression', 'category' => 'lead_capture'],
            ['event_key' => 'lead_box.click', 'label' => 'Lead Box Click', 'category' => 'lead_capture'],
            ['event_key' => 'lead_form.opened', 'label' => 'Lead Form Opened', 'category' => 'lead_capture'],
            ['event_key' => 'lead_form.submitted', 'label' => 'Lead Form Submitted', 'category' => 'lead_capture'],
            ['event_key' => 'lead_form.failed', 'label' => 'Lead Form Failed', 'category' => 'lead_capture'],
            ['event_key' => 'popup.eligible', 'label' => 'Popup Eligible', 'category' => 'popup'],
            ['event_key' => 'popup.impression', 'label' => 'Popup Impression', 'category' => 'popup'],
            ['event_key' => 'popup.opened', 'label' => 'Popup Opened', 'category' => 'popup'],
            ['event_key' => 'popup.dismissed', 'label' => 'Popup Dismissed', 'category' => 'popup'],
            ['event_key' => 'popup.submitted', 'label' => 'Popup Submitted', 'category' => 'popup'],
            ['event_key' => 'tool.viewed', 'label' => 'Tool Viewed', 'category' => 'tool'],
            ['event_key' => 'tool.started', 'label' => 'Tool Started', 'category' => 'tool'],
            ['event_key' => 'tool.milestone', 'label' => 'Tool Milestone', 'category' => 'tool'],
            ['event_key' => 'tool.completed', 'label' => 'Tool Completed', 'category' => 'tool'],
            ['event_key' => 'tool.report_generated', 'label' => 'Tool Report Generated', 'category' => 'tool'],
            ['event_key' => 'form.started', 'label' => 'Form Started', 'category' => 'form'],
            ['event_key' => 'form.submitted', 'label' => 'Form Submitted', 'category' => 'form'],
            ['event_key' => 'form.failed', 'label' => 'Form Failed', 'category' => 'form'],
            ['event_key' => 'conversion.recorded', 'label' => 'Conversion Recorded', 'category' => 'conversion'],
        ],
    ],

    'catalog' => [
        'pages' => [
            'home' => ['label' => 'Home', 'category' => 'public'],
            'about' => ['label' => 'About', 'category' => 'public'],
            'services' => ['label' => 'Services', 'category' => 'public'],
            'buyers' => ['label' => 'Buyers Strategy', 'category' => 'public'],
            'sellers' => ['label' => 'Sellers Strategy', 'category' => 'public'],
            'consultation' => ['label' => 'Consultation', 'category' => 'public'],
            'consultation_request' => ['label' => 'Consultation Request', 'category' => 'public'],
            'resources' => ['label' => 'Resources', 'category' => 'public'],
            'contact' => ['label' => 'Contact', 'category' => 'public'],
            'blog' => ['label' => 'Blog', 'category' => 'public'],
        ],
        'cta_types' => [
            'primary_navigation' => 1,
            'consultation_entry' => 2,
            'content_entry' => 3,
            'lead_box_entry' => 4,
        ],
        'conversion_types' => [
            'lead_form_submission' => 1,
            'popup_submission' => 2,
            'consultation_request' => 3,
            'home_value_request' => 4,
        ],
        'ctas' => [
            'home.hero.consultation' => [
                'label' => 'Home Hero Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation',
            ],
            'home.hero.services' => [
                'label' => 'Home Hero Services',
                'cta_type_id' => 1,
                'intent_key' => 'services',
            ],
            'home.path.buyers' => [
                'label' => 'Home Buyers Strategy',
                'cta_type_id' => 1,
                'intent_key' => 'buyers_strategy',
            ],
            'home.path.sellers' => [
                'label' => 'Home Sellers Strategy',
                'cta_type_id' => 1,
                'intent_key' => 'sellers_strategy',
            ],
            'consultation.hero.request' => [
                'label' => 'Consultation Hero Request',
                'cta_type_id' => 2,
                'intent_key' => 'consultation_request',
            ],
            'consultation.hero.contact' => [
                'label' => 'Consultation Hero Contact',
                'cta_type_id' => 2,
                'intent_key' => 'contact',
            ],
            'consultation.mid.request' => [
                'label' => 'Consultation Mid Request',
                'cta_type_id' => 2,
                'intent_key' => 'consultation_request',
            ],
            'buyers_strategy.hero.consultation' => [
                'label' => 'Buyers Strategy Hero Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation',
            ],
            'buyers_strategy.hero.articles' => [
                'label' => 'Buyers Strategy Hero Articles',
                'cta_type_id' => 3,
                'intent_key' => 'buying_articles',
            ],
            'buyers_strategy.footer.consultation' => [
                'label' => 'Buyers Strategy Footer Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation',
            ],
            'sellers_strategy.hero.consultation' => [
                'label' => 'Sellers Strategy Hero Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation',
            ],
            'sellers_strategy.hero.articles' => [
                'label' => 'Sellers Strategy Hero Articles',
                'cta_type_id' => 3,
                'intent_key' => 'selling_articles',
            ],
            'sellers_strategy.footer.consultation' => [
                'label' => 'Sellers Strategy Footer Consultation',
                'cta_type_id' => 2,
                'intent_key' => 'consultation',
            ],
        ],
    ],

    'scenarios' => [
        'thresholds' => [
            'high_engagement_min_meaningful_events' => 2,
            'research_min_distinct_pages' => 2,
            'research_min_key_actions' => 3,
            'repeat_interaction_min_repeats' => 2,
        ],
        'definitions' => [
            [
                'scenario_key' => 'popup_assisted_conversion',
                'label' => 'Popup-Assisted Conversion',
                'description' => 'A session converted after observed popup submission activity.',
                'priority' => 10,
            ],
            [
                'scenario_key' => 'lead_box_assisted_conversion',
                'label' => 'Lead-Box-Assisted Conversion',
                'description' => 'A session converted through a lead-box-driven submission path.',
                'priority' => 20,
            ],
            [
                'scenario_key' => 'repeat_interaction_before_conversion',
                'label' => 'Repeat Interaction Before Conversion',
                'description' => 'A session repeated meaningful actions before converting.',
                'priority' => 30,
            ],
            [
                'scenario_key' => 'research_then_conversion',
                'label' => 'Research Then Conversion',
                'description' => 'A session visited multiple pages or took multiple key actions before converting.',
                'priority' => 40,
            ],
            [
                'scenario_key' => 'direct_cta_conversion',
                'label' => 'Direct CTA Conversion',
                'description' => 'A session moved from a CTA interaction to conversion without popup or lead-box assistance.',
                'priority' => 50,
            ],
            [
                'scenario_key' => 'direct_conversion_no_assist',
                'label' => 'Direct Conversion Without Assist',
                'description' => 'A session converted without a tracked popup, lead-box, or CTA assist pattern.',
                'priority' => 60,
            ],
            [
                'scenario_key' => 'popup_resistant_session',
                'label' => 'Popup-Resistant Session',
                'description' => 'A session saw and dismissed a popup without submitting it.',
                'priority' => 70,
            ],
            [
                'scenario_key' => 'high_engagement_no_conversion',
                'label' => 'High-Engagement No Conversion',
                'description' => 'A non-converting session still showed multiple meaningful interactions.',
                'priority' => 80,
            ],
            [
                'scenario_key' => 'low_engagement_no_conversion',
                'label' => 'Low-Engagement No Conversion',
                'description' => 'A non-converting session showed only minimal interaction.',
                'priority' => 90,
            ],
        ],
        'secondary_definitions' => [
            [
                'scenario_key' => 'popup_resistant',
                'label' => 'Popup Resistant',
                'description' => 'The session opened or saw a popup and dismissed it without submitting.',
                'priority' => 200,
            ],
            [
                'scenario_key' => 'high_engagement',
                'label' => 'High Engagement',
                'description' => 'The session showed multiple meaningful interactions regardless of final conversion.',
                'priority' => 210,
            ],
            [
                'scenario_key' => 'lead_box_assisted',
                'label' => 'Lead-Box Assisted',
                'description' => 'The session meaningfully used a lead-box path.',
                'priority' => 220,
            ],
            [
                'scenario_key' => 'repeat_interaction',
                'label' => 'Repeat Interaction',
                'description' => 'The session repeated a meaningful tracked action before ending.',
                'priority' => 230,
            ],
            [
                'scenario_key' => 'research_heavy',
                'label' => 'Research Heavy',
                'description' => 'The session explored multiple pages or took several key actions before ending.',
                'priority' => 240,
            ],
        ],
    ],

    'attribution' => [
        'fallback_referrer_medium' => 'referral',
        'fallback_direct_source' => 'direct',
        'fallback_direct_medium' => 'none',
        'conversion_touch_event_keys' => [
            'popup.submitted',
            'lead_form.submitted',
            'cta.click',
        ],
    ],

    'retention' => [
        'raw_sessions_days' => (int) env('ANALYTICS_RETENTION_RAW_SESSIONS_DAYS', 180),
        'raw_events_days' => (int) env('ANALYTICS_RETENTION_RAW_EVENTS_DAYS', 180),
        'raw_touches_days' => (int) env('ANALYTICS_RETENTION_RAW_TOUCHES_DAYS', 365),
        'keep_rollups' => true,
        'keep_conversions' => true,
        'keep_session_scenarios' => true,
        'keep_conversion_attributions' => true,
    ],
];
