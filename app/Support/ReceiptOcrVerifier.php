<?php

namespace App\Support;

use App\ReimbursementAttachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

/**
 * Reads a receipt/invoice photo (via whichever OcrClientInterface provider
 * is configured -- see services.ocr.provider, currently Veryfi by default,
 * Gemini as the alternative) to extract its No. Invoice/Receipt -- there is
 * no typed value to compare it against, the extracted number IS the value,
 * deduped separately by the caller against every other No. Invoice/Receipt
 * on file. Nominal/amount is purely manual input and is not verified
 * against the receipt at all. Technical failures (no API key, unsupported
 * file, timeout, bad response) resolve to "unavailable"/"skipped" so a
 * broken OCR service never blocks a submission on its own -- only an actual
 * duplicate invoice number does (checked by the caller, not here).
 */
class ReceiptOcrVerifier
{
    /** @var string[] */
    private const SUPPORTED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif', 'application/pdf'];

    private OcrClientInterface $client;

    public function __construct(?OcrClientInterface $client = null)
    {
        $this->client = $client ?? self::resolveDefaultClient();
    }

    /** Picks the OCR provider from config -- 'veryfi' (default) or 'gemini'. Any other/unrecognised value also falls back to Veryfi rather than silently disabling OCR. */
    private static function resolveDefaultClient(): OcrClientInterface
    {
        $provider = strtolower(trim((string) config('services.ocr.provider', 'veryfi')));

        return $provider === 'gemini' ? new GeminiOcrClient() : new VeryfiOcrClient();
    }

    /**
     * @return array{status: string, extracted_no_invoice: ?string, extracted_amount: ?float, extracted_transaction_date: ?string, extracted_merchant_name: ?string, extracted_currency: ?string, message: string}
     */
    public function read(UploadedFile $file): array
    {
        if (!$this->client->isConfigured()) {
            // Previously silent -- "OCR tidak tersedia" with nothing in the
            // log to explain why. Most likely causes: services.veryfi.*
            // (or gemini.*) missing from .env, or a stale cached config
            // (php artisan config:cache run before those env vars were set)
            // still being served -- `config:clear` rules that out fast.
            Log::warning('OCR unavailable: client not configured', ['provider' => get_class($this->client)]);
            return $this->result('unavailable', null, null, null, null, null, 'Verifikasi OCR tidak aktif (API key/kredensial belum dikonfigurasi).');
        }

        $mimeType = null;
        try {
            $mimeType = $file->isValid() ? $file->getMimeType() : null;
        } catch (\Throwable $e) {
            $mimeType = null;
        }

        if (!$mimeType || !in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return $this->result('skipped', null, null, null, null, null, 'Verifikasi OCR dilewati (format file bukan foto/gambar yang didukung).');
        }

        $realPath = null;
        try {
            $realPath = $file->getRealPath();
        } catch (\Throwable $e) {
            $realPath = null;
        }

        if (!$realPath || !is_file($realPath)) {
            // Previously silent too. Typically means the upload's temp file
            // was already gone by the time OCR ran (e.g. a second read of an
            // UploadedFile whose tmp file was already moved/cleaned up) or a
            // genuinely corrupt/zero-byte upload -- not a Veryfi/Gemini issue
            // at all, so it wouldn't show up in that client's own logging.
            Log::warning('OCR unavailable: could not read uploaded file', [
                'original_name' => (function () use ($file) {
                    try {
                        return $file->getClientOriginalName();
                    } catch (\Throwable $e) {
                        return null;
                    }
                })(),
                'real_path' => $realPath,
            ]);
            return $this->result('unavailable', null, null, null, null, null, 'Verifikasi OCR gagal membaca file yang diupload.');
        }

        $extraction = $this->client->extract($realPath, $mimeType);
        if (!$extraction['ok']) {
            // The raw API error (already logged by the client) isn't shown to the user --
            // it's an infra detail, not something they can act on. An admin still needs
            // to find out when this isn't just a one-off blip though (e.g. an expired API
            // key failing every single upload) -- OcrFailureAlerter pings WhatsApp once
            // failures cluster past a threshold, without changing what the user sees here.
            OcrFailureAlerter::recordFailure((string) ($extraction['error'] ?? 'Unknown error'));
            return $this->result('unavailable', null, null, null, null, null, 'Verifikasi OCR sedang tidak tersedia. Item tetap bisa disimpan; coba upload ulang struknya beberapa saat lagi kalau ingin diverifikasi.');
        }

        $extractedInvoice = $extraction['no_invoice'];
        $extractedAmount = $extraction['amount'];
        $extractedDate = $extraction['transaction_date'] ?? null;
        $extractedMerchant = $extraction['merchant_name'] ?? null;
        $extractedCurrency = $extraction['currency'] ?? null;

        // A receipt always carries at least a total or a transaction date.
        // When BOTH are missing the photo is not a receipt, and that verdict
        // must not be overridden by an invoice number alone: OCR happily
        // returns a "number" for any digits it finds -- a licence plate, a
        // phone number, text on a banner -- so a photo of a car came back as
        // a valid invoice (Sep 2026). Checking amount+date first closes that
        // hole; the invoice number is only trusted once the photo has proven
        // receipt-shaped. Still fails open: a warning, never a hard block.
        //
        // Merchant name is deliberately NOT part of this check: Veryfi has
        // been observed hallucinating a plausible-looking vendor name
        // (matched against its own vendor database/logo lookup) even from
        // garbled, non-receipt OCR text -- confirmed from a real production
        // case where invoice_number/total/date all came back null with a
        // nonsense ocr_text, but a populated vendor.name anyway. Amount and
        // date are far harder to hallucinate a convincing value for, so both
        // being blank is the reliable signal (works the same for Gemini,
        // which is explicitly prompted to null everything out for a
        // non-receipt photo -- see GeminiOcrClient::PROMPT).
        if ($extractedAmount === null && $extractedDate === null) {
            return $this->result('not_receipt', null, null, null, null, null, 'Foto ini sepertinya bukan invoice/struk (tidak ada nominal atau tanggal transaksi yang terbaca dengan jelas). Coba upload ulang foto struk/invoice yang jelas.');
        }

        if ($extractedInvoice === null) {
            // amount/date already checked above, so reaching here means the
            // photo IS receipt-shaped -- it just has no invoice number field.
            return $this->result('read', null, $extractedAmount, $extractedDate, $extractedMerchant, $extractedCurrency, 'Struk terbaca, tetapi No. Invoice/Receipt tidak ditemukan pada foto.');
        }

        return $this->result('read', $extractedInvoice, $extractedAmount, $extractedDate, $extractedMerchant, $extractedCurrency, 'No. Invoice/Receipt: ' . $extractedInvoice);
    }

    /**
     * Runs OCR now on the first not-yet-checked attachment for a detail row
     * (ocr_status IS NULL -- carried forward from a "kept" resave that never
     * re-uploads, or from before this feature existed), persisting the
     * result onto that attachment record so it isn't re-checked again next
     * time. Lets a row that was never actually OCR'd catch up and become
     * dedup-able instead of staying silently unverified forever.
     *
     * @return string normalized extracted invoice number, or '' if there was
     *   nothing to backfill or nothing readable
     */
    public function backfillUncheckedAttachment(string $detailType, int $detailId): string
    {
        $attachment = ReimbursementAttachment::where('detail_type', $detailType)
            ->where('detail_id', $detailId)
            ->whereNull('ocr_status')
            ->orderBy('id')
            ->first();

        if (!$attachment || empty($attachment->file_name)) {
            return '';
        }

        $absolutePath = public_path('images/file_bukti/' . $attachment->file_name);
        if (!is_file($absolutePath)) {
            return '';
        }

        $fakeUpload = new UploadedFile($absolutePath, $attachment->file_name, null, null, true);
        $result = $this->read($fakeUpload);

        $attachment->update([
            'ocr_status' => $result['status'],
            'ocr_extracted_no_invoice' => $result['extracted_no_invoice'],
            'ocr_extracted_amount' => $result['extracted_amount'],
            'ocr_message' => $result['message'],
            'ocr_checked_at' => now(),
        ]);

        return !empty($result['extracted_no_invoice'])
            ? DuplicateInvoiceChecker::normalizeNumber($result['extracted_no_invoice'])
            : '';
    }

    private function result(
        string $status,
        ?string $extractedInvoice,
        ?float $extractedAmount,
        ?string $extractedDate,
        ?string $extractedMerchant,
        ?string $extractedCurrency,
        string $message
    ): array {
        return [
            'status' => $status,
            'extracted_no_invoice' => $extractedInvoice,
            'extracted_amount' => $extractedAmount,
            'extracted_transaction_date' => $extractedDate,
            'extracted_merchant_name' => $extractedMerchant,
            'extracted_currency' => $extractedCurrency,
            'message' => $message,
        ];
    }
}
