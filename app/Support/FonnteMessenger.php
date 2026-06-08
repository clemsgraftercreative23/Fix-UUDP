<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

class FonnteMessenger
{
    /**
     * Normalize Indonesian phone numbers for Fonnte (628xxx).
     * Accurate sync often stores 08xxx; Fonnte expects 62xxx.
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if ($phone === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone);
        if ($digits === null || $digits === '') {
            return null;
        }

        if (isset($digits[0]) && $digits[0] === '0') {
            $digits = '62' . substr($digits, 1);
        } elseif (isset($digits[0]) && $digits[0] === '8') {
            $digits = '62' . $digits;
        }

        $length = strlen($digits);
        if ($length < 10 || $length > 15) {
            return null;
        }

        return $digits;
    }

    public static function send(?string $target, string $message, array $context = []): ?array
    {
        $normalized = self::normalizePhone($target);
        if ($normalized === null) {
            Log::warning('WhatsApp skipped: invalid or empty phone number', array_merge($context, [
                'original_target' => $target,
            ]));

            return null;
        }

        $token = config('services.fonnte.token');
        if (empty($token)) {
            Log::warning('WhatsApp skipped: Fonnte token not configured', $context);

            return null;
        }

        try {
            $response = \Curl::to(config('services.fonnte.base_url', 'https://api.fonnte.com/send'))
                ->withHeaders(['Authorization: ' . $token])
                ->withData([
                    'target' => $normalized,
                    'message' => $message,
                ])
                ->post();

            $decoded = json_decode((string) $response, true);
            if (is_array($decoded) && array_key_exists('status', $decoded) && !$decoded['status']) {
                Log::warning('WhatsApp send failed', array_merge($context, [
                    'target' => $normalized,
                    'original_target' => $target,
                    'response' => $decoded,
                ]));
            }

            return is_array($decoded) ? $decoded : ['raw' => (string) $response];
        } catch (\Throwable $e) {
            Log::error('WhatsApp send error', array_merge($context, [
                'target' => $normalized,
                'original_target' => $target,
                'error' => $e->getMessage(),
            ]));

            return null;
        }
    }
}
