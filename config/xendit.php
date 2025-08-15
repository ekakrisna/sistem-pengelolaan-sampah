<?php

return [
    'base_url'    => env('XENDIT_BASE_URL', 'https://api.xendit.co'),
    'api_key'     => env('XENDIT_API_KEY'),
    'api_version' => env('XENDIT_API_VERSION', '2024-11-11'),
    'timeout'     => env('XENDIT_TIMEOUT', 15),
    'retry'       => [
        'times' => env('XENDIT_RETRY_TIMES', 2),
        'sleep' => env('XENDIT_RETRY_SLEEP', 200),
    ],

    // === Logging options ===
    'logging' => [
        'enabled'     => env('XENDIT_LOG_ENABLED', true),
        'channel'     => env('XENDIT_LOG_CHANNEL', 'xendit'), // gunakan channel khusus
        'level'       => env('XENDIT_LOG_LEVEL', 'info'),
        'log_body'    => env('XENDIT_LOG_BODY', false), // off by default untuk keamanan
        'max_length'  => env('XENDIT_LOG_MAX', 4000),   // truncate payload/response
        'mask_fields' => [
            'card_number',
            'expiry_month',
            'expiry_year',
            'cvv',
            'account_number',
            'phone',
            'email',
            'authorization',
            'api_key',
            'token',
            'secret',
        ],
    ],
];
