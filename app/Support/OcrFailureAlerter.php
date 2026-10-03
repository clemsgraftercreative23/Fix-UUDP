<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Watches for repeated receipt-OCR failures -- whichever provider is active
 * (Veryfi by default, or Gemini; see services.ocr.provider) -- such as an
 * expired/invalid API key, quota exhausted, persistent timeouts, malformed
 * responses, etc., and pings an admin over WhatsApp (via FonnteMessenger)
 * once failures cluster past a threshold within a short window -- instead of
 * the current behaviour where every failure fails open silently, with the
 * real reason visible only in storage/logs/laravel.log (see
 * VeryfiOcrClient/GeminiOcrClient's Log::warning/error calls).
 *
 * Deliberately does NOT change what the end user sees in the receipt-upload
 * UI: ReceiptOcrVerifier still returns the same generic "OCR sedang tidak
 * tersedia" message to the browser. This class is purely a side-channel for
 * an admin to find out about a systemic problem (e.g. an expired API token)
 * without waiting for someone to notice their submissions have unverified
 * receipts and dig through the log themselves.
 *
 * A single failure doesn't page anyone -- OCR fails open by design (a bad
 * photo, a momentary network blip) and that's normal, not an incident. Only
 * a cluster of failures (default: 3 within 15 minutes) is treated as
 * "something is actually broken" and triggers one alert, followed by a
 * cooldown (default: 30 minutes) so a sustained outage doesn't spam the
 * admin's WhatsApp with one message per upload attempt.
 */
class OcrFailureAlerter
{
    private const COUNT_CACHE_KEY = 'ocr_failure_alerter:count';
    private const COOLDOWN_CACHE_KEY = 'ocr_failure_alerter:cooldown';

    /**
     * Call this once per OCR failure (extraction['ok'] === false). Never
     * throws -- a broken alert path must not break the receipt upload flow
     * it's watching.
     */
    public static function recordFailure(string $errorMessage): void
    {
        try {
            $phones = self::adminPhones();
            if (empty($phones)) {
                // No OCR_ALERT_PHONE configured -- alerting is opt-in, silently do nothing.
                return;
            }

            $threshold = max(1, (int) config('services.ocr_alert.threshold', 3));
            $windowMinutes = max(1, (int) config('services.ocr_alert.window_minutes', 15));
            $cooldownMinutes = max(1, (int) config('services.ocr_alert.cooldown_minutes', 30));

            $count = (int) Cache::get(self::COUNT_CACHE_KEY, 0) + 1;
            Cache::put(self::COUNT_CACHE_KEY, $count, now()->addMinutes($windowMinutes));

            if ($count < $threshold) {
                return;
            }

            if (Cache::has(self::COOLDOWN_CACHE_KEY)) {
                // Already alerted recently and the failures are still within the
                // same window -- don't send a WhatsApp message per upload attempt.
                return;
            }

            $message = "⚠ OCR Struk Bermasalah\n" .
                "Verifikasi OCR (Gemini) gagal {$count}x dalam {$windowMinutes} menit terakhir pada modul Reimbursement.\n" .
                "Error terakhir: {$errorMessage}\n" .
                "Submission tetap bisa disimpan (fail-open), tapi struk tidak terverifikasi selama ini berlangsung.\n" .
                "Cek storage/logs/laravel.log (cari \"Gemini OCR\") untuk detail teknis.";

            foreach ($phones as $phone) {
                FonnteMessenger::send($phone, $message, ['channel' => 'ocr_failure_alert']);
            }

            // Cooldown + reset the counter so the next window starts clean instead
            // of immediately re-tripping the threshold on the very next failure.
            Cache::put(self::COOLDOWN_CACHE_KEY, true, now()->addMinutes($cooldownMinutes));
            Cache::forget(self::COUNT_CACHE_KEY);
        } catch (\Throwable $e) {
            Log::error('OcrFailureAlerter: failed to process/send alert', ['error' => $e->getMessage()]);
        }
    }

    /** @return string[] */
    private static function adminPhones(): array
    {
        $raw = (string) config('services.ocr_alert.phone', '');
        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }
}
