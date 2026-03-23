<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Generator Defaults
    |--------------------------------------------------------------------------
    |
    | Core behavior rules for the admin content formula tool.
    |
    */

    'generator' => [
        'default_result_count' => 50,
        'max_result_count' => 50,

        /*
        |--------------------------------------------------------------------------
        | Star Weights
        |--------------------------------------------------------------------------
        |
        | 1 star = active
        | 2 stars = slight emphasis
        | 3 stars = major emphasis
        |
        */

        'star_weights' => [
            1 => 1,
            2 => 2,
            3 => 4,
        ],

        /*
        |--------------------------------------------------------------------------
        | Required Core Groups
        |--------------------------------------------------------------------------
        |
        | These must always have at least one selected item.
        |
        */

        'required_groups' => [
            'topics',
            'article_types',
            'article_formats',
            'vibes',
        ],

        /*
        |--------------------------------------------------------------------------
        | Tier Rules
        |--------------------------------------------------------------------------
        |
        | Tier 1 groups are always included in generated rows if selected.
        | Tier 2 groups are used lightly and variably.
        |
        */

        'tier_1_groups' => [
            'audiences',
            'contexts',
            'reader_impacts',
        ],

        'tier_2_groups' => [
            'perspectives',
        ],

        'tier_2' => [
            'max_per_row' => 1,
            'default_include_probability' => 0.35,
        ],

        /*
        |--------------------------------------------------------------------------
        | Variation Rules
        |--------------------------------------------------------------------------
        |
        | Used by the generator to reduce ugly repetition.
        |
        */

        'variation' => [
            'prevent_exact_duplicates' => true,
            'soft_block_high_similarity_to_previous' => true,
            'soft_similarity_threshold' => 3,
            'strict_attempts' => 30,
            'max_attempts_per_row' => 80,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | UI Defaults
    |--------------------------------------------------------------------------
    |
    | Controls how the admin page should behave visually.
    |
    */

    'ui' => [
        'core_groups_open_by_default' => [
            'topics',
            'article_types',
            'article_formats',
            'vibes',
        ],

        'optional_groups_open_by_default' => [],

        'show_search_for_groups' => [
            'topics',
            'reader_impacts',
            'audiences',
            'contexts',
            'perspectives',
        ],

        'show_select_all_for_groups' => [
            'topics',
            'article_types',
            'article_formats',
            'vibes',
            'reader_impacts',
            'audiences',
            'contexts',
            'perspectives',
        ],

        'sticky_control_center' => true,
        'left_panel_scrollable' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Category Definitions
    |--------------------------------------------------------------------------
    |
    | These drive the checklists, labels, descriptions, and generator logic.
    |
    */

    'categories' => [

        'topics' => [
            'label' => 'Topic',
            'description' => 'What the article is about.',
            'required' => true,
            'controlled' => false,
            'editable' => true,
            'tier' => 'core',
            'searchable' => true,
            'items' => [
                'Buying a Home',
                'Selling a Home',
                'First-Time Buyers',
                'Home Pricing',
                'Home Value',
                'Market Trends',
                'Local Market Conditions',
                'Mortgage Options',
                'Interest Rates',
                'Down Payments',
                'Closing Costs',
                'Pre-Approval',
                'Credit & Home Buying',
                'Debt-to-Income Ratio',
                'Home Inspections',
                'Appraisals',
                'Negotiation',
                'Bidding Wars',
                'Buyer Mistakes',
                'Seller Mistakes',
                'Open Houses',
                'Home Staging',
                'Listing Strategy',
                'Luxury Homes',
                'Investment Properties',
                'Rental Properties',
                'Airbnb / Short-Term Rentals',
                'Relocation',
                'New Construction',
                'Condos',
                'Townhomes',
                'Single-Family Homes',
                'Foreclosures',
                'Short Sales',
                'Probate Sales',
                'Home Equity',
                'Refinancing',
                'Property Taxes',
                'Home Insurance',
                'HOAs',
                'Moving Process',
                'Timing the Market',
                'Neighborhood Selection',
                'School Districts',
                'Fixer-Uppers',
                'Cash Offers',
                'FSBO',
                'Real Estate Scams',
                'Contracts & Paperwork',
                'Building Wealth Through Real Estate',
            ],
        ],

        'article_types' => [
            'label' => 'Type of Article',
            'description' => 'What kind of article you want to write.',
            'required' => true,
            'controlled' => true,
            'editable' => false,
            'tier' => 'core',
            'searchable' => false,
            'items' => [
                'Problems',
                'Mistakes',
                'Trends',
                'Questions',
                'Comparisons',
                'Myths',
                'Strategies',
                'Opportunities',
                'Cases',
                'Predictions',
            ],
        ],

        'article_formats' => [
            'label' => 'Article Format',
            'description' => 'How the article is written.',
            'required' => true,
            'controlled' => true,
            'editable' => false,
            'tier' => 'core',
            'searchable' => false,
            'items' => [
                'Guide',
                'List',
                'Steps',
                'Breakdown',
                'Explanation',
                'Checklist',
                'Framework',
                'FAQ',
                'Case Study',
                'Freeform',
                'Exploration',
                'Narrative',
            ],
        ],

        'vibes' => [
            'label' => 'Vibe',
            'description' => 'How the article feels to read.',
            'required' => true,
            'controlled' => true,
            'editable' => false,
            'tier' => 'core',
            'searchable' => false,
            'items' => [
                'Optimistic',
                'Joyful',
                'Enlightening',
                'Encouraging',
                'Reassuring',
                'Motivating',
                'Empowering',
                'Calm',
                'Urgent',
                'Serious',
                'Honest',
                'Bold',
                'Eye-opening',
                'Reflective',
                'Inspirational',
            ],
        ],

        'reader_impacts' => [
            'label' => 'Reader Impact',
            'description' => 'How the reader should feel after reading.',
            'required' => false,
            'controlled' => true,
            'editable' => false,
            'tier' => 'tier_1',
            'searchable' => true,
            'items' => [
                'Informed',
                'Confident',
                'Prepared',
                'Relieved',
                'Aware',
                'Motivated',
                'Reassured',
                'Validated',
                'In Control',
                'Inspired',
                'Cautious',
                'Clear',
                'Smarter',
                'Ahead',
                'Understood',
            ],
        ],

        'audiences' => [
            'label' => 'Audience',
            'description' => 'Who the article is meant for.',
            'required' => false,
            'controlled' => false,
            'editable' => true,
            'tier' => 'tier_1',
            'searchable' => true,
            'items' => [
                'First-Time Buyers',
                'Sellers',
                'Investors',
                'Families',
                'Retirees',
                'Relocating Buyers',
                'Luxury Buyers',
                'Cash Buyers',
                'Budget-Conscious Buyers',
                'New Homeowners',
            ],
        ],

        'contexts' => [
            'label' => 'Context',
            'description' => 'The situation or conditions surrounding the article.',
            'required' => false,
            'controlled' => false,
            'editable' => true,
            'tier' => 'tier_1',
            'searchable' => true,
            'items' => [
                'In Today’s Market',
                'On a Budget',
                'Without Experience',
                'After a Setback',
                'In 2026',
                'During Uncertainty',
                'With High Interest Rates',
                'In a Competitive Market',
                'Before Making an Offer',
                'Before Listing a Home',
            ],
        ],

        'perspectives' => [
            'label' => 'Perspective',
            'description' => 'The lens or point of view behind the article.',
            'required' => false,
            'controlled' => false,
            'editable' => true,
            'tier' => 'tier_2',
            'searchable' => true,
            'items' => [
                'Philosophical',
                'Practical',
                'Strategic',
                'Personal',
                'Business-Focused',
                'Long-Term',
                'Beginner Mindset',
                'Investor Mindset',
            ],
        ],

        'extra_direction' => [
            'label' => 'Extra Direction',
            'description' => 'Any extra guidance you want the system to consider.',
            'required' => false,
            'controlled' => false,
            'editable' => true,
            'tier' => 'extra',
            'input_type' => 'text',
            'searchable' => false,
            'items' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Title Styles
    |--------------------------------------------------------------------------
    |
    | These are independent title suggestion patterns generated per row.
    |
    */

    'title_styles' => [
        [
            'key' => 'professional',
            'label' => 'Professional',
            'template' => ':topic :article_type: A :article_format',
        ],
        [
            'key' => 'direct',
            'label' => 'Direct',
            'template' => ':topic :article_type You Should Know',
        ],
        [
            'key' => 'question',
            'label' => 'Question-Driven',
            'template' => 'Are You Overlooking :topic :article_type?',
        ],
        [
            'key' => 'insight',
            'label' => 'Insight',
            'template' => 'What to Know About :topic :article_type',
        ],
        [
            'key' => 'improvement',
            'label' => 'Improvement',
            'template' => 'How to Navigate :topic :article_type',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Prompt Styles
    |--------------------------------------------------------------------------
    |
    | These are independent prompt suggestion patterns generated per row.
    |
    */

    'prompt_styles' => [
        [
            'key' => 'structured',
            'label' => 'Structured',
            'template' => 'Write a :vibe :article_format about :topic focused on :article_type.',
        ],
        [
            'key' => 'practical',
            'label' => 'Practical',
            'template' => 'Write a practical :article_format on :topic centered around :article_type.',
        ],
        [
            'key' => 'engaging',
            'label' => 'Engaging',
            'template' => 'Write an engaging article about :topic from a :article_type perspective in a :vibe style.',
        ],
        [
            'key' => 'insightful',
            'label' => 'Insightful',
            'template' => 'Write an insightful :article_format about :topic exploring :article_type.',
        ],
        [
            'key' => 'results',
            'label' => 'Results-Focused',
            'template' => 'Write a results-focused :article_format about :topic based on :article_type.',
        ],
    ],
];
