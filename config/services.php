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

    'nfse' => [
        'sefin_url' => env('NFSE_SEFIN_URL', 'https://sefin.nfse.gov.br/SefinNacional'),
        'sefin_url_homologation' => env('NFSE_SEFIN_URL_HOMOLOGATION', 'https://sefin.producaorestrita.nfse.gov.br/SefinNacional'),
        'adn_url' => env('NFSE_ADN_URL', 'https://adn.nfse.gov.br'),
        'adn_url_homologation' => env('NFSE_ADN_URL_HOMOLOGATION', 'https://adn.producaorestrita.nfse.gov.br'),
    ],

];
