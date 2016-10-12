<?php

return [
    'thl_backend' => [
        'name' => 'Backend',
        'type' => 'module',
        'providers' => [
            Thl\Backend\RouteServiceProvider::class,
        ],
        'aliases' => [
        ],
        'depends' => [
            'thl_theme'
        ],
        'autoload' => [
            'psr-4' => [
                'Thl\\Backend\\' => realpath(__DIR__.'/src/')
            ]
        ]
    ]
];
