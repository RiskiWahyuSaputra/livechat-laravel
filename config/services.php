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

    'openclaw' => [
        'base_url' => env('OPENCLAW_BASE_URL', 'http://127.0.0.1:18789'),
        'hook_path' => env('OPENCLAW_HOOK_PATH', '/hooks/agent'),
        'hook_token' => env('OPENCLAW_HOOK_TOKEN'),
        'agent_name' => env('OPENCLAW_AGENT_NAME', 'Website AI'),
        'model' => env('OPENCLAW_MODEL', 'codex'),
        'timeout_seconds' => env('OPENCLAW_TIMEOUT_SECONDS', 30),
    ],

];
