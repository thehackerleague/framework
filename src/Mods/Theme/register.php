<?php

return [
    'mod_theme' => [
        'name' => 'Theme',
        'type' => 'module',
        'providers' => [
            Mods\Theme\ThemeServiceProvider::class
        ],
        'aliases' => [
        ],
        'depends' => [
            'mod_view'
        ],
        'autoload' => [
        ]
    ]
];
