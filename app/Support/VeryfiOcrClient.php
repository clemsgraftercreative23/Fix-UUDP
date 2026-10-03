<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Veryfi's "process a document" REST API
 * (POST /api/v8/partner/documents/), used to OCR a receipt/invoice photo
 * into the same small structured payload GeminiOcrClient produces (No.
 * Invoice/Receipt, amount, date, merchant, currency). Mirrors
 * GeminiOcrClient/FonnteMessenger's shape: config-driven, never throws,
 * logs and returns a failure array instead.
 *
 * Response field mapping is per Veryfi's v8 documented schema
 * (docs.veryfi.com/api/receipts-invoices/process-a-document/):
 *   - vendor name:      vendor.name (nested) -- raw_vendor_name/vendor_name
 *                        are checked too, defensively, in case a given
 *                        account/API version returns it top-level instead
 *   - invoice number:   invoice_number (top-level)
 *   - total amount:     total (top-level)
 *   - currency:         currency_code (top-level)
 *   - transaction date: date (top-level, "YYYY-MM-DD HH:MM:SS" -- only the
 *                        date part is kept)
 * These were confirmed against Veryfi's own docs while wiring this up, but
 * Veryfi's exact response shape can vary a little by account/API version --
 * if extraction starts silently coming back empty after this goes live,
 * log a real `raw` response once (see the `raw` key in extract()'s return)
 * and re-check field names against it before assuming the receipt itself
 * was unreadable.
 */
class VeryfiOcrClient implements OcrClientInterface
{
    /** Per-request timeout. Veryfi's OCR is typically a few seconds; anything hitting this is a stalled connection. */
    private const REQUEST_TIMEOUT_SECONDS = 20;

    public function isConfigured(): bool
    {
        $clientId = config('services.veryfi.client_id');
        $apiKey = config('services.veryfi.api_key');

        // username is only required for the legacy "apikey user:key" auth
        // scheme -- a Bearer API key (set as api_key with username left
        // blank) doesn't need one, see buildAuthHeader().
        return !empty($clientId) && !empty($apiKey);
    }

    /**
     * @return array{ok: bool, no_invoice: ?string, amount: ?float, transaction_date: ?string, merchant_name: ?string, currency: ?string, error: ?string, raw: array}
     */
    public function extract(string $absoluteFilePath, string $mimeType): array
    {
        if (!$this->isConfigured()) {
            return $this->failure('Veryfi credentials not configured');
        }

        if (!is_file($absoluteFilePath)) {
            return $this->failure('File not found');
        }

        $bytes = @file_get_contents($absoluteFilePath);
        if ($bytes === false) {
            return $this->failure('Unable to read file');
        }

        $base64 = base64_encode($bytes);
        $baseUrl = rtrim((string) config('services.veryfi.base_url', 'https://api.veryfi.com'), '/');
        $url = $baseUrl . '/api/v8/partner/documents/';

        try {
            $decoded = \Curl::to($url)
                ->withHeaders([
                    'CLIENT-ID: ' . (string) config('services.veryfi.client_id'),
                    'AUTHORIZATION: ' . $this->buildAuthHeaderValue(),
                ])
                ->withData([
                    'file_data' => $base64,
                    'file_name' => basename($absoluteFilePath),
                ])
                ->asJson(true)
                ->withTimeout(self::REQUEST_TIMEOUT_SECONDS)
                ->post();

            if (!is_array($decoded)) {
                Log::warning('Veryfi OCR: non-JSON response', ['response' => $decoded]);
                return $this->failure('Invalid response from OCR service');
            }

            // Veryfi signals failure two different ways depending on the
            // error: an {"error": ...} key (documented shape), or
            // {"status": "fail", "message": "..."} (the actual shape seen in
            // practice for an auth rejection -- "Not Authorized" -- which the
            // {"error"} check alone silently missed, letting an auth failure
            // fall through into the success path below with every field
            // reading blank instead of a clear "unavailable" status).
            if (isset($decoded['error']) || (($decoded['status'] ?? null) === 'fail')) {
                $message = isset($decoded['error'])
                    ? (is_array($decoded['error']) ? ($decoded['error']['message'] ?? 'OCR API error') : (string) $decoded['error'])
                    : (string) ($decoded['message'] ?? 'OCR API error');
                Log::warning('Veryfi OCR: API error', ['error' => $message, 'raw' => $decoded]);
                return array_merge($this->failure($message), ['raw' => $decoded]);
            }

            // TEMPORARY diagnostic: field names below (vendor.name,
            // invoice_number, total, currency_code) were guessed from
            // Veryfi's docs, not a real response, and the first live test
            // (Sep 22 2026) came back with merchant/amount/currency/invoice
            // all empty -- only `date` extracted correctly. Logging the full
            // raw response here (once, on every successful call) until the
            // real field names are confirmed and this mapping is fixed, then
            // this Log::info call should be removed or dropped to a lower
            // frequency (it will contain the receipt's OCR'd text/amounts).
            Log::info('Veryfi OCR: raw response (temporary diagnostic)', ['raw' => $decoded]);

            $vendorName = $this->nullableString(
                data_get($decoded, 'vendor.name')
                    ?? data_get($decoded, 'vendor.raw_name')
                    ?? ($decoded['raw_vendor_name'] ?? null)
                    ?? ($decoded['vendor_name'] ?? null)
            );

            $invoiceNumber = $this->nullableString($decoded['invoice_number'] ?? null);

            $amount = isset($decoded['total']) && is_numeric($decoded['total'])
                ? (float) $decoded['total']
                : null;

            $currency = $this->nullableString($decoded['currency_code'] ?? null);
            if ($currency !== null) {
                $currency = strtoupper($currency);
                if (!preg_match('/^[A-Z]{3}$/', $currency)) {
                    $currency = null;
                }
            }

            $transactionDate = null;
            $rawDate = $this->nullableString($decoded['date'] ?? null);
            if ($rawDate !== null && preg_match('/^(\d{4}-\d{2}-\d{2})/', $rawDate, $m)) {
                $transactionDate = $m[1];
            }

            return [
                'ok' => true,
                'no_invoice' => $invoiceNumber,
                'amount' => $amount,
                'transaction_date' => $transactionDate,
                'merchant_name' => $vendorName,
                'currency' => $currency,
                'error' => null,
                'raw' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::error('Veryfi OCR: request failed', ['error' => $e->getMessage()]);
            return $this->failure('OCR request failed: ' . $e->getMessage());
        }
    }

    /**
     * Standard API keys ("apikey username:api_key") need a username; Veryfi's
     * newer Bearer API keys don't -- if no username is configured, treat
     * `api_key` as a Bearer key instead of assuming misconfiguration.
     */
    private function buildAuthHeaderValue(): string
    {
        $username = trim((string) config('services.veryfi.username', ''));
        $apiKey = (string) config('services.veryfi.api_key');

        if ($username === '') {
            return 'Bearer ' . $apiKey;
        }

        return 'apikey ' . $username . ':' . $apiKey;
    }

    private function failure(string $error): array
    {
        return [
            'ok' => false,
            'no_invoice' => null,
            'amount' => null,
            'transaction_date' => null,
            'merchant_name' => null,
            'currency' => null,
            'error' => $error,
            'raw' => [],
        ];
    }

    /** Trim a nullable OCR string field, treating blank/the literal "null" as absent. */
    private function nullableString($value): ?string
    {
        if (!is_scalar($value)) {
            return null;
        }
        $value = trim((string) $value);
        return ($value === '' || strtolower($value) === 'null') ? null : $value;
    }
}
