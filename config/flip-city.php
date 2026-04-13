<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Flip-City Configuration
    |--------------------------------------------------------------------------
    |
    | Ezek a beállítások vezérlik a Flip-City csomag működését.
    |
    */

    'auto_close_hours' => 3,
    'default_rate' => 1500, // HUF/óra példa
    'currency' => 'HUF',

    'routes' => [
        'prefix' => 'flip-city',
        'admin_prefix' => 'admin/flip-city',
        'middleware' => ['web'],
        'admin_middleware' => ['web', 'auth'], // Igény szerint módosítható
    ],

    'assets' => [
        'publish_path' => 'packages/flip-city',
    ],
];
