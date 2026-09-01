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

    /**
     * Array variant for forms where one submission can carry more than one
     * receipt (e.g. Travel: one invoice number per day/item). Falls back to
     * a single number when no array is given, mirroring DuplicateDateChecker.
     *
     * @param mixed $rawNumbers
     */
    public static function normalizeNumbers($rawNumbers, ?string $singleNumber = null): array
    {
        $numbers = is_array($rawNumbers) ? $rawNumbers : [];
        if (empty($numbers) && $singleNumber !== null) {
            $numbers = [$singleNumber];
        }

        $normalized = array_map(function ($number) {
            return is_scalar($number) ? self::normalizeNumber($number) : '';
        }, $numbers);

        return array_values(array_unique(array_filter($normalized, function ($number) {
            return $number !== '';
        })));
    }

    /**
     * @param string[] $requestedNumbers numbers the user is trying to submit
     * @param string[] $usedNumbers numbers already found on file, anywhere
     */
    public static function buildBatchResponse(array $requestedNumbers, array $usedNumbers): array
    {
        $duplicateNumbers = array_values(array_unique(array_intersect($requestedNumbers, $usedNumbers)));
        $isDuplicate = !empty($duplicateNumbers);

        return [
            'duplicate' => $isDuplicate,
            'code' => $isDuplicate ? 'DUPLICATE_INVOICE_NUMBER' : 'OK',
            'message' => $isDuplicate
                ? 'Nomor invoice/receipt ' . implode(', ', $duplicateNumbers)
                    . ' sudah pernah digunakan pada pengajuan reimbursement sebelumnya. '
                    . 'Pastikan ini bukan invoice/receipt yang sama sebelum melanjutkan.'
                : 'Nomor invoice/receipt belum pernah digunakan sebelumnya.',
            'duplicate_numbers' => $duplicateNumbers,
        ];
    }
}
