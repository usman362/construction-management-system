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
    | Three providers are wired — flip via OCR_PROVIDER in .env without
    | touching code:
    |   - 'groq'      : Meta Llama 4 Scout 17B vision via Groq Cloud. TRULY
    |                   FREE — no billing setup, no credit card. Just sign up
    |                   at console.groq.com. 30 req/min free tier. Default.
    |   - 'gemini'    : Google Gemini 2.0 Flash. Free tier exists but
    |                   sometimes Google Cloud nags for billing setup.
    |   - 'anthropic' : Claude Sonnet 4.5. Paid (~$0.02/scan) but best
    |                   handwriting recognition. Production upgrade path.
    */
    'ocr' => [
        'provider' => env('OCR_PROVIDER', 'groq'),
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

    'groq' => [
        'api_key'  => env('GROQ_API_KEY'),
        // Llama 4 Scout 17B is Groq's current vision model. If Meta/Groq
        // rotates, override via GROQ_MODEL in .env. Other vision-capable
        // options if needed: llama-3.2-90b-vision-preview (older).
        'model'    => env('GROQ_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct'),
        'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
    ],

];
