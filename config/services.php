<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, SparkPost and others. This file provides a sane default
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'sparkpost' => [
        'secret' => env('SPARKPOST_SECRET'),
    ],

    'fonnte' => [
        'base_url' => env('FONNTE_BASE_URL', 'https://api.fonnte.com/send'),
        'token' => env('FONNTE_TOKEN'),
    ],

    'gemini' => [
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com/v1beta'),
        'api_key' => env('GEMINI_API_KEY'),
        'ocr_model' => env('GEMINI_OCR_MODEL', 'gemini-3.8-flash'),
    ],

    'veryfi' => [
        'base_url' => env('VERYFI_BASE_URL', 'https://api.veryfi.com'),
        'client_id' => env('VERYFI_CLIENT_ID'),
        'username' => env('VERYFI_USERNAME'),
        // Standard API key ("apikey username:api_key" auth scheme). If your
        // account instead uses a Bearer API key (Veryfi's newer scheme), set
        // this to that key and leave `username` blank -- VeryfiOcrClient
        // sends "Authorization: Bearer <key>" whenever username is empty.
        'api_key' => env('VERYFI_API_KEY'),
        // NOT used for the Authorization header -- Veryfi's Client Secret is
        // only for the optional X-Veryfi-Request-Signature/-Timestamp HMAC
        // request-signing headers, which VeryfiOcrClient doesn't send yet.
        // Stored here so it's available if that gets added later; safe to
        // leave unset otherwise.
        'client_secret' => env('VERYFI_CLIENT_SECRET'),
    ],

    // Which OCR provider ReceiptOcrVerifier uses to read receipts/invoices
    // for the Travel/Entertainment reimbursement forms. 'veryfi' (default) or
    // 'gemini'. Switching back to 'gemini' needs no code change -- both
    // clients implement the same OcrClientInterface.
    'ocr' => [
        'provider' => env('OCR_PROVIDER', 'veryfi'),
    ],

    // Admin WhatsApp alert (via FonnteMessenger) fired when receipt OCR keeps
    // failing (expired/invalid API key, quota exhausted, persistent timeouts).
    // The user never sees the technical reason -- this is how an admin finds
    // out without having to babysit storage/logs/laravel.log. `phone` accepts
    // one or several comma-separated numbers (08xxx or 62xxx, either works --
    // see FonnteMessenger::normalizePhone()); leave it empty to disable.
    'ocr_alert' => [
        'phone' => env('OCR_ALERT_PHONE'),
        'threshold' => env('OCR_ALERT_THRESHOLD', 3),
        'window_minutes' => env('OCR_ALERT_WINDOW_MINUTES', 15),
        'cooldown_minutes' => env('OCR_ALERT_COOLDOWN_MINUTES', 30),
    ],

];
