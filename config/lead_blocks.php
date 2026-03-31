<?php

return [
    'slot_keys' => [
        'home_intro',
        'home_mid',
        'home_bottom',
    ],

    'page_slots' => [
        'home' => ['home_intro', 'home_mid', 'home_bottom'],
    ],

    'blog' => [
        'blog_index_mid_lead',
    ],


    'types' => [
        'resource',
        'service',
        'offer',
    ],

    'statuses' => [
        'draft',
        'active',
        'inactive',
    ],

    'icons' => [
        // Keep curated + small. Add only as needed.
        'book-open',
        'download',
        'sparkles',
        'shield-check',
        'clock',
        'message-square',
        'phone',
        'check-circle-2',
    ],

    'resource' => [
        'visual_presets' => [
            'default',
        ],
    ],


];
