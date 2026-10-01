<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // CHIP Collect (Portal Ansuran). Keys normally set in Tetapan › Integrasi (encrypted);
    // these are fallbacks. CHIP_FAKE simulates the gateway locally (ignored in production).
    'whatsapp' => [
        'url' => env('WHATSAPP_API_URL'),
        'token' => env('WHATSAPP_API_TOKEN'),
    ],

    'chip' => [
        'base_url' => env('CHIP_BASE_URL', 'https://gate.chip-in.asia/api/v1/'),
        'brand_id' => env('CHIP_BRAND_ID'),
        'secret_key' => env('CHIP_SECRET_KEY'),
        'fake' => (bool) env('CHIP_FAKE', false),
    ],

];
