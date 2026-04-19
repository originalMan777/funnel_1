<?php

return [
    'page_fallbacks' => [
        'contact' => [
            'acquisition_slug' => 'general-inquiry-acquisition',
            'service_slug' => 'general-contact',
        ],
        'consultation_request' => [
            'acquisition_slug' => 'general-inquiry-acquisition',
            'service_slug' => 'callback-request',
        ],
    ],

    'popup_lead_type_fallbacks' => [
        'buyer' => [
            'acquisition_slug' => 'buyer-acquisition',
        ],
        'seller' => [
            'acquisition_slug' => 'seller-acquisition',
        ],
        'general' => [
            'acquisition_slug' => 'general-inquiry-acquisition',
        ],
    ],

    'default_fallback' => [
        'acquisition_slug' => 'general-inquiry-acquisition',
    ],
];
