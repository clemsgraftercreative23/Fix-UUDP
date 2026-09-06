<?php

namespace App\Support;

use App\ReimbursementAttachment;
use Illuminate\Http\UploadedFile;

/**
 * Reads a receipt/invoice photo (via GeminiOcrClient) to extract its No.
 * Invoice/Receipt -- there is no typed value to compare it against, the
 * extracted number IS the value, deduped separately by the caller against
 * every other No. Invoice/Receipt on file. Nominal/amount is purely
 * manual input and is not verified against the receipt at all. Technical
 * failures (no API key, unsupported file, timeout, bad response) resolve
 * to "unavailable"/"skipped" so a broken OCR service never blocks a
 * submission on its own -- only an actual duplicate invoice number does
 * (checked by the caller, not here).
 */
class ReceiptOcrVerifier
{
    /** @var string[] */
    private const SUPPORTED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

    private GeminiOcrClient $client;

    public function __construct(?GeminiOcrClient $client = null)
    {
        $this->client = $client ?? new GeminiOcrClient();
    }

    /**
     * @return array{status: string, extracted_no_invoice: ?string, extracted_amount: ?float, message: string}
     */
    public function read(UploadedFile $file): array
    {
        if (empty(config('services.gemini.api_key'))) {
            return $this->result('unavailable', null, null, 'Verifikasi OCR tidak aktif (API key belum dikonfigurasi).');
        }

        $mimeType = null;
        try {
            $mimeType = $file->isValid() ? $file->getMimeType() : null;
        } catch (\Throwable $e) {
            $mimeType = null;
        }

        if (!$mimeType || !in_array($mimeType, self::SUPPORTED_MIME_TYPES, true)) {
            return $this->result('skipped', null, null, 'Verifikasi OCR dilewati (format file bukan foto/gambar yang didukung).');
        }

        $realPath = null;
        try {
            $realPath = $file->getRealPath();
        } catch (\Throwable $e) {
            $realPath = null;
        }

        if (!$realPath || !is_file($realPath)) {
            return $this->result('unavailable', null, null, 'Verifikasi OCR gagal membaca file yang diupload.');
        }

        $extraction = $this->client->extract($realPath, $mimeType);
        if (!$extraction['ok']) {
            // The raw API error (already logged by the client) isn't shown to the user --
            // it's an infra detail, not something they can act on.
            return $this->result('unavailable', null, null, 'Verifikasi OCR sedang tidak tersedia. Item tetap bisa disimpan; coba upload ulang struknya beberapa saat lagi kalau ingin diverifikasi.');
        }

        $extractedInvoice = $extraction['no_invoice'];
        $extractedAmount = $extraction['amount'];

        if ($extractedInvoice === null) {
            return $this->result('read', null, $extractedAmount, 'Struk terbaca, tetapi No. Invoice/Receipt tidak ditemukan pada foto.');
        }

        return $this->result('read', $extractedInvoice, $extractedAmount, 'No. Invoice/Receipt: ' . $extractedInvoice);
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

    private function result(string $status, ?string $extractedInvoice, ?float $extractedAmount, string $message): array
    {
        return [
            'status' => $status,
            'extracted_no_invoice' => $extractedInvoice,
            'extracted_amount' => $extractedAmount,
            'message' => $message,
        ];
    }
}
