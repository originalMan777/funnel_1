<?php

return [
    'layout' => [
        'development_banner' => 'This website is currently under development. Some pages and features are still being finalized.',
    ],

    'home' => [
        'why_points' => [
            ['icon' => '◔', 'text' => 'Clear, honest guidance'],
            ['icon' => '◔', 'text' => 'Focused on your specific situation'],
            ['icon' => '◔', 'text' => 'Local market understanding'],
            ['icon' => '◔', 'text' => 'Strategy before action'],
            ['icon' => '◔', 'text' => 'No pressure — just direction you can trust'],
        ],
        'insights' => [
            [
                'title' => 'Buying in NJ',
                'body' => 'Know how to approach buying, what to watch for, and how to move forward with confidence.',
                'href' => '/buyers-strategy',
                'label' => 'Buyers',
                'cta' => 'See how to approach buying →',
            ],
            [
                'title' => 'Selling Tips',
                'body' => 'Learn how to position, prepare, and sell your property with a clear strategy behind you.',
                'href' => '/sellers-strategy',
                'label' => 'Sellers',
                'cta' => 'Learn how to sell strategically →',
            ],
            [
                'title' => 'Market Trends',
                'body' => 'Understand what’s happening in the market and how it impacts your next move.',
                'href' => '/blog?category=market-trends',
                'label' => 'Market',
                'cta' => 'Understand where the market is going →',
            ],
        ],
    ],

    'blog' => [
        'author_label' => 'Written by Awestruk.',
        'category_badge_classes' => [
            'food' => 'border-2 border-green-600 text-green-700 bg-white',
            'dogs' => 'border-2 border-blue-600 text-blue-700 bg-white',
            'dog' => 'border-2 border-blue-600 text-blue-700 bg-white',
            'trees' => 'border-2 border-emerald-600 text-emerald-700 bg-white',
            'tree' => 'border-2 border-emerald-600 text-emerald-700 bg-white',
            'gold' => 'border-2 border-amber-500 text-amber-700 bg-white',
            'news' => 'border-2 border-slate-600 text-slate-700 bg-white',
            'guides' => 'border-2 border-violet-600 text-violet-700 bg-white',
            'guide' => 'border-2 border-violet-600 text-violet-700 bg-white',
        ],
        'default_category_badge_class' => 'border-2 border-rose-600 text-rose-700 bg-white',
    ],
];
