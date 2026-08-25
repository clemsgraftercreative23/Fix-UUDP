<?php

namespace App\Support;

/**
 * Pure logic for "Validasi Invoice / Receipt": given a submitted invoice/
 * receipt number, decide whether it has already been used on another
 * reimbursement and build the API response. Unlike the submission-date
 * check, this is intentionally NOT scoped to the current user or a single
 * reimbursement type — a physical receipt shouldn't be claimed twice by
 * anyone, for anything.
 */
class DuplicateInvoiceChecker
{
    public static function normalizeNumber($rawNumber): string
    {
        return trim((string) $rawNumber);
    }

    public static function buildResponse(string $number, bool $alreadyUsed): array
    {
        return [
            'duplicate' => $alreadyUsed,
            'code' => $alreadyUsed ? 'DUPLICATE_INVOICE_NUMBER' : 'OK',
            'message' => $alreadyUsed
                ? "Nomor invoice/receipt \"{$number}\" sudah pernah digunakan pada pengajuan reimbursement sebelumnya. "
                    . 'Pastikan ini bukan invoice/receipt yang sama sebelum melanjutkan.'
                : 'Nomor invoice/receipt belum pernah digunakan sebelumnya.',
        ];
    }
}
