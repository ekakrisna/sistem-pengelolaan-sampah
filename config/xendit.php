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

    'webhook' => [
        'token' => env('XENDIT_WEBHOOK_TOKEN', ''),
        'hmac_secret'     => env('XENDIT_WEBHOOK_HMAC_SECRET', ''),
        'signature_header' => env('XENDIT_WEBHOOK_SIGNATURE_HEADER', 'x-endpoint-signature-hmac-sha256'),
        'idempotency' => [
            'enabled'     => true,
            'ttl'         => 1800,
            'cache_store' => env('XENDIT_WEBHOOK_CACHE_STORE', null),
        ],
    ],

    'logging' => [
        'enabled'     => env('XENDIT_LOG_ENABLED', true),
        'channel'     => env('XENDIT_LOG_CHANNEL', 'xendit'),
        'level'       => env('XENDIT_LOG_LEVEL', 'info'),
        'log_body'    => env('XENDIT_LOG_BODY', false),
        'max_length'  => env('XENDIT_LOG_MAX', 4000),
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
