<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Remote deploy token
    |--------------------------------------------------------------------------
    |
    | Long random string (e.g. php -r "echo bin2hex(random_bytes(32));").
    | Required for POST /system/deploy/* when not empty.
    |
    */
    'token' => env('DEPLOY_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Optional IP allowlist
    |--------------------------------------------------------------------------
    |
    | Comma-separated IPs. If empty, any IP is allowed (still needs token).
    | Example: DEPLOY_ALLOWED_IPS=203.0.113.10,198.51.100.2
    |
    */
    'allowed_ips' => env('DEPLOY_ALLOWED_IPS'),

];
