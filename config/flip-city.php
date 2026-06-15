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
    'billing_enabled' => env('FLIPCITY_BILLING_ENABLED', false),
    'companion_price' => 500, // Kísérő/fő ára
    'profile_qr_print_text' => 'Kérjük, mutassa be ezt a kódot a belépéshez!',
    'show_profile_booking' => false,
    'profile_qr_print_enabled' => true,

    'routes' => [
        'prefix' => 'flip-city',
        'admin_prefix' => 'flip-city',
        'middleware' => ['web'],
        'admin_middleware' => ['web', 'auth:admin'],
    ],

    'assets' => [
        'publish_path' => 'packages/flip-city',
    ],

    /*
    |--------------------------------------------------------------------------
    | Számla Agent Beállítások
    |--------------------------------------------------------------------------
    |
    | A kboss/szamlaagent_v2 csomaghoz szükséges hitelesítési adatok.
    |
    */
    'invoice' => [
        'api_key' => env('FLIPCITY_INVOICE_API_KEY', 'jvi5i2q6i6twb3m4i5ete9s8ew6aeyfjk5achcc5n5'),
        'email' => env('FLIPCITY_INVOICE_EMAIL', 'adam.kerekes25@gmail.com'),
        'password' => env('FLIPCITY_INVOICE_PASSWORD', 'R6vegas2'),
        'name' => env('FLIPCITY_INVOICE_NAME', 'Kerekes Ádám'),
        'log_path' => env('FLIPCITY_INVOICE_LOG_PATH', storage_path('logs/szamlaagent.log')),
    ],
];
