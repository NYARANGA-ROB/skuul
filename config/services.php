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
<<<<<<< HEAD
        'domain'   => env('MAILGUN_DOMAIN'),
        'secret'   => env('MAILGUN_SECRET'),
=======
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
>>>>>>> 80e3dc5 (First commit)
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
<<<<<<< HEAD
        'key'    => env('AWS_ACCESS_KEY_ID'),
=======
        'key' => env('AWS_ACCESS_KEY_ID'),
>>>>>>> 80e3dc5 (First commit)
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

<<<<<<< HEAD
    /*
    |--------------------------------------------------------------------------
    | M-Pesa Configuration
    |--------------------------------------------------------------------------
    |
    | This section contains the configuration for the M-Pesa payment gateway.
    | Make sure to set the appropriate environment variables in your .env file.
    |
    */
    'mpesa' => [
        'env' => env('MPESA_ENV', 'sandbox'), // sandbox or production
        'consumer_key' => env('MPESA_CONSUMER_KEY'),
        'consumer_secret' => env('MPESA_CONSUMER_SECRET'),
        'shortcode' => env('MPESA_SHORTCODE'),
        'passkey' => env('MPESA_PASSKEY'),
        'initiator_name' => env('MPESA_INITIATOR_NAME'),
        'initiator_password' => env('MPESA_INITIATOR_PASSWORD'),
        'cert_path' => env('MPESA_CERT_PATH', 'mpesa_cert.cer'),
        'timeout' => env('MPESA_TIMEOUT', 30),
        'callback_url' => env('MPESA_CALLBACK_URL', '/api/mpesa/callback'),
        'stk_push_callback_url' => env('MPESA_STK_PUSH_CALLBACK_URL', '/api/mpesa/callback/stk'),
        'b2c_callback_url' => env('MPESA_B2C_CALLBACK_URL', '/api/mpesa/callback/b2c'),
        'c2b_validation_url' => env('MPESA_C2B_VALIDATION_URL', '/api/mpesa/validate'),
        'c2b_confirmation_url' => env('MPESA_C2B_CONFIRMATION_URL', '/api/mpesa/confirm'),
    ],

=======
>>>>>>> 80e3dc5 (First commit)
];
