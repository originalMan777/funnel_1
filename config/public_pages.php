<?php

return [
    'excluded_route_prefixes' => [
        'admin.',
    ],

    'excluded_route_names' => [
        'dashboard',
        'login',
        'register',
    ],

    'route_name_keys' => [
        'home' => 'home',
        'about' => 'about',
        'services' => 'services',
        'buyers.strategy' => 'buyers',
        'sellers.strategy' => 'sellers',
        'consultation' => 'consultation',
        'resources' => 'resources',
        'contact' => 'contact',
        'qo.show' => 'qo',
    ],

    'route_prefix_keys' => [
        'blog.' => 'blog',
    ],

    'path_keys' => [
        '/' => 'home',
        '' => 'home',
        'about' => 'about',
        'services' => 'services',
        'buyers-strategy' => 'buyers',
        'sellers-strategy' => 'sellers',
        'consultation' => 'consultation',
        'resources' => 'resources',
        'contact' => 'contact',
    ],

    'path_prefix_keys' => [
        'blog' => 'blog',
        'q/' => 'qo',
    ],
];
