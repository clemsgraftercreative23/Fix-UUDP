<?php

namespace App\Services\Accurate;

use DateTime;
use DateTimeZone;
use Illuminate\Support\Facades\Log;

class AccurateApiTokenClient
{
    /** @var string */
    protected $debugSessionId = '566b62';

    /** @var string */
    protected $debugLogPath = 'debug-566b62.log';

    /** @var string */
    protected $host;

    /** @var string */
    protected $token;

    /** @var string */
    protected $signingSecret;

    /** @var string */
    protected $signatureKey;

    /** @var string */
    protected $timezone;

    /** @var string */
    protected $signMode;

    /** @var string */
    protected $applicationName;

    /** @var int */
    protected $timeout;

    public function __construct()
    {
        $this->host = (string) config('accurate.host', 'https://zeus.accurate.id');
        if ($this->host === '') {
            $this->host = (string) getenv('ACCURATE_API_HOST');
        }

        $this->token = trim((string) config('accurate.token', ''));
        if ($this->token === '') {
            $this->token = trim((string) getenv('ACCURATE_API_TOKEN'));
        }

        $this->signingSecret = trim((string) config('accurate.signing_secret', ''));
        if ($this->signingSecret === '') {
            $this->signingSecret = trim((string) getenv('ACCURATE_API_SIGNING_SECRET'));
        }

        // Signature key must come from Accurate Application credentials.
        // Do not silently fallback to signing secret because it can produce
        // a valid HMAC format with the wrong key and endless 200+error-body responses.
        $this->signatureKey = trim((string) config('accurate.signature_key', ''));
        if ($this->signatureKey === '') {
            $this->signatureKey = trim((string) getenv('ACCURATE_API_SIGNATURE_KEY'));
        }

        $this->timezone = (string) config('accurate.timezone', 'UTC');
        $this->signMode = (string) config('accurate.sign_mode', 'TIMESTAMP_ONLY');
        $this->applicationName = trim((string) config('accurate.application_name', 'UUDP New'));
        if ($this->applicationName === '') {
            $this->applicationName = trim((string) getenv('ACCURATE_API_APPLICATION_NAME'));
        }
        if ($this->applicationName === '') {
            // Accurate app signature is required for this tenant.
            $this->applicationName = 'UUDP New';
        }
        $this->timeout = (int) config('accurate.timeout', 60);
    }

    // --- Debug helpers (safe to call in non-production) ---
    public function getApplicationName()
    {
        return $this->applicationName;
    }

    public function getSignMode()
    {
        return $this->signMode;
    }

    public function debugSigningKeys()
    {
        return $this->buildSigningKeys();
    }

    public function debugBuildSignString($method, $pathWithQuery, $timestampString, $rawBody)
    {
        return $this->buildSignString($method, $pathWithQuery, $timestampString, $rawBody);
    }

    public function debugMaskKey($key)
    {
        return $this->maskKey($key);
    }

    /**
     * Whether API Token credentials are configured (non-empty).
     *
     * @return bool
     */
    public function isConfigured()
    {
        return $this->token !== '' && $this->signatureKey !== '';
    }

    /**
     * @return string[]
     */
    public function configurationErrorMessages()
    {
        $errors = [];
        if ($this->token === '') {
            $errors[] = 'ACCURATE_API_TOKEN is not set.';
        }
        if ($this->signatureKey === '') {
            $errors[] = 'ACCURATE_API_SIGNATURE_KEY is not set (required for application signature UUDP New).';
        }

        return $errors;
    }

    /**
     * X-Api-Timestamp value in ISO8601 UTC format (Postman toISOString style).
     *
     * @return string
     */
    public function buildTimestampString()
    {
        $dt = new DateTime('now', new DateTimeZone($this->timezone));
        $dt->setTimezone(new DateTimeZone('UTC'));

        // Example: 2026-05-05T08:33:12.123Z
        return $dt->format('Y-m-d\TH:i:s.v\Z');
    }

    /**
     * @param string $method GET|POST|PUT|DELETE
     * @param string $pathWithQuery e.g. /accurate/api/department/list.do?page=1
     * @param string $timestampString same as X-Api-Timestamp header
     * @param string $rawBody exact request body (empty for GET)
     * @return string Base64-encoded HMAC-SHA256
     */
    public function buildSignature($method, $pathWithQuery, $timestampString, $rawBody, $hmacKey = null)
    {
        $method = strtoupper($method);
        $pathWithQuery = $this->normalizePathForSigning($pathWithQuery);
        $rawBody = (string) $rawBody;
        $hmacKey = $hmacKey === null ? $this->signingSecret : (string) $hmacKey;

        $signString = $this->buildSignString($method, $pathWithQuery, $timestampString, $rawBody);

        return base64_encode(hash_hmac('sha256', $signString, $hmacKey, true));
    }

    /**
     * Build the canonical string used for signing based on current signMode.
     *
     * @return string
     */
    protected function buildSignString($method, $pathWithQuery, $timestampString, $rawBody)
    {
        switch ($this->signMode) {
            case 'TIMESTAMP_ONLY':
                return $timestampString;
            case 'TIMESTAMP_METHOD_PATH_BODY':
                return $timestampString.$method.$pathWithQuery.$rawBody;
            case 'APPLICATION_TIMESTAMP_METHOD_PATH_BODY':
                return $this->applicationName.$timestampString.$method.$pathWithQuery.$rawBody;
            case 'APPLICATION_METHOD_PATH_TIMESTAMP_BODY':
                return $this->applicationName.$method.$pathWithQuery.$timestampString.$rawBody;
            case 'METHOD_PATH_TIMESTAMP_BODY':
            default:
                return $method.$pathWithQuery.$timestampString.$rawBody;
        }
    }

    /**
     * Mask a key for logging so secrets aren't fully exposed.
     */
    protected function maskKey($key)
    {
        $k = (string) $key;
        $len = strlen($k);
        if ($len <= 8) {
            return substr($k, 0, 1) . str_repeat('*', max(0, $len - 2)) . substr($k, -1);
        }
        return substr($k, 0, 4) . '...' . substr($k, -4);
    }

    /**
     * @param string $method
     * @param string $pathWithQuery path starting with /accurate/ or full URL
     * @param string|null $rawBody for POST/PUT
     * @return array{ok:bool,status:int,body:string,error:?string}
     */
    public function request($method, $pathWithQuery, $rawBody = null)
    {
        $configuredSignMode = $this->signMode;
        // #region agent log
        Log::error('DBG566b62 AccurateApiTokenClient request entry', [
            'method' => strtoupper((string) $method),
            'path' => (string) $pathWithQuery,
            'sign_mode' => (string) $configuredSignMode,
            'application_name' => (string) $this->applicationName,
        ]);
        // #endregion
        if (!$this->isConfigured()) {
            // #region agent log
            $this->debugLog('pre-fix', 'H3', 'AccurateApiTokenClient.php:isConfigured', 'Accurate config missing', [
                'has_token' => $this->token !== '',
                'has_signature_key' => $this->signatureKey !== '',
                'has_signing_secret' => $this->signingSecret !== '',
                'application_name' => $this->applicationName,
                'sign_mode' => $this->signMode,
            ]);
            // #endregion
            return [
                'ok' => false,
                'status' => 0,
                'body' => '',
                'error' => implode(' ', $this->configurationErrorMessages()),
            ];
        }

        $method = strtoupper($method);
        $url = $this->absoluteUrl($pathWithQuery);
        $pathForSign = $this->extractPathAndQuery($url);
        $rawBody = $rawBody === null ? '' : (string) $rawBody;
        $signatureModes = array_values(array_unique(array_filter([
            'TIMESTAMP_ONLY',
            $this->signMode,
            'APPLICATION_METHOD_PATH_TIMESTAMP_BODY',
            'APPLICATION_TIMESTAMP_METHOD_PATH_BODY',
            'METHOD_PATH_TIMESTAMP_BODY',
            'TIMESTAMP_METHOD_PATH_BODY',
        ])));
        $signingKeys = $this->buildSigningKeys();

        // #region agent log
        $this->debugLog('pre-fix', 'H1', 'AccurateApiTokenClient.php:request:init', 'Prepared Accurate request options', [
            'method' => $method,
            'path_for_sign' => $pathForSign,
            'configured_sign_mode' => $this->signMode,
            'application_name' => $this->applicationName,
            'signature_modes' => $signatureModes,
            'signing_key_count' => count($signingKeys),
            'raw_body_length' => strlen($rawBody),
        ]);
        // #endregion

        $lastResponse = [
            'ok' => false,
            'status' => 0,
            'body' => '',
            'error' => 'Unable to call Accurate API.',
        ];
        $attemptedModes = [];
        $attemptCount = 0;

        foreach ($signatureModes as $signatureMode) {
            $this->signMode = $signatureMode;
            $attemptedModes[] = $signatureMode;
            foreach ($signingKeys as $signingKey) {
                $attemptCount++;
                $timestamp = $this->buildTimestampString();
                $signString = $this->buildSignString($method, $pathForSign, $timestamp, $rawBody);
                $signature = $this->buildSignature($method, $pathForSign, $timestamp, $rawBody, $signingKey);

                // Log signing attempt details for debugging signature mismatches
                try {
                    Log::debug('Accurate signing attempt', [
                        'mode' => $this->signMode,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                        'key_mask' => $this->maskKey($signingKey),
                        'sign_string' => $this->buildSignString($method, $pathForSign, $timestamp, $rawBody),
                    ]);
                } catch (\Throwable $e) {
                    // ignore logging errors
                }

                // #region agent log
                $this->debugLog('pre-fix', 'H2', 'AccurateApiTokenClient.php:request:attempt', 'Accurate signing attempt generated', [
                    'mode' => $this->signMode,
                    'application_name' => $this->applicationName,
                    'timestamp' => $timestamp,
                    'path_for_sign' => $pathForSign,
                    'sign_string_sha256' => hash('sha256', $signString),
                    'key_length' => strlen((string) $signingKey),
                    'is_key_base64_like' => base64_decode((string) $signingKey, true) !== false,
                ]);
                // #endregion

                $headers = [
                    'Accept: application/json',
                    'Authorization: Bearer '.$this->token,
                    'X-Api-Timestamp: '.$timestamp,
                    'X-Api-Signature: '.$signature,
                ];
                if ($this->applicationName !== '') {
                    $headers[] = 'X-Api-Application: '.$this->applicationName;
                    $headers[] = 'X-Api-Application-Name: '.$this->applicationName;
                }
                $needsJsonContentType = in_array($method, ['POST', 'PUT', 'PATCH'], true)
                    || ($method === 'DELETE' && $rawBody !== '');
                if ($needsJsonContentType) {
                    $headers[] = 'Content-Type: application/json';
                }

                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

                if ($method === 'GET' || $method === 'HEAD') {
                    curl_setopt($ch, CURLOPT_HTTPGET, true);
                } elseif ($method === 'DELETE') {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
                    if ($rawBody !== '') {
                        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
                    }
                } else {
                    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                    curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
                }

                $body = curl_exec($ch);
                $errno = curl_errno($ch);
                $errstr = curl_error($ch);
                $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
                curl_close($ch);

                if ($errno !== 0) {
                    $lastResponse = [
                        'ok' => false,
                        'status' => $status,
                        'body' => is_string($body) ? $body : '',
                        'error' => $errstr !== '' ? $errstr : 'curl error '.$errno,
                    ];
                    continue;
                }

                $bodyStr = is_string($body) ? $body : '';
                $httpOk = $status >= 200 && $status < 400;
                $businessOk = $this->responseBodyIndicatesAccurateSuccess($bodyStr, $status);

                $lastResponse = [
                    'ok' => $httpOk && $businessOk,
                    'status' => $status,
                    'body' => $bodyStr,
                    'error' => null,
                    'debug' => [
                        'configured_mode' => $configuredSignMode,
                        'current_mode' => $this->signMode,
                        'attempted_modes' => $attemptedModes,
                        'attempt_count' => $attemptCount,
                        'key_count' => count($signingKeys),
                    ],
                ];

                // #region agent log
                $this->debugLog('pre-fix', 'H4', 'AccurateApiTokenClient.php:request:response', 'Accurate response received', [
                    'mode' => $this->signMode,
                    'status' => $status,
                    'http_ok' => $httpOk,
                    'business_ok' => $businessOk,
                    'looks_like_signature_error' => $this->responseLooksLikeSignatureError($bodyStr, $status),
                    'body_preview' => substr($bodyStr, 0, 220),
                ]);
                // #endregion

                if ($lastResponse['ok']) {
                    return $lastResponse;
                }

                if (!$this->responseLooksLikeSignatureError($bodyStr, $status)) {
                    return $lastResponse;
                }

                // Log signature rejection details so we can compare with Postman
                try {
                    Log::warning('Accurate signature rejected', [
                        'mode' => $this->signMode,
                        'timestamp' => $timestamp,
                        'signature' => $signature,
                        'key_mask' => $this->maskKey($signingKey),
                        'response_status' => $status,
                        'response_body' => $bodyStr,
                        'sign_string' => $this->buildSignString($method, $pathForSign, $timestamp, $rawBody),
                    ]);
                } catch (\Throwable $e) {
                    // ignore logging errors
                }
            }
        }
        $lastResponse['debug'] = [
            'configured_mode' => $configuredSignMode,
            'current_mode' => $this->signMode,
            'attempted_modes' => $attemptedModes,
            'attempt_count' => $attemptCount,
            'key_count' => count($signingKeys),
        ];
        $this->signMode = $configuredSignMode;

        return $lastResponse;
    }

    /**
     * @return string[]
     */
    protected function buildSigningKeys()
    {
        $keys = [];

        if ($this->signatureKey !== '') {
            $keys[] = $this->signatureKey;
        }

        $decoded = base64_decode($this->signatureKey, true);
        if ($decoded !== false && $decoded !== '' && !in_array($decoded, $keys, true)) {
            $keys[] = $decoded;
        }

        if (empty($keys)) {
            $keys[] = '';
        }

        return $keys;
    }

    /**
     * Accurate JSON uses "s" and/or "success"; list responses include "d" + "sp".
     * Non-JSON or empty body with 200 is not treated as success (avoids false "Online" in UI).
     *
     * @param string $rawBody
     * @param int    $httpStatus
     * @return bool
     */
    protected function responseBodyIndicatesAccurateSuccess($rawBody, $httpStatus)
    {
        if ($rawBody === '') {
            return $httpStatus === 204;
        }

        $decoded = json_decode($rawBody, true);
        if (!is_array($decoded)) {
            return false;
        }

        if (array_key_exists('s', $decoded)) {
            return (bool) $decoded['s'];
        }
        if (array_key_exists('success', $decoded)) {
            return (bool) $decoded['success'];
        }

        return isset($decoded['d']) && is_array($decoded['d'])
            && isset($decoded['sp']) && is_array($decoded['sp']);
    }

    /**
     * Detect signature/application mismatches so we can retry with another sign mode.
     *
     * @param string $rawBody
     * @param int $httpStatus
     * @return bool
     */
    protected function responseLooksLikeSignatureError($rawBody, $httpStatus)
    {
        if (in_array($httpStatus, [401, 403], true)) {
            return true;
        }

        if ($rawBody === '') {
            return false;
        }

        return stripos($rawBody, 'X-Api-Signature invalid') !== false
            || stripos($rawBody, 'Require Application') !== false
            || stripos($rawBody, 'signature') !== false;
    }

    /**
     * @param string $pathWithQuery
     * @return string
     */
    protected function absoluteUrl($pathWithQuery)
    {
        if (preg_match('#^https?://#i', $pathWithQuery)) {
            return $pathWithQuery;
        }
        if ($pathWithQuery === '' || $pathWithQuery[0] !== '/') {
            $pathWithQuery = '/'.$pathWithQuery;
        }

        return $this->host.$pathWithQuery;
    }

    /**
     * @param string $absoluteUrl
     * @return string path + query, e.g. /accurate/api/department/list.do
     */
    protected function extractPathAndQuery($absoluteUrl)
    {
        $parts = parse_url($absoluteUrl);
        $path = isset($parts['path']) ? $parts['path'] : '/';
        if (!empty($parts['query'])) {
            $path .= '?'.$parts['query'];
        }

        return $path === '' ? '/' : $path;
    }

    /**
     * @param string $pathWithQuery
     * @return string
     */
    protected function normalizePathForSigning($pathWithQuery)
    {
        if (preg_match('#^https?://#i', $pathWithQuery)) {
            return $this->extractPathAndQuery($pathWithQuery);
        }
        if ($pathWithQuery === '' || $pathWithQuery[0] !== '/') {
            return '/'.$pathWithQuery;
        }

        return $pathWithQuery;
    }

    /**
     * Append NDJSON debug logs for this debug session.
     *
     * @param string $runId
     * @param string $hypothesisId
     * @param string $location
     * @param string $message
     * @param array  $data
     * @return void
     */
    protected function debugLog($runId, $hypothesisId, $location, $message, array $data = [])
    {
        $targetPath = base_path($this->debugLogPath);
        try {
            $payload = [
                'sessionId' => $this->debugSessionId,
                'runId' => $runId,
                'hypothesisId' => $hypothesisId,
                'location' => $location,
                'message' => $message,
                'data' => $data,
                'timestamp' => round(microtime(true) * 1000),
            ];
            // #region agent log
            $endpoint = 'http://127.0.0.1:7625/ingest/321bb544-7e4d-4a89-891c-42b49f2a0f34';
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
            $httpContext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => "Content-Type: application/json\r\nX-Debug-Session-Id: 566b62\r\n",
                    'content' => $jsonPayload,
                    'timeout' => 1,
                    'ignore_errors' => true,
                ],
            ]);
            @file_get_contents($endpoint, false, $httpContext);
            // #endregion
            $result = file_put_contents($targetPath, json_encode($payload, JSON_UNESCAPED_SLASHES).PHP_EOL, FILE_APPEND);
            if ($result === false) {
                Log::error('Agent debug log write failed', [
                    'target' => $targetPath,
                    'location' => $location,
                ]);
            }
        } catch (\Throwable $e) {
            Log::error('Agent debug log exception', [
                'target' => $targetPath,
                'location' => $location,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
