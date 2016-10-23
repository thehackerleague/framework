<?php

return [
    'mod_view' => [
        'name' => 'View',
        'type' => 'module',
        'providers' => [
            Mods\View\ViewServiceProvider::class
        ],
        'aliases' => [
        ],
        'depends' => [
            'mod_foundation'
        ],
    ]
];
