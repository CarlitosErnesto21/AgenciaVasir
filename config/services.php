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

    'wompi' => [
        // Configuración para Wompi El Salvador (OAuth 2.0)
        'client_id' => env('WOMPI_CLIENT_ID'),
        'client_secret' => env('WOMPI_CLIENT_SECRET'),
        'sandbox' => env('WOMPI_SANDBOX', true),
        'auth_url' => env('WOMPI_AUTH_URL', 'https://id.wompi.sv/connect/token'),
        'base_url' => env('WOMPI_BASE_URL', 'https://api.wompi.sv'),
        'audience' => env('WOMPI_AUDIENCE', 'wompi_api'),

        // Claves antiguas para compatibilidad (por si cambias a Wompi Colombia)
        'public_key' => env('WOMPI_PUBLIC_KEY'),
        'private_key' => env('WOMPI_PRIVATE_KEY'),
        'widget_url' => env('WOMPI_SANDBOX', true)
            ? 'https://checkout.wompi.co/widget.js'
            : 'https://checkout.wompi.co/widget.js',
    ],];
