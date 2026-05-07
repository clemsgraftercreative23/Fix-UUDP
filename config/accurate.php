<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Accurate Online — API Token (Bearer + X-Api-Timestamp + X-Api-Signature)
    |--------------------------------------------------------------------------
    |
    | See Accurate "API Token" documentation (e.g. v1.0.3). OpenAPI at
    | https://account.accurate.id/open-api/json.do still lists OAuth; API Token
    | auth uses the three headers below. No X-Session-ID.
    |
    | ACCURATE_API_SIGN_MODE builds the UTF-8 string passed to HMAC-SHA256:
    | - TIMESTAMP_ONLY: timestamp
    | - METHOD_PATH_TIMESTAMP_BODY: strtoupper(M).path.timestamp.body
    | - TIMESTAMP_METHOD_PATH_BODY: timestamp.strtoupper(M).path.body
    | - APPLICATION_METHOD_PATH_TIMESTAMP_BODY: appName.strtoupper(M).path.timestamp.body
    | - APPLICATION_TIMESTAMP_METHOD_PATH_BODY: appName.timestamp.strtoupper(M).path.body
    |
    | If Accurate rejects signatures, switch sign_mode or compare with the PDF /
    | Postman pre-request script, then adjust here.
    |
    */

    'host' => rtrim(env('ACCURATE_API_HOST', 'https://zeus.accurate.id'), '/'),

    'token' => env('ACCURATE_API_TOKEN', ''),

    // Explicit application signature key (required for app-bound signature).
    'signature_key' => env('ACCURATE_API_SIGNATURE_KEY', ''),

    'signing_secret' => env('ACCURATE_API_SIGNING_SECRET', ''),

    'application_name' => env('ACCURATE_API_APPLICATION_NAME', 'UUDP New'),

    'timezone' => env('ACCURATE_API_SIGN_TIMEZONE', 'UTC'),

    'sign_mode' => env('ACCURATE_API_SIGN_MODE', 'APPLICATION_METHOD_PATH_TIMESTAMP_BODY'),

    'timeout' => (int) env('ACCURATE_API_TIMEOUT', 60),

];
