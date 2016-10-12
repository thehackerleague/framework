<?php

return [
    'thl_view' => [
        'name' => 'View',
        'type' => 'module',
        'providers' => [
            Thl\View\ViewServiceProvider::class
        ],
        'aliases' => [
        ],
        'depends' => [
            'thl_foundation'
        ],
    ]
];
