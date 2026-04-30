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

    /*
    |--------------------------------------------------------------------------
    | Snap-a-Timesheet OCR — provider switch
    |--------------------------------------------------------------------------
    | Brenda 2026-04-29 killer feature: foreman snaps a photo of a paper
    | timesheet and the system extracts every employee + hours + project
    | + date into structured entries the office can confirm in one click.
    |
    | Two providers are wired — flip via OCR_PROVIDER in .env without
    | touching code:
    |   - 'gemini'    : Google Gemini 1.5 Flash. Has a generous FREE tier
    |                   (1500 requests/day). Default for demos / pre-sales.
    |   - 'anthropic' : Claude Sonnet 4.5. Paid (~$0.02/scan) but slightly
    |                   better on messy handwriting. Switch when client
    |                   approves the feature and is ready to budget.
    */
    'ocr' => [
        'provider' => env('OCR_PROVIDER', 'gemini'),
    ],

    'anthropic' => [
        'api_key'  => env('ANTHROPIC_API_KEY'),
        'model'    => env('ANTHROPIC_MODEL', 'claude-sonnet-4-5'),
        'base_url' => env('ANTHROPIC_BASE_URL', 'https://api.anthropic.com/v1'),
        'version'  => env('ANTHROPIC_VERSION', '2023-06-01'),
    ],

    'gemini' => [
        'api_key'  => env('GEMINI_API_KEY'),
        // 2026-04-30: gemini-1.5-flash was retired from the v1beta endpoint.
        // gemini-2.0-flash is the current free-tier vision model. If Google
        // rotates again, override via GEMINI_MODEL in .env without touching code.
        'model'    => env('GEMINI_MODEL', 'gemini-2.0-flash'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
    ],

];
