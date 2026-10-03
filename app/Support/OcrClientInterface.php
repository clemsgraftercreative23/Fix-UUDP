<?php

namespace App\Support;

/**
 * Common contract for a receipt/invoice OCR provider (GeminiOcrClient,
 * VeryfiOcrClient, ...) so ReceiptOcrVerifier can swap providers via config
 * (see services.ocr.provider) without any caller needing to know which one
 * is active. Both implementations must never throw -- config problems,
 * network errors, and bad responses all resolve to a `['ok' => false, ...]`
 * result instead, matching the existing "fail open" behaviour (a broken OCR
 * provider never blocks a submission).
 */
interface OcrClientInterface
{
    /** Whether this provider has the credentials it needs to be called at all -- checked before extract() so a misconfigured provider fails cheaply with a clear reason instead of making (and failing) a real HTTP request. */
    public function isConfigured(): bool;

    /**
     * @return array{ok: bool, no_invoice: ?string, amount: ?float, transaction_date: ?string, merchant_name: ?string, currency: ?string, error: ?string, raw: array}
     */
    public function extract(string $absoluteFilePath, string $mimeType): array;
}
