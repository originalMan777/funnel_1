<?php

use App\Models\LeadBox;

$slotDefinitions = [
    'home_intro' => [
        'label' => 'Home (intro)',
        'required_type' => LeadBox::TYPE_RESOURCE,
        'page_key' => 'home',
    ],
    'home_mid' => [
        'label' => 'Home (mid)',
        'required_type' => LeadBox::TYPE_SERVICE,
        'page_key' => 'home',
    ],
    'home_bottom' => [
        'label' => 'Home (bottom)',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'home',
    ],
    'blog_index_mid_lead' => [
        'label' => 'Blog index (mid)',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_index',
    ],
    'blog_post_inline_1' => [
        'label' => 'Blog post inline 1',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_show',
    ],
    'blog_post_inline_2' => [
        'label' => 'Blog post inline 2',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_show',
    ],
    'blog_post_inline_3' => [
        'label' => 'Blog post inline 3',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_show',
    ],
    'blog_post_inline_4' => [
        'label' => 'Blog post inline 4',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_show',
    ],
    'blog_post_before_related' => [
        'label' => 'Blog post before related',
        'required_type' => LeadBox::TYPE_OFFER,
        'page_key' => 'blog_show',
    ],
];

$pageSlots = [];
foreach ($slotDefinitions as $slotKey => $definition) {
    $pageSlots[$definition['page_key']][] = $slotKey;
}

return [
    'slot_definitions' => $slotDefinitions,

    'slot_keys' => array_keys($slotDefinitions),

    'page_slots' => $pageSlots,

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
