<?php

return [
    'thl_theme' => [
        'name' => 'Theme',
        'type' => 'module',
        'providers' => [
            Thl\Theme\ThemeServiceProvider::class
        ],
        'aliases' => [
        ],
        'depends' => [
            'thl_view'
        ],
        'autoload' => [
            'psr-4' => [
                'Thl\\Theme\\' => realpath(__DIR__.'/src/')
            ]
        ]
    ]
];
