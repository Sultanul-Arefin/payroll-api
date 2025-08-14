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

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

'taxbandits' => [
    'auth_url' => env('TAXBANDITS_AUTH_URL'),
    'api_url' => env('TAXBANDITS_API_URL'),  // Add this line
    'client_id' => env('TAXBANDITS_CLIENT_ID'),
    'client_secret' => env('TAXBANDITS_CLIENT_SECRET'),
    'user_token' => env('TAXBANDITS_USER_TOKEN'),
    'business_id' => env('TAXBANDITS_BUSINESS_ID'),
],

   
];
