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
        // Whisper-large-v3 transcription endpoint (audio → text). Used by
        // the WhatsApp / SMS bot when foremen send voice notes.
        'whisper_model' => env('GROQ_WHISPER_MODEL', 'whisper-large-v3'),
    ],

    /*
    | --------------------------------------------------------------------------
    | Twilio (WhatsApp + SMS bot — Brenda Phase 5, 2026-05-12)
    | --------------------------------------------------------------------------
    | Inbound webhook routes to TwilioWebhookController. Outbound replies use
    | TwiML XML — no Twilio SDK needed for that path. To send proactive
    | messages (not just reply to incoming) we'd need the Twilio PHP SDK,
    | but the MVP only replies inline so the SDK isn't a dependency.
    |
    | TWILIO_FROM is the registered "from" — Twilio WhatsApp sandbox uses
    | "whatsapp:+14155238886" by default. For SMS, a regular Twilio number.
    |
    | TWILIO_VALIDATE_SIGNATURE=true (default) enforces X-Twilio-Signature
    | verification on incoming webhooks. Flip to false locally for ngrok
    | testing where the signature doesn't match.
    */
    'twilio' => [
        'sid'                => env('TWILIO_ACCOUNT_SID'),
        'auth_token'         => env('TWILIO_AUTH_TOKEN'),
        'from'               => env('TWILIO_FROM'),
        'validate_signature' => env('TWILIO_VALIDATE_SIGNATURE', true),
    ],

];
