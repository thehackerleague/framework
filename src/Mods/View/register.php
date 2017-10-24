<?php

return [
    'mod_view' => [
        'name' => 'View',
        'type' => 'module',
        'providers' => [
            Mods\View\ViewServiceProvider::class,
            Laracasts\Flash\FlashServiceProvider::class,
            Mods\Form\FormServiceProvider::class
        ],
        'aliases' => [
        ],
        'depends' => [
            'mod_foundation'
        ],
    ]
];
