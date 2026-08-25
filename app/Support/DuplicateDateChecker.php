<?php

namespace App\Support;

/**
 * Pure logic for "Validasi Tanggal Pengajuan": given the dates a user is
 * about to submit and the dates they already have on file, decide whether
 * this looks like a duplicate submission and build the API response.
 */
class DuplicateDateChecker
{
    /** @param mixed $rawDates */
    public static function normalizeDates($rawDates, ?string $singleDate = null): array
    {
        $dates = is_array($rawDates) ? $rawDates : [];
        if (empty($dates) && $singleDate) {
            $dates = [$singleDate];
        }

        return array_values(array_unique(array_filter($dates, function ($date) {
            return is_string($date) && $date !== '';
        })));
    }

    /**
     * @param string[] $requestedDates dates the user is trying to submit
     * @param string[] $existingDates dates already found on file for this user/type
     */
    public static function buildResponse(array $requestedDates, array $existingDates): array
    {
        $duplicateDates = array_values(array_unique(array_intersect($requestedDates, $existingDates)));
        $isDuplicate = !empty($duplicateDates);

        return [
            'duplicate' => $isDuplicate,
            'code' => $isDuplicate ? 'DUPLICATE_REIMBURSEMENT_DATE' : 'OK',
            'message' => $isDuplicate
                ? 'Anda sudah pernah mengajukan reimbursement untuk tanggal '
                    . implode(', ', $duplicateDates) . '. Pastikan pengajuan ini bukan duplikat sebelum melanjutkan.'
                : 'Tanggal pengajuan belum pernah diajukan sebelumnya.',
            'duplicate_dates' => $duplicateDates,
        ];
    }
}
