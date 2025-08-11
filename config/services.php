<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
    'xendit' => [
        'api_key' => env('XENDIT_API_KEY'),
        'for_user_id' => env('XENDIT_FOR_USER_ID'),
        'callback_url' => env('XENDIT_CALLBACK_URL'),
        'callback_token' => env('XENDIT_CALLBACK_TOKEN'),
        'currency' => env('XENDIT_DEFAULT_CURRENCY', 'IDR'),
        'country' => env('XENDIT_DEFAULT_COUNTRY', 'ID'),
        'expiry_hours' => env('XENDIT_EXPIRY_HOURS', 24),
        'cards_allow_pci' => (bool) env('XENDIT_CARDS_ALLOW_PCI', false),
    ],
];
