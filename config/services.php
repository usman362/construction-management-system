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
    | Groq Cloud — Snap-a-Timesheet OCR (Brenda 2026-04-29 killer feature)
    |--------------------------------------------------------------------------
    | Foreman snaps a photo of a paper timesheet → AI extracts every row
    | into structured entries the office confirms in one click.
    |
    | Provider: Groq Cloud (Llama 4 Scout 17B Vision). Truly free — no
    | credit card, no billing setup. Sign up at https://console.groq.com/keys.
    | Free tier: 30 req/min, ~14k requests/day. Famously fast (~1-3 sec).
    */
    'groq' => [
        'api_key'  => env('GROQ_API_KEY'),
        // Llama 4 Scout 17B is Groq's current vision model. If Meta/Groq
        // rotates, override via GROQ_MODEL in .env without touching code.
        'model'    => env('GROQ_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],

];
