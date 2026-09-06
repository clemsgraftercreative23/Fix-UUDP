<?php

namespace App\Support;

use Illuminate\Support\Facades\Log;

/**
 * Thin wrapper around Google's Gemini API (generateContent), used to OCR a
 * receipt/invoice photo into a small structured payload (No. Invoice/
 * Receipt + amount). Mirrors FonnteMessenger's shape: config-driven,
 * never throws, logs and returns a failure array instead.
 */
class GeminiOcrClient
{
    /** Delays (ms) between retries when Gemini reports the model is overloaded -- per Google, these spikes are "usually temporary". */
    private const RETRY_DELAYS_MS = [1000, 2000];

    private const PROMPT = <<<'PROMPT'
You are reading a receipt or invoice photo for an expense reimbursement system.
Extract exactly two fields and reply with STRICT JSON only, no prose, no markdown
code fences, matching this shape:
{"no_invoice": "<the invoice/receipt/transaction number printed on it, or null if none is visible>", "amount": <the total amount paid as a plain number with no currency symbol or thousands separator, or null if unreadable>}
If the image is not a receipt/invoice at all, reply {"no_invoice": null, "amount": null}.
PROMPT;

    /**
     * @return array{ok: bool, no_invoice: ?string, amount: ?float, error: ?string, raw: array}
     */
    public function extract(string $absoluteFilePath, string $mimeType): array
    {
        $apiKey = config('services.gemini.api_key');
        if (empty($apiKey)) {
            return $this->failure('Gemini API key not configured');
        }

        if (!is_file($absoluteFilePath)) {
            return $this->failure('File not found');
        }

        $bytes = @file_get_contents($absoluteFilePath);
        if ($bytes === false) {
            return $this->failure('Unable to read file');
        }

        $base64 = base64_encode($bytes);
        $baseUrl = rtrim((string) config('services.gemini.base_url', 'https://generativelanguage.googleapis.com/v1beta'), '/');
        $model = config('services.gemini.ocr_model', 'gemini-3.8-flash');
        $url = $baseUrl . '/models/' . $model . ':generateContent?key=' . urlencode($apiKey);

        $maxAttempts = count(self::RETRY_DELAYS_MS) + 1;
        $result = [];
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            if ($attempt > 0) {
                Log::warning('Gemini OCR: retrying after overloaded response', ['attempt' => $attempt]);
                usleep(self::RETRY_DELAYS_MS[$attempt - 1] * 1000);
            }

            $result = $this->sendRequest($url, $mimeType, $base64);
            if ($result['ok'] || !$this->isOverloaded($result)) {
                return $result;
            }
        }

        // Every attempt hit the same "model overloaded" condition -- give up and let the
        // caller fail open. Google's own guidance is that these spikes are temporary, so
        // this isn't treated as a hard error, just an unavailable-for-now result.
        return $result;
    }

    private function failure(string $error): array
    {
        return ['ok' => false, 'no_invoice' => null, 'amount' => null, 'error' => $error, 'raw' => []];
    }

    /** Gemini reports model overload as HTTP 503 / status "UNAVAILABLE" -- per Google, these spikes are usually temporary and worth a quick retry. */
    private function isOverloaded(array $result): bool
    {
        $raw = $result['raw']['error'] ?? null;
        if (!is_array($raw)) {
            return false;
        }

        return (int) ($raw['code'] ?? 0) === 503 || ($raw['status'] ?? '') === 'UNAVAILABLE';
    }

    private function sendRequest(string $url, string $mimeType, string $base64): array
    {
        try {
            $decoded = \Curl::to($url)
                ->withData([
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => self::PROMPT],
                                ['inline_data' => ['mime_type' => $mimeType, 'data' => $base64]],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'responseMimeType' => 'application/json',
                        'temperature' => 0,
                    ],
                ])
                ->asJson(true)
                ->withTimeout(45)
                ->post();

            if (!is_array($decoded)) {
                Log::warning('Gemini OCR: non-JSON response', ['response' => $decoded]);
                return $this->failure('Invalid response from OCR service');
            }

            if (isset($decoded['error'])) {
                Log::warning('Gemini OCR: API error', ['error' => $decoded['error']]);
                $message = is_array($decoded['error']) ? ($decoded['error']['message'] ?? 'OCR API error') : (string) $decoded['error'];
                return array_merge($this->failure($message), ['raw' => $decoded]);
            }

            $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
            if (!is_string($content) || $content === '') {
                Log::warning('Gemini OCR: empty content in response', ['decoded' => $decoded]);
                return $this->failure('OCR service returned no content');
            }

            $extracted = $this->extractJsonObject($content);
            if ($extracted === null) {
                Log::warning('Gemini OCR: could not parse JSON from model reply', ['content' => $content]);
                return $this->failure('Could not parse OCR result');
            }

            $noInvoice = isset($extracted['no_invoice']) && is_scalar($extracted['no_invoice'])
                ? trim((string) $extracted['no_invoice'])
                : null;
            $noInvoice = ($noInvoice === '' || strtolower((string) $noInvoice) === 'null') ? null : $noInvoice;

            $amount = isset($extracted['amount']) && is_numeric($extracted['amount'])
                ? (float) $extracted['amount']
                : null;

            return [
                'ok' => true,
                'no_invoice' => $noInvoice,
                'amount' => $amount,
                'error' => null,
                'raw' => $decoded,
            ];
        } catch (\Throwable $e) {
            Log::error('Gemini OCR: request failed', ['error' => $e->getMessage()]);
            return $this->failure('OCR request failed: ' . $e->getMessage());
        }
    }

    /** Pull the first {...} JSON object out of a model reply that may be wrapped in prose or code fences. */
    private function extractJsonObject(string $content): ?array
    {
        $direct = json_decode(trim($content), true);
        if (is_array($direct)) {
            return $direct;
        }

        if (preg_match('/\{.*\}/s', $content, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return null;
    }
}
